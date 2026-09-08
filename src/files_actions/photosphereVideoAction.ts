/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Permission, registerFileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import svgRotate3d from '@mdi/svg/svg/rotate-3d.svg?raw'

import { isWebGl2Supported } from '../utils/photosphereUtils.ts'
import { PHOTOSPHERE_VIDEO_MIMES } from '../utils/videoMimes.ts'

/**
 * Register the "View in 360°" action on the files API.
 *
 * 360° videos carry no reliable server-side detectable metadata, so the
 * user explicitly chooses to open a video in the 360° view through this
 * context menu entry (see src/models/photosphereVideos.js).
 */
export function registerPhotosphereVideoAction() {
	registerFileAction({
		id: 'view-photosphere-video',
		displayName: () => t('viewer', 'View in 360°'),
		iconSvgInline: () => svgRotate3d,
		order: 1000,
		enabled: ({ nodes }) => {
			// Disable if not located in user root
			if (nodes.some(node => !(node.isDavResource && node.root?.startsWith('/files')))) {
				return false
			}

			return isWebGl2Supported()
				&& nodes.every((node) =>
					Boolean(node.permissions & Permission.READ)
					&& PHOTOSPHERE_VIDEO_MIMES.includes(node.mime ?? ''),
				)
		},
		async exec({ nodes }) {
			window.OCA.Viewer.openWith('photosphere-videos', {
				path: nodes[0].path,
			})
			return null
		},
	})
}
