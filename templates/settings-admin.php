<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Admin settings template. Rendered via OCA\Viewer\Settings\AdminSettings.
 *
 * @var array $_
 * @var \OCP\IL10N $l
 */

?>
<div id="viewer-admin-settings" class="section">
	<h2><?php p($l->t('Viewer')); ?></h2>
	<p>
		<input type="checkbox"
			id="viewer-photospheres-enabled"
			class="checkbox"
			<?php if ($_['photospheresEnabled']) {
				p('checked');
			} ?> />
		<label for="viewer-photospheres-enabled">
			<?php p($l->t('Show photo spheres (360° images) in an interactive panorama view')); ?>
		</label>
		<br />
		<em>
			<?php p($l->t('Photo spheres are recognized by reading the XMP metadata of jpeg files on the server, which causes a little extra load. If this option is disabled, photo spheres are displayed like regular images.')); ?>
		</em>
	</p>
</div>
