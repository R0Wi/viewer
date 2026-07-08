<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Model;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Result of scanning a file for Google Photo Sphere XMP metadata
 * (see https://developers.google.com/streetview/spherical-metadata).
 *
 * Serialized as the WebDAV property
 * {http://nextcloud.org/ns}viewer-photosphere-metadata.
 *
 * @psalm-api
 */
class PhotosphereMetadata implements XmlSerializable {
	public bool $usePanoramaViewer = false;
	public bool $containsCroppingConfig = false;
	public PhotosphereCroppingConfig $croppingConfig;

	public function __construct() {
		$this->croppingConfig = new PhotosphereCroppingConfig();
	}

	/**
	 * @psalm-suppress PossiblyUnusedParam only called from event driven code
	 */
	public static function fromArray(array $data): self {
		$metadata = new self();
		$metadata->usePanoramaViewer = (bool)($data['usePanoramaViewer'] ?? false);
		$metadata->containsCroppingConfig = (bool)($data['containsCroppingConfig'] ?? false);
		$croppingConfig = $data['croppingConfig'] ?? [];
		$metadata->croppingConfig = PhotosphereCroppingConfig::fromArray(is_array($croppingConfig) ? $croppingConfig : []);
		return $metadata;
	}

	public function toArray(): array {
		return [
			'usePanoramaViewer' => $this->usePanoramaViewer,
			'containsCroppingConfig' => $this->containsCroppingConfig,
			'croppingConfig' => $this->croppingConfig->toArray(),
		];
	}

	/**
	 * @psalm-suppress PossiblyUnusedParam only called by the sabre serializer
	 */
	public function xmlSerialize(Writer $writer): void {
		// Serialize as JSON text content instead of XML sub-elements:
		// the WebDAV client reads element.textContent, which would
		// concatenate all child text nodes when using XML sub-elements.
		$writer->write(json_encode($this->toArray()));
	}
}
