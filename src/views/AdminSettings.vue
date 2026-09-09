<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection :name="t('viewer', 'Viewer')"
		:description="t('viewer', 'Photo spheres are recognized by reading the XMP metadata of jpeg files on the server, which causes a little extra load. If this option is disabled, photo spheres are displayed like regular images.')">
		<NcCheckboxRadioSwitch type="switch"
			:checked="enabled"
			:disabled="saving"
			@update:checked="save">
			{{ t('viewer', 'Show photo spheres (360° images) in an interactive panorama view') }}
		</NcCheckboxRadioSwitch>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

import logger from '../services/logger.js'

export default {
	name: 'AdminSettings',

	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
	},

	data() {
		return {
			enabled: loadState(appName, 'photospheres_enabled', true),
			saving: false,
		}
	},

	methods: {
		/**
		 * Persist the photosphere support setting
		 *
		 * @param {boolean} enabled the new state
		 */
		async save(enabled) {
			const previous = this.enabled
			this.enabled = enabled
			this.saving = true
			try {
				await axios.put(generateUrl('/apps/viewer/settings/photospheres'), { enabled })
			} catch (error) {
				logger.error('Could not save the viewer admin settings', { error })
				showError(t('viewer', 'Could not save setting'))
				this.enabled = previous
			} finally {
				this.saving = false
			}
		},
	},
}
</script>
