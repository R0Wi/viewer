<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="photosphere_container" />
</template>

<script>
import logger from '../services/logger.js'

export default {
	name: 'PhotosphereVideos',

	data() {
		return {
			photosphereViewer: null,
			isDestroyed: false,
		}
	},

	async mounted() {
		// Dragging the panorama conflicts with the modal swipe gestures
		this.disableSwipe()

		try {
			// The photo-sphere-viewer library is loaded lazily: it is
			// only needed when a 360° video is actually displayed
			const [{ Viewer }, { EquirectangularVideoAdapter }, { VideoPlugin }] = await Promise.all([
				import('@photo-sphere-viewer/core'),
				import('@photo-sphere-viewer/equirectangular-video-adapter'),
				import('@photo-sphere-viewer/video-plugin'),
			])

			// The component might already have been destroyed while the
			// library was loading (e.g. fast navigation to the next file)
			if (this.isDestroyed) {
				return
			}

			this.photosphereViewer = new Viewer({
				container: this.$el,
				adapter: [EquirectangularVideoAdapter, {
					muted: false,
				}],
				panorama: {
					source: this.src,
				},
				caption: this.basename,
				withCredentials: true,
				plugins: [
					[VideoPlugin, {
						progressbar: true,
						bigbutton: true,
					}],
				],
				navbar: [
					'video',
					'zoom',
					'move',
					'caption',
					'fullscreen',
				],
			})
			this.photosphereViewer.addEventListener('ready', () => {
				this.doneLoading()
			}, { once: true })
		} catch (error) {
			logger.error('Failed to initialize the 360° video viewer', { error })
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
@import '@photo-sphere-viewer/video-plugin/index.css';
</style>

<style scoped lang="scss">
.photosphere_container {
	width: 100%;
	height: 100%;
}
</style>
