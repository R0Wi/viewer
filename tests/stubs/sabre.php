<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Sabre\Xml {
	class Writer {
		/**
		 * @param mixed $value
		 */
		public function write($value): void {
		}
	}

	interface XmlSerializable {
		public function xmlSerialize(Writer $writer): void;
	}
}

namespace Sabre\DAV {
	class Server {
		public function on(string $eventName, callable $callBack, int $priority = 100): void {
		}
	}

	class ServerPlugin {
		/**
		 * @return void
		 */
		public function initialize(Server $server) {
		}
	}

	interface INode {
		/**
		 * @return string
		 */
		public function getName();
	}

	interface IFile extends INode {
	}

	interface ICollection extends INode {
		/**
		 * @return INode[]
		 */
		public function getChildren(): array;
	}

	class PropFind {
		public function getStatus(string $propertyName): ?int {
			return null;
		}

		public function getDepth(): int {
			return 0;
		}

		/**
		 * @param mixed $valueOrCallBack
		 */
		public function handle(string $propertyName, $valueOrCallBack): void {
		}
	}
}
