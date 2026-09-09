/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { createRandomUser, expect, loginUser, test } from '../../support/fixtures.ts'
import { getRowForFile, navigateToFolder, openFile } from '../../support/filesUtils.ts'
import {
	expectViewerLoaded,
	getCloseButton,
	getViewer,
	getViewerName,
	goNext,
} from '../../support/viewerUtils.ts'
import { createFolder, uploadFile } from '../../support/webdav.ts'

// The fixtures live in a folder of their own so that the slideshow contains
// exactly these two files, whatever else the account starts out with.
const FOLDER = 'Panoramas'

/**
 * The panorama canvas of the slide currently shown.
 *
 * The viewer preloads the adjacent slides, so the neighbours of a photosphere
 * mount a container of their own — scope the assertion to the active slide.
 *
 * @param page the page
 */
const getActivePanorama = (page: Page) =>
	getViewer(page).locator('.photosphere_container.viewer__file--active canvas')

/**
 * Wait for the slideshow to show the given file, then read the id of the
 * handler that claimed it.
 *
 * @param page the page
 * @param basename the file expected to be shown
 */
async function handlerShowing(page: Page, basename: string): Promise<string | null> {
	await expect(getViewerName(page)).toContainText(basename)
	return await getViewer(page).getAttribute('data-handler')
}

test.describe.serial('Open photosphere.jpg (360° image) in viewer', () => {
	let page: Page

	test.beforeAll(async ({ browser }) => {
		page = await browser.newPage()
		const user = await createRandomUser()
		await createFolder(user, `/${FOLDER}`)
		await uploadFile(user, 'photosphere.jpg', 'image/jpeg', `/${FOLDER}/photosphere.jpg`)
		await uploadFile(user, 'image1.jpg', 'image/jpeg', `/${FOLDER}/image1.jpg`)
		await loginUser(page, user)
		await page.goto('apps/files')
		await navigateToFolder(page, FOLDER)
	})

	test.afterAll(async () => {
		await page.close()
	})

	test('See photosphere.jpg in the list', async () => {
		await expect(getRowForFile(page, 'photosphere.jpg')).toContainText('photosphere .jpg')
	})

	test('Open the viewer on file click', async () => {
		await openFile(page, 'photosphere.jpg')
		await expect(getViewer(page)).toBeVisible()
	})

	test('The photosphere handler claims the file', async () => {
		// The handler claims this jpeg through its canHandle() matcher, which
		// reads the XMP metadata served as a WebDAV property by PhotospherePlugin.
		await expect(getViewer(page)).toHaveAttribute('data-handler', 'photospheres')
	})

	test('The panorama is rendered', async () => {
		await expect(getActivePanorama(page)).toBeVisible()
	})

	test('Does not see a loading animation', async () => {
		await expectViewerLoaded(page)
	})

	test('Regular jpeg images are still displayed by the image handler', async () => {
		// image1.jpg is a plain jpeg: the matcher rejects it and the file falls
		// back to the handler image/jpeg is registered to.
		await goNext(page)
		expect(await handlerShowing(page, 'image1.jpg')).toBe('images')
		await expect(getActivePanorama(page)).toHaveCount(0)
		await expectViewerLoaded(page)
	})

	test('Navigating on switches back to the photosphere handler', async () => {
		await goNext(page)
		expect(await handlerShowing(page, 'photosphere.jpg')).toBe('photospheres')
		await expect(getActivePanorama(page)).toBeVisible()
	})

	test('Close the viewer', async () => {
		await getCloseButton(page).click()
		await expect(getViewer(page)).toHaveCount(0)
	})
})
