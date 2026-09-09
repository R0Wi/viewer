/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'
import Vue from 'vue'

import AdminSettings from './views/AdminSettings.vue'

Vue.mixin({ methods: { t } })

// Admin settings of the viewer app, rendered into the mount point of
// templates/settings-admin.php (see lib/Settings/AdminSettings.php)
export default new Vue({
	el: '#viewer-admin-settings',
	// eslint-disable-next-line vue/match-component-file-name
	name: 'AdminSettingsRoot',
	render: (h) => h(AdminSettings),
})
