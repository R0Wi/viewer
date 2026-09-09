/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Photospheres from '../components/Photospheres.vue'
import { parsePhotosphereMetadata, isPhotosphere, isWebGl2Supported } from '../utils/photosphereUtils.ts'

/**
 * Memoizes the parsed metadata per fileInfo object. canHandle() is
 * evaluated for up to three files (previous/current/next) on every
 * navigation step, and JSON.parse()-ing the same string repeatedly on
 * every one of those calls is wasted work.
 *
 * A WeakMap is used so entries are released once a fileInfo object is no
 * longer referenced elsewhere, rather than growing unbounded across a long
 * slideshow session.
 */
const metadataCache = new WeakMap()

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
		if (!fileInfo || typeof fileInfo !== 'object') {
			return false
		}

		let metadata
		if (metadataCache.has(fileInfo)) {
			metadata = metadataCache.get(fileInfo)
		} else {
			metadata = parsePhotosphereMetadata(fileInfo.viewerPhotosphereMetadata)
			metadataCache.set(fileInfo, metadata)
		}

		return isPhotosphere(metadata) && isWebGl2Supported()
	},
}
