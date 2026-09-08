<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Service;

use OCA\Viewer\Model\PhotosphereMetadata;
use OCP\Files\File;
use Psr\Log\LoggerInterface;

/**
 * Reads the Google Photo Sphere XMP metadata from the first bytes of a
 * JPEG file (see https://developers.google.com/streetview/spherical-metadata).
 *
 * Ported from the files_photospheres app
 * (https://github.com/nextcloud/files_photospheres).
 *
 * @psalm-api
 */
class PhotosphereMetadataReader {
	/**
	 * Read files in 8kb blocks
	 */
	private const int CHUNK_SIZE = 8192;

	/**
	 * Maximum of blocks to read for xmp-data
	 * (read max 800kb of the file)
	 */
	private const int MAX_BLOCK_COUNT = 100;

	/**
	 * XML-start-tag for xmp-data
	 */
	private const string XMP_START_TAG = '<x:xmpmeta';

	/**
	 * XML-end-tag for xmp-data
	 */
	private const string XMP_END_TAG = '</x:xmpmeta>';

	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	public function fromFile(File $file): PhotosphereMetadata {
		$firstFileBytesString = $this->readFirstFileBlocks($file);
		return $this->fromString($firstFileBytesString);
	}

	public function fromString(string $fileString): PhotosphereMetadata {
		$posStart = strpos($fileString, self::XMP_START_TAG);
		$posEnd = strpos($fileString, self::XMP_END_TAG);

		$metadata = new PhotosphereMetadata();

		// We need both the start and end tag
		if ($posStart === false || $posEnd === false) {
			return $metadata;
		}

		// Check if we should use the panorama viewer
		$metadata->usePanoramaViewer = $this->shouldUsePanoramaViewer($fileString);

		$bufferCutStart = substr($fileString, $posStart);
		$buffer = substr($bufferCutStart, 0, $posEnd + strlen(self::XMP_END_TAG));

		$this->fillCroppingConfig($buffer, $metadata);

		return $metadata;
	}

	private function readFirstFileBlocks(File $file): string {
		$fileHandle = $file->fopen('rb');
		if (!$fileHandle) {
			throw new \Exception('Could not open file');
		}

		$data = '';
		$blocksRead = 0;

		while (!feof($fileHandle) && strpos($data, self::XMP_END_TAG) === false && $blocksRead < self::MAX_BLOCK_COUNT) {
			$chunk = fread($fileHandle, self::CHUNK_SIZE);
			if ($chunk !== false) {
				$data .= $chunk;
			}
			$blocksRead++;
		}
		fclose($fileHandle);

		return $data;
	}

	private function shouldUsePanoramaViewer(string $xmlString): bool {
		// GPano:UsePanoramaViewer tag (optional)
		$usePanoViewerStr = $this->getXmpStringValue($xmlString, 'UsePanoramaViewer');
		if ($usePanoViewerStr !== null) {
			return strtolower($usePanoViewerStr) === 'true';
		}

		// Since GPano:UsePanoramaViewer is optional, take a look at GPano:ProjectionType
		$projectionTypeStr = $this->getXmpStringValue($xmlString, 'ProjectionType');
		if ($projectionTypeStr !== null) {
			return strtolower($projectionTypeStr) === 'equirectangular';
		}

		/*
			We also support VR180. These cameras often don't set the
			ProjectionType but it's mandatory to set GImage:Mime
			(see https://developers.google.com/vr/reference/cardboard-camera-vr-photo-format).
			So if that tag is set to image/jpeg in the first xmpdata block,
			we can guess that it's a VR180 image which can be shown as photosphere.
		*/
		$gImageMimeStr = $this->getXmpStringValue($xmlString, 'Mime', 'GImage');
		if ($gImageMimeStr !== null) {
			return strtolower($gImageMimeStr) === 'image/jpeg';
		}

		return false;
	}

	private function fillCroppingConfig(string $xmlString, PhotosphereMetadata $metadata): void {
		/*
			This logic is mainly taken from photo-sphere-viewer.js -> TextureLoader.js
		*/
		$gPanoMatch = @preg_match('/GPano:/', $xmlString);
		if ($gPanoMatch === false) {
			$this->logger->warning("Regex match on 'GPano:' failed");
			return;
		}
		if ($gPanoMatch === 0) {
			// containsCroppingConfig is false here
			return;
		}

		$metadata->containsCroppingConfig = true;

		$metadata->croppingConfig->fullWidth = $this->getXmpIntValue($xmlString, 'FullPanoWidthPixels');
		$metadata->croppingConfig->fullHeight = $this->getXmpIntValue($xmlString, 'FullPanoHeightPixels');
		$metadata->croppingConfig->croppedWidth = $this->getXmpIntValue($xmlString, 'CroppedAreaImageWidthPixels');
		$metadata->croppingConfig->croppedHeight = $this->getXmpIntValue($xmlString, 'CroppedAreaImageHeightPixels');
		$metadata->croppingConfig->croppedX = $this->getXmpIntValue($xmlString, 'CroppedAreaLeftPixels');
		$metadata->croppingConfig->croppedY = $this->getXmpIntValue($xmlString, 'CroppedAreaTopPixels');
		$metadata->croppingConfig->poseHeading = $this->getXmpFloatValue($xmlString, 'PoseHeadingDegrees');
		$metadata->croppingConfig->posePitch = $this->getXmpFloatValue($xmlString, 'PosePitchDegrees');
		$metadata->croppingConfig->poseRoll = $this->getXmpFloatValue($xmlString, 'PoseRollDegrees');
	}

	private function getXmpStringValue(string $xmlString, string $xmpKey, string $xmlNs = 'GPano'): ?string {
		// XMP data can be stored in attributes or separate nodes
		$matchResult = @preg_match("/$xmlNs:$xmpKey((.?=.?\"(.*?)\")|(>(.*?)<))/", $xmlString, $matchValues);

		if ($matchResult === false) {
			$this->logger->warning("Regex match on XMP metadata '$xmpKey' failed");
			return null;
		}

		$value = $matchValues === [] ? false : end($matchValues);
		return $matchResult === 1 && $value !== false ? $value : null;
	}

	private function getXmpIntValue(string $xmlString, string $xmpKey): ?int {
		$str = $this->getXmpStringValue($xmlString, $xmpKey);

		return $str !== null ? intval($str) : null;
	}

	private function getXmpFloatValue(string $xmlString, string $xmpKey): ?float {
		$str = $this->getXmpStringValue($xmlString, $xmpKey);

		return $str !== null ? floatval($str) : null;
	}
}
