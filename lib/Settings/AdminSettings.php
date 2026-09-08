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
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;
use OCP\Util;

/**
 * @psalm-api
 */
class AdminSettings implements ISettings {
	public function __construct(
		private PhotosphereConfig $photosphereConfig,
		private IInitialState $initialState,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState(
			'photospheres_enabled',
			$this->photosphereConfig->isPhotosphereSupportEnabled()
		);
		Util::addStyle(Application::APP_ID, 'viewer-settings');
		Util::addScript(Application::APP_ID, 'viewer-settings');

		return new TemplateResponse(Application::APP_ID, 'settings-admin');
	}

	#[\Override]
	public function getSection(): string {
		return 'additional';
	}

	#[\Override]
	public function getPriority(): int {
		return 70;
	}
}
