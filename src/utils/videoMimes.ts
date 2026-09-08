/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Video mime types the viewer can display.
 *
 * Kept free of component imports so that it can also be used from the
 * init bundle (see src/files_actions/photosphereVideoAction.ts).
 */
export const VIDEO_MIMES = [
	'video/mpeg',
	'video/ogg',
	'video/webm',
	'video/mp4',
	'video/x-m4v',
	'video/x-flv',
	'video/quicktime',
]

/** Mime types displayed by reusing the handling of another mime type */
export const VIDEO_MIMES_ALIASES: Record<string, string> = {
	'video/x-matroska': 'video/webm',
}

/**
 * Mime types that can be opened through the "View in 360°" action.
 *
 * 360° videos are rendered from a plain <video> element by the
 * equirectangular video adapter, so every format the regular video
 * handler plays can be shown as a panorama as well.
 */
export const PHOTOSPHERE_VIDEO_MIMES = [
	...VIDEO_MIMES,
	...Object.keys(VIDEO_MIMES_ALIASES),
]
