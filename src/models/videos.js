/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Videos from '../components/Videos.vue'
import { VIDEO_MIMES, VIDEO_MIMES_ALIASES } from '../utils/videoMimes.ts'

export default {
	id: 'videos',
	group: 'media',
	mimes: VIDEO_MIMES,
	mimesAliases: VIDEO_MIMES_ALIASES,
	component: Videos,
}
