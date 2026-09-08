/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import PhotosphereVideos from '../components/PhotosphereVideos.vue'
import { PHOTOSPHERE_VIDEO_MIMES } from '../utils/videoMimes.ts'

export default {
	id: 'photosphere-videos',
	// The aliased mimes are listed directly: a matcher handler doesn't
	// own its mime types, so it has no aliases to remap (see Viewer.vue)
	mimes: PHOTOSPHERE_VIDEO_MIMES,
	component: PhotosphereVideos,
	theme: 'dark',

	/**
	 * 360° videos carry no reliable server-side detectable metadata, so
	 * they are never claimed automatically: this handler is only used
	 * explicitly through the "View in 360°" file action, which opens the
	 * viewer with `OCA.Viewer.openWith('photosphere-videos', ...)`
	 * (see src/files_actions/photosphereVideoAction.ts).
	 *
	 * @return {boolean} always false
	 */
	canHandle() {
		return false
	},
}
