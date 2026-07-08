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

	async mounted() {
		// Dragging the panorama conflicts with the modal swipe gestures
		this.disableSwipe()

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
		} catch (error) {
			logger.error('Failed to initialize the photosphere viewer', { error })
			this.$emit('error', error)
		}
	},

	beforeDestroy() {
		this.isDestroyed = true
		if (this.photosphereViewer) {
			this.photosphereViewer.destroy()
			this.photosphereViewer = null
		}
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
