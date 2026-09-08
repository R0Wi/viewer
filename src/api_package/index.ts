/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AsyncComponent, Component } from 'vue'

export interface IHandler {
	/**
	 * Unique identifier for the handler
	 */
	id: string

	/**
	 * Indicate support for comparing two files
	 */
	canCompare?: boolean

	/**
	 * Vue 2 component to render the file.
	 */
	component: Component | AsyncComponent

	/**
	 * Group identifier to combine for navigating to the next/previous files
	 */
	group?: string

	/**
	 * List of mime types that are supported for opening
	 */
	mimes?: string[]

	/**
	 * Aliases for mime types, used to map different mime types to the same handler.
	 */
	mimesAliases?: Record<string, string>

	/**
	 * Viewer modal theme (one of 'dark', 'light', 'default')
	 */
	theme?: 'dark' | 'light' | 'default'

	/**
	 * Optional per-file matcher.
	 *
	 * When provided, the handler may claim individual files of the declared
	 * mime types based on the file info (e.g. dav properties), taking
	 * precedence over the handler the mime type is registered to. This allows
	 * apps to provide a specialised view for a subset of files sharing a
	 * generic mime type (e.g. 360° photospheres within image/jpeg).
	 *
	 * The callback must be synchronous and side-effect free: it is evaluated
	 * every time the viewer resolves the component of a file (including
	 * adjacent files of a slideshow).
	 *
	 * @param fileInfo - The file info of the file to check
	 * @return Whether this handler should be used to display the file
	 */
	canHandle?: (fileInfo: Record<string, unknown>) => boolean
}

/**
 * Register a new handler for the viewer.
 * This needs to be called before the viewer is initialized to ensure the handler is available.
 * So this should be called from an initialization script (`OCP\Util::addInitScript`).
 *
 * @param handler - The handler to register
 * @throws Error if the handler is invalid
 */
export function registerHandler(handler: IHandler): void {
	validateHandler(handler)

	window._oca_viewer_handlers ??= new Map<string, IHandler>()
	if (window._oca_viewer_handlers.has(handler.id)) {
		console.warn(`Handler with id ${handler.id} is already registered.`)
		return
	}

	window._oca_viewer_handlers.set(handler.id, handler)
}

/**
 * Validate the handler object.
 *
 * @param handler - The handler to validate
 */
function validateHandler(handler: IHandler) {
	const { id, mimes, mimesAliases, component, canHandle } = handler

	// checking valid handler id
	if (!id || id.trim() === '' || typeof id !== 'string') {
		throw new Error('The handler does not have a valid id')
	}

	// Nothing available to process! Failure
	if ((!mimes || !Array.isArray(mimes)) && !mimesAliases) {
		throw new Error('Handler needs a valid mime array or mimesAliases')
	}

	// checking valid handler component data
	if ((!component || (typeof component !== 'object' && typeof component !== 'function'))) {
		throw new Error('The handler does not have a valid component')
	}

	// checking valid per-file matcher, if any
	if (canHandle !== undefined && typeof canHandle !== 'function') {
		throw new Error('The handler canHandle property must be a function')
	}
}
