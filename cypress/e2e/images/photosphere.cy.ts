/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('Open photosphere.jpg (360° image) in viewer', function() {
	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.uploadFile(user, 'photosphere.jpg', 'image/jpeg')
			cy.uploadFile(user, 'image1.jpg', 'image/jpeg')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('See photosphere.jpg in the list', function() {
		cy.getFile('photosphere.jpg', { timeout: 10000 })
			.should('contain', 'photosphere .jpg')
	})

	it('Open the viewer on file click and render the photosphere', function() {
		cy.openFile('photosphere.jpg')
		cy.get('body > .viewer').should('be.visible')

		// The photosphere handler claims the file through its
		// canHandle() matcher (recognized via the XMP metadata
		// served as WebDAV property by the PhotospherePlugin)
		cy.get('body > .viewer', { timeout: 10000 })
			.should('have.attr', 'data-handler', 'photospheres')

		// photo-sphere-viewer renders into a canvas
		cy.get('body > .viewer .photosphere_container canvas', { timeout: 10000 })
			.should('be.visible')

		// Make sure loading is finished
		cy.get('body > .viewer', { timeout: 10000 })
			.should('not.have.class', 'icon-loading')
	})

	it('Regular jpeg images are still displayed by the image handler', function() {
		cy.get('body > .viewer button.header-close').click()

		cy.openFile('image1.jpg')
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.attr', 'data-handler', 'images')
	})
})
