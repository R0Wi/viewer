<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Listener;

use OCA\Viewer\Sabre\PhotospherePlugin;
use OCA\Viewer\Service\PhotosphereConfig;
use OCA\Viewer\Service\PhotosphereMetadataReader;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;
use OCP\FilesMetadata\AMetadataEvent;
use OCP\FilesMetadata\Event\MetadataLiveEvent;
use OCP\FilesMetadata\Event\MetadataNamedEvent;
use Psr\Log\LoggerInterface;

/**
 * Computes the Google Photo Sphere XMP metadata of jpeg files ahead of
 * time and stores it via the files metadata API, so that
 * {@see PhotospherePlugin} can serve it from every PROPFIND without ever
 * reading the file itself.
 *
 * This covers two paths:
 * - {@see MetadataLiveEvent}: dispatched whenever a file is created or
 *   updated — regular uploads and edits, and files newly discovered by a
 *   plain `occ files:scan`.
 * - {@see MetadataNamedEvent}: dispatched for
 *   `occ files:scan --generate-metadata photospheres`, so an administrator
 *   can backfill metadata for files that already existed before this
 *   feature was enabled (or before the app was installed) without having
 *   to wait for them to be re-uploaded or edited.
 *
 * The computation itself (reading at most 800kb of the file, see
 * {@see PhotosphereMetadataReader}) is cheap enough to run inline in both
 * cases; there's no need to defer it to a background job via
 * {@see MetadataLiveEvent::requestBackgroundJob()}.
 *
 * @template-implements IEventListener<Event>
 * @psalm-api
 */
class PhotosphereMetadataListener implements IEventListener {
	/**
	 * Name passed to `occ files:scan --generate-metadata <name>` to
	 * (re-)generate photosphere metadata for existing files.
	 */
	public const string GENERATE_METADATA_NAME = 'photospheres';

	public function __construct(
		private PhotosphereMetadataReader $metadataReader,
		private PhotosphereConfig $photosphereConfig,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof MetadataLiveEvent) {
			$this->process($event);
			return;
		}

		if ($event instanceof MetadataNamedEvent && $event->getName() === self::GENERATE_METADATA_NAME) {
			$this->process($event);
			return;
		}
	}

	private function process(AMetadataEvent $event): void {
		if (!$this->photosphereConfig->isPhotosphereSupportEnabled()) {
			return;
		}

		$node = $event->getNode();
		if (!($node instanceof File) || $node->getMimetype() !== 'image/jpeg') {
			return;
		}

		try {
			$metadata = $this->metadataReader->fromFile($node);
		} catch (\Exception $e) {
			$this->logger->warning('Could not read photosphere metadata of file {file}: {message}', [
				'file' => $node->getName(),
				'message' => $e->getMessage(),
				'exception' => $e,
			]);
			return;
		}

		if (!$metadata->usePanoramaViewer) {
			// Nothing to store: a missing metadata key is already treated
			// as "not a photosphere" by both PhotospherePlugin and the
			// frontend, and this keeps the metadata table free of an entry
			// for every plain jpeg. unset() is a no-op if the file was
			// never a photosphere to begin with, and clears a stale
			// "is a photosphere" entry if it was edited to no longer be one.
			$event->getMetadata()->unset(PhotospherePlugin::METADATA_KEY);
			return;
		}

		$event->getMetadata()->setArray(PhotospherePlugin::METADATA_KEY, $metadata->toArray());
	}
}
