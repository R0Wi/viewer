/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'

/**
 * Whether photosphere support is enabled by the administrator
 * (see lib/Service/PhotosphereConfig.php). Read once: the value only
 * changes with a page reload.
 */
export const photospheresEnabled: boolean = loadState(appName, 'photospheres_enabled', true)

/**
 * Google Photo Sphere XMP metadata of a file, delivered by the backend
 * via the WebDAV property {http://nextcloud.org/ns}viewer-photosphere-metadata
 * (see lib/Sabre/PhotospherePlugin.php).
 */
export interface PhotosphereMetadata {
	usePanoramaViewer?: boolean
	containsCroppingConfig?: boolean
	croppingConfig?: Record<string, number|null>
}

/**
 * Parse the raw photosphere metadata value as delivered by the backend.
 * The WebDAV property is serialized as a JSON string, but depending on
 * the consumer it might already be an object.
 *
 * @param rawValue the raw property value
 * @return the parsed metadata or null
 */
export function parsePhotosphereMetadata(rawValue: unknown): PhotosphereMetadata|null {
	if (typeof rawValue === 'string') {
		try {
			return JSON.parse(rawValue)
		} catch (error) {
			console.warn('viewer: failed to parse photosphere metadata JSON', { rawValue, error })
			return null
		}
	}
	if (rawValue && typeof rawValue === 'object') {
		return rawValue as PhotosphereMetadata
	}
	return null
}

/**
 * Check if the given (parsed) metadata describes a photosphere
 *
 * @param metadata the parsed photosphere metadata
 * @return true if the file is a photosphere
 */
export function isPhotosphere(metadata: PhotosphereMetadata|null): boolean {
	return metadata?.usePanoramaViewer === true
}

let webGl2Supported: boolean|null = null

/**
 * Check (once) if the browser supports WebGL2, which is required
 * to render photospheres
 *
 * @return true if WebGL2 is supported
 */
export function isWebGl2Supported(): boolean {
	if (webGl2Supported === null) {
		try {
			const canvas = document.createElement('canvas')
			const context = window.WebGL2RenderingContext
				? canvas.getContext('webgl2')
				: null
			webGl2Supported = context !== null
			// Browsers only allow a handful of live contexts, so give this
			// probe's context back instead of holding it for the session
			context?.getExtension('WEBGL_lose_context')?.loseContext()
		} catch (error) {
			webGl2Supported = false
		}
	}
	return webGl2Supported
}
