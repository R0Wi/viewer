<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Service;

use OCA\Viewer\AppInfo\Application;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Access to the photosphere (360° image) support configuration.
 *
 * The support is optional: reading the XMP metadata of jpeg files causes
 * a little extra server load, so administrators can turn it off.
 *
 * @psalm-api
 */
class PhotosphereConfig {
	public const string CONFIG_KEY_PHOTOSPHERES_ENABLED = 'photospheres_enabled';
	public const bool PHOTOSPHERES_ENABLED_DEFAULT = true;

	public function __construct(
		private IAppConfig $appConfig,
		private LoggerInterface $logger,
	) {
	}

	public function isPhotosphereSupportEnabled(): bool {
		try {
			return $this->appConfig->getValueBool(
				Application::APP_ID,
				self::CONFIG_KEY_PHOTOSPHERES_ENABLED,
				self::PHOTOSPHERES_ENABLED_DEFAULT
			);
		} catch (AppConfigTypeConflictException $e) {
			// The value was written with a different type, e.g. through
			// `occ config:app:set … --type integer`. Don't let that take
			// the viewer down with it.
			$this->logger->warning('Ignoring malformed {key} app config value: {message}', [
				'key' => self::CONFIG_KEY_PHOTOSPHERES_ENABLED,
				'message' => $e->getMessage(),
				'exception' => $e,
			]);
			return self::PHOTOSPHERES_ENABLED_DEFAULT;
		}
	}

	public function setPhotosphereSupportEnabled(bool $enabled): void {
		$this->appConfig->setValueBool(
			Application::APP_ID,
			self::CONFIG_KEY_PHOTOSPHERES_ENABLED,
			$enabled
		);
	}
}
