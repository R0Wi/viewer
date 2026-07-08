/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import Photospheres from '../components/Photospheres.vue'
import { parsePhotosphereMetadata, isPhotosphere, isWebGl2Supported } from '../utils/photosphereUtils.ts'

/**
 * Whether the photosphere support is enabled by the administrator
 * (see lib/Service/PhotosphereConfig.php)
 */
export const enabled = loadState(appName, 'photospheres_enabled', true)

export default {
	id: 'photospheres',
	mimes: [
		'image/jpeg',
	],
	component: Photospheres,
	theme: 'dark',

	/**
	 * Claim only those image/jpeg files that actually are photospheres,
	 * based on the XMP metadata delivered by the backend (see
	 * lib/Sabre/PhotospherePlugin.php). If the browser can't render them
	 * (no WebGL2), the files are left to the regular image handler.
	 *
	 * @param {object} fileInfo the file info
	 * @return {boolean} true if the file should be opened as a photosphere
	 */
	canHandle(fileInfo) {
		const metadata = parsePhotosphereMetadata(fileInfo?.viewerPhotosphereMetadata)
		return isPhotosphere(metadata) && isWebGl2Supported()
	},
}
