/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode, IView } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'

import logger from '../services/logger.js'

/**
 * Push the given file into the Files app history/URL, so that browser
 * back/forward navigation and reloading the page keep working while a
 * file is open in the viewer.
 *
 * @param node The file to open
 * @param view The files view
 * @param dir the directory path
 */
export function pushToHistory(node: INode, view: IView, dir: string) {
	if (!window.OCP?.Files?.Router) {
		// No router, we're in standalone mode
		logger.debug('No router found, skipping history push')
		return
	}

	const editing = window.OCP.Files.Router.query.editing === 'true' ? 'true' : 'false'
	window.OCP.Files.Router.goToRoute(
		null,
		{ ...window.OCP.Files.Router.params, view: view.id, fileid: String(node.fileid) },
		{ ...window.OCP.Files.Router.query, dir, openfile: 'true', editing },
		true,
	)
}

/**
 * Closes the viewer when browser back/forward navigation leaves the
 * "openfile" state that {@see pushToHistory} pushed.
 */
function onPopState() {
	emit('editor:toggle', window.OCP?.Files?.Router?.query?.editing === 'true')
	if (window.OCP?.Files?.Router?.query?.openfile !== 'true') {
		window.OCA.Viewer.close()
		window.removeEventListener('popstate', onPopState)
	}
}

/**
 * Start pushing history entries for the given file/view/directory, so
 * that opening a file, navigating (previous/next) and closing the viewer
 * are reflected in the URL and survive a reload.
 *
 * Shared by every file action that opens the viewer ({@see registerViewerAction},
 * {@see registerPhotosphereVideoAction}), so that a file opened through any
 * of them behaves the same way with respect to browser history.
 *
 * @param node the initially opened file
 * @param view the files view
 * @param dir the directory path
 * @return the `onClose` callback to pass to `OCA.Viewer.open()`/`openWith()`
 */
export function startHistoryTracking(node: INode, view: IView, dir: string): () => void {
	if (window.OCP?.Files?.Router) {
		window.addEventListener('popstate', onPopState)
	}

	pushToHistory(node, view, dir)

	return () => {
		// If there is no router, we're in standalone mode
		if (!window.OCP?.Files?.Router) {
			return
		}

		// This can sometime be called with the openfile set to true already. But we don't want to keep openfile when closing the viewer.
		const newQuery = { ...window.OCP?.Files?.Router?.query }
		delete newQuery.openfile
		delete newQuery.editing
		window.OCP?.Files?.Router?.goToRoute(null, window.OCP?.Files?.Router?.params, newQuery)
	}
}
