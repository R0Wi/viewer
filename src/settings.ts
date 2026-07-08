/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

import logger from './services/logger.js'

/**
 * Admin settings for the viewer app (see templates/settings-admin.php
 * and lib/Settings/AdminSettings.php)
 */
document.addEventListener('DOMContentLoaded', () => {
	const checkbox = document.getElementById('viewer-photospheres-enabled') as HTMLInputElement|null
	if (!checkbox) {
		return
	}

	checkbox.addEventListener('change', async () => {
		const enabled = checkbox.checked
		checkbox.disabled = true
		try {
			await axios.put(
				generateUrl('/apps/viewer/settings/photospheres'),
				{ enabled },
			)
			showSuccess(t('viewer', 'Setting saved'))
		} catch (error) {
			logger.error('Could not save the admin settings', { error })
			// Restore the previous state
			checkbox.checked = !enabled
			showError(t('viewer', 'Could not save setting'))
		} finally {
			checkbox.disabled = false
		}
	})
})
