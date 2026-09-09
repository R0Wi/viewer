<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Sabre;

use OCA\DAV\Connector\Sabre\File;
use OCA\Viewer\Model\PhotosphereMetadata;
use OCA\Viewer\Service\PhotosphereConfig;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;
use OCP\FilesMetadata\IFilesMetadataManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\IFile;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

/**
 * Serves the Google Photo Sphere XMP metadata of jpeg files as a WebDAV
 * property, so that the viewer can recognize photospheres (360° images)
 * without additional requests.
 *
 * The metadata itself is computed ahead of time by
 * {@see \OCA\Viewer\Listener\PhotosphereMetadataListener} — on upload/edit,
 * and through `occ files:scan --generate-metadata photospheres` for files
 * that already existed before this feature was enabled — and stored via
 * {@see IFilesMetadataManager}. This plugin only ever reads the
 * already-computed value: it never opens the file itself, so answering a
 * PROPFIND never costs file I/O, however many jpegs the folder contains.
 *
 * Ported from the files_photospheres app
 * (https://github.com/nextcloud/files_photospheres).
 *
 * @psalm-api
 */
class PhotospherePlugin extends ServerPlugin {
	// See src/utils/photosphereUtils.ts
	public const string PROPERTY_PHOTOSPHERE_METADATA = '{http://nextcloud.org/ns}viewer-photosphere-metadata';

	/**
	 * Files metadata key the photosphere metadata is stored under.
	 *
	 * @see \OCA\Viewer\Listener\PhotosphereMetadataListener
	 * @see \OCA\Viewer\Migration\Version9000Date20260909120000
	 */
	public const string METADATA_KEY = 'photosphere';

	public function __construct(
		private IFilesMetadataManager $filesMetadataManager,
		private PhotosphereConfig $photosphereConfig,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * @return void
	 */
	#[\Override]
	public function initialize(Server $server) {
		$server->on('propFind', [$this, 'handleGetProperties']);
		$this->logger->debug(get_class($this) . ' initialized');
	}

	/**
	 * Adds the photosphere XMP metadata to the properties of a file.
	 *
	 * @return void
	 */
	public function handleGetProperties(
		PropFind $propFind,
		INode $node,
	) {
		if (!($node instanceof IFile) || is_null($propFind->getStatus(self::PROPERTY_PHOTOSPHERE_METADATA))) {
			return;
		}

		if (!$this->photosphereConfig->isPhotosphereSupportEnabled()) {
			return;
		}

		$propFind->handle(self::PROPERTY_PHOTOSPHERE_METADATA, function () use ($node) {
			return $node instanceof File ? $this->getMetadata($node) : null;
		});
	}

	private function getMetadata(File $file): ?PhotosphereMetadata {
		$fileInfo = $file->getFileInfo();
		if ($fileInfo->getMimetype() !== 'image/jpeg') {
			return null;
		}

		$id = $file->getId();
		if ($id === null) {
			$this->logger->warning('File {file} has no id', ['file' => $file->getName()]);
			return null;
		}

		try {
			// Never generate: generating here would mean reading the file
			// during a PROPFIND again, exactly what storing the metadata
			// ahead of time is meant to avoid. A file with no metadata yet
			// (not uploaded/edited nor covered by a metadata-generating
			// rescan since this feature shipped) is simply treated as "not
			// a photosphere" until one of those happens.
			$metadata = $this->filesMetadataManager->getMetadata($id, false);
		} catch (FilesMetadataNotFoundException) {
			return null;
		}

		if (!$metadata->hasKey(self::METADATA_KEY)) {
			return null;
		}

		try {
			return PhotosphereMetadata::fromArray($metadata->getArray(self::METADATA_KEY));
		} catch (FilesMetadataTypeException $e) {
			$this->logger->warning('Malformed photosphere metadata for file {file}: {message}', [
				'file' => $file->getName(),
				'message' => $e->getMessage(),
				'exception' => $e,
			]);
			return null;
		}
	}
}
