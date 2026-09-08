<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Controller;

use OCA\Viewer\AppInfo\Application;
use OCA\Viewer\Service\PhotosphereConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Admin-only controller to persist the app settings
 * (no NoAdminRequired attribute on purpose).
 *
 * @psalm-api
 */
class SettingsController extends Controller {
	public function __construct(
		IRequest $request,
		private PhotosphereConfig $photosphereConfig,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Enable or disable the photosphere (360° image) support
	 */
	public function setPhotosphereSupport(bool $enabled): JSONResponse {
		$this->photosphereConfig->setPhotosphereSupportEnabled($enabled);
		return new JSONResponse([
			'success' => true,
			'enabled' => $this->photosphereConfig->isPhotosphereSupportEnabled(),
		]);
	}
}
