<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Migration;

use Closure;
use OCA\Viewer\Sabre\PhotospherePlugin;
use OCP\DB\ISchemaWrapper;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Registers the 'photosphere' files metadata key.
 *
 * Per {@see IFilesMetadataManager::initMetadata}, this call is mandatory
 * before the key can be used in a WebDAV request, and must happen in a
 * migration/repair step rather than on every app boot, since it reads the
 * (lazy) app config.
 *
 * @psalm-api
 */
class Version9000Date20260909120000 extends SimpleMigrationStep {
	public function __construct(
		private IFilesMetadataManager $filesMetadataManager,
	) {
	}

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array{tablePrefix?: string} $options
	 */
	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		$this->filesMetadataManager->initMetadata(
			PhotospherePlugin::METADATA_KEY,
			IMetadataValueWrapper::TYPE_ARRAY,
			false,
			IMetadataValueWrapper::EDIT_FORBIDDEN,
		);
	}
}
