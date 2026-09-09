<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\AppInfo;

use OCA\Viewer\Event\LoadViewer;
use OCA\Viewer\Listener\LoadViewerScript;
use OCA\Viewer\Listener\PhotosphereMetadataListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\FilesMetadata\Event\MetadataLiveEvent;
use OCP\FilesMetadata\Event\MetadataNamedEvent;

/**
 * @psalm-api
 */
class Application extends App implements IBootstrap {
	public const string APP_ID = 'viewer';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(LoadViewer::class, LoadViewerScript::class);

		// Compute photosphere metadata ahead of time on upload/edit, and
		// on `occ files:scan --generate-metadata photospheres` for
		// pre-existing files (see PhotosphereMetadataListener).
		$context->registerEventListener(MetadataLiveEvent::class, PhotosphereMetadataListener::class);
		$context->registerEventListener(MetadataNamedEvent::class, PhotosphereMetadataListener::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
	}
}
