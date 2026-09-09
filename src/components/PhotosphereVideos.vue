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
			// Guards against initializing twice: once from mounted() (when
			// the slide starts out active) and once more from the active
			// watcher below (when it becomes active later).
			initialized: false,
		}
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
				this.photosphereViewer.addEventListener('panorama-error', (event) => {
					logger.error('Failed to load the 360° video panorama', { event })
					this.$emit('error', event)
				}, { once: true })
			} catch (error) {
				logger.error('Failed to initialize the 360° video viewer', { error })
				this.$emit('error', error)
			}
		},
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
