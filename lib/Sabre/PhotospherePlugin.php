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
use OCA\Viewer\Service\PhotosphereMetadataReader;
use OCP\ICache;
use OCP\ICacheFactory;
use Psr\Log\LoggerInterface;
use Sabre\DAV\ICollection;
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
 * Ported from the files_photospheres app
 * (https://github.com/nextcloud/files_photospheres).
 *
 * @psalm-api
 */
class PhotospherePlugin extends ServerPlugin {
	// See src/utils/photosphereUtils.ts
	public const PROPERTY_PHOTOSPHERE_METADATA = '{http://nextcloud.org/ns}viewer-photosphere-metadata';

	private ICache $cache;

	public function __construct(
		private PhotosphereMetadataReader $metadataReader,
		private PhotosphereConfig $photosphereConfig,
		ICacheFactory $cacheFactory,
		private LoggerInterface $logger,
	) {
		$this->cache = $cacheFactory->createLocal(get_class($this));
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * @return void
	 */
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
		if ((!($node instanceof IFile) && !($node instanceof ICollection))
			|| is_null($propFind->getStatus(self::PROPERTY_PHOTOSPHERE_METADATA))) {
			return;
		}

		if (!$this->photosphereConfig->isPhotosphereSupportEnabled()) {
			return;
		}

		// We try to create a cache for the whole directory
		// so that individual file XMP metadata requests are faster
		if (($node instanceof ICollection) && $propFind->getDepth() !== 0) {
			$this->cacheDirectory($node);
		}

		$propFind->handle(self::PROPERTY_PHOTOSPHERE_METADATA, function () use ($node) {
			return $node instanceof File ? $this->getMetadataCached($node) : null;
		});
	}

	private function cacheDirectory(ICollection $directory): void {
		$this->logger->debug('Start caching directory {dir}', ['dir' => $directory->getName()]);
		$start = hrtime(true);

		$children = $directory->getChildren();

		foreach ($children as $child) {
			if ($child instanceof File) {
				$this->getMetadataCached($child);
			}
		}

		$elapsedMs = (float)(hrtime(true) - $start) / 1e+6;
		$this->logger->debug('Caching directory {dir} done. It took {ms}ms', ['dir' => $directory->getName(), 'ms' => $elapsedMs]);
	}

	private function getMetadataCached(File $file): ?PhotosphereMetadata {
		$id = $file->getId();

		if ($id === null) {
			$this->logger->warning('File {file} has no id', ['file' => $file->getName()]);
			return null;
		}

		if ($file->getFileInfo()->getMimetype() !== 'image/jpeg') {
			return null;
		}

		/** @var PhotosphereMetadata|array|null $cachedMetadata */
		$cachedMetadata = $this->cache->get((string)$id);

		if ($cachedMetadata instanceof PhotosphereMetadata) {
			$this->logger->debug('Cache hit for file {file}', ['file' => $file->getName()]);
			return $cachedMetadata;
		}
		if (is_array($cachedMetadata)) {
			$this->logger->debug('Cache hit for file {file}', ['file' => $file->getName()]);
			return PhotosphereMetadata::fromArray($cachedMetadata);
		}

		$this->logger->debug('Cache miss for file {file}', ['file' => $file->getName()]);
		try {
			$metadata = $this->metadataReader->fromFile($file->getNode());
		} catch (\Exception $e) {
			$this->logger->warning('Could not read photosphere metadata of file {file}: {message}', [
				'file' => $file->getName(),
				'message' => $e->getMessage(),
				'exception' => $e,
			]);
			return null;
		}
		$this->cache->set((string)$id, $metadata);

		return $metadata;
	}
}
