<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

class File implements \Sabre\DAV\IFile {
	public function getId(): ?int {
		return null;
	}

	public function getName(): string {
		return '';
	}

	public function getFileInfo(): \OCP\Files\FileInfo {
		throw new \Exception('stub');
	}

	public function getNode(): \OCP\Files\File {
		throw new \Exception('stub');
	}
}
