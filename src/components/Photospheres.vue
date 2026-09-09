<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="photosphere_container" />
</template>

<script>
import logger from '../services/logger.js'
import { parsePhotosphereMetadata } from '../utils/photosphereUtils.ts'

export default {
	name: 'Photospheres',

	data() {
		return {
			photosphereViewer: null,
			isDestroyed: false,
			// Guards against initializing twice: once from mounted() (when
			// the slide starts out active) and once more from the active
			// watcher below (when it becomes active later).
			initialized: false,
		}
	},

	computed: {
		/**
		 * The photosphere XMP metadata of the current file, delivered by
		 * the backend via the viewer-photosphere-metadata WebDAV property
		 * and passed along as part of the file info.
		 *
		 * @return {?import('../utils/photosphereUtils.ts').PhotosphereMetadata}
		 */
		photosphereMetadata() {
			return parsePhotosphereMetadata(this.$attrs.viewerPhotosphereMetadata)
		},
	},

	watch: {
		active(isActive) {
			if (isActive) {
				// Dragging the panorama conflicts with the modal swipe
				// gestures. The viewer also preloads the previous/next
				// slide, so this instance might become active only after
				// mounted() already ran once.
				this.disableSwipe()
				this.initPhotosphere()
			} else {
				this.enableSwipe()
			}
		},
	},

	mounted() {
		if (this.active) {
			this.disableSwipe()
			this.initPhotosphere()
		}
	},

	beforeDestroy() {
		this.isDestroyed = true
		// Restore swipe unconditionally: harmless if this instance never
		// disabled it (e.g. it was only ever preloaded, not active), and
		// otherwise guarantees it's not left disabled behind us.
		this.enableSwipe()
		if (this.photosphereViewer) {
			this.photosphereViewer.destroy()
			this.photosphereViewer = null
		}
	},

	methods: {
		async initPhotosphere() {
			if (this.initialized) {
				return
			}
			this.initialized = true

			try {
				// The photo-sphere-viewer library is loaded lazily: it is
				// only needed when a photosphere is actually displayed
				const [{ Viewer }, { AutorotatePlugin }, { GyroscopePlugin }, { StereoPlugin }] = await Promise.all([
					import('@photo-sphere-viewer/core'),
					import('@photo-sphere-viewer/autorotate-plugin'),
					import('@photo-sphere-viewer/gyroscope-plugin'),
					import('@photo-sphere-viewer/stereo-plugin'),
				])

				// The component might already have been destroyed while the
				// library was loading (e.g. fast navigation to the next file)
				if (this.isDestroyed) {
					return
				}

				/** @type {import('@photo-sphere-viewer/core').ViewerConfig} */
				const config = {
					container: this.$el,
					panorama: this.src,
					caption: this.basename,
					withCredentials: true,
					plugins: [
						GyroscopePlugin,
						StereoPlugin,
						AutorotatePlugin.withConfig({
							autostartOnIdle: false,
							autostartDelay: null,
						}),
					],
					// No 'download' button: downloading is provided by the
					// viewer toolbar (which respects share permissions)
					navbar: [
						'autorotate',
						'zoom',
						'move',
						'description',
						'caption',
						'fullscreen',
						'stereo',
						'gyroscope',
					],
				}

				// Add cropping information (if any) so that partial
				// panoramas are rendered correctly
				if (this.photosphereMetadata?.containsCroppingConfig) {
					config.panoData = this.photosphereMetadata.croppingConfig
				}

				this.photosphereViewer = new Viewer(config)
				this.photosphereViewer.addEventListener('ready', () => {
					this.doneLoading()
				}, { once: true })
				this.photosphereViewer.addEventListener('panorama-error', (event) => {
					logger.error('Failed to load the photosphere panorama', { event })
					this.$emit('error', event)
				}, { once: true })
			} catch (error) {
				logger.error('Failed to initialize the photosphere viewer', { error })
				this.$emit('error', error)
			}
		},
	},
}
</script>

<style>
@import '@photo-sphere-viewer/core/index.css';
</style>

<style scoped lang="scss">
.photosphere_container {
	width: 100%;
	height: 100%;
}
</style>
