<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Settings;

use OCA\Viewer\AppInfo\Application;
use OCA\Viewer\Service\PhotosphereConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\Util;

/**
 * @psalm-api
 */
class AdminSettings implements ISettings {
	public function __construct(
		private PhotosphereConfig $photosphereConfig,
	) {
	}

	public function getForm(): TemplateResponse {
		Util::addScript(Application::APP_ID, 'viewer-settings');

		return new TemplateResponse(Application::APP_ID, 'settings-admin', [
			'photospheresEnabled' => $this->photosphereConfig->isPhotosphereSupportEnabled(),
		]);
	}

	public function getSection(): string {
		return 'additional';
	}

	public function getPriority(): int {
		return 70;
	}
}
