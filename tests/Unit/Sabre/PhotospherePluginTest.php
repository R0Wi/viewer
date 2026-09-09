<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Tests\Unit\Sabre;

use OCA\DAV\Connector\Sabre\File;
use OCA\Viewer\Model\PhotosphereMetadata;
use OCA\Viewer\Sabre\PhotospherePlugin;
use OCA\Viewer\Service\PhotosphereConfig;
use OCP\Files\FileInfo;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\Model\IFilesMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sabre\DAV\ICollection;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;

/**
 * Unlike the pre-rewrite plugin, there is no directory pre-scan and no
 * ad-hoc caching left to test here: the plugin is a thin, read-only lookup
 * against IFilesMetadataManager. See PhotosphereMetadataListenerTest for
 * the code that actually computes and stores the metadata.
 */
class PhotospherePluginTest extends TestCase {
	private const META_PROP = '{http://nextcloud.org/ns}viewer-photosphere-metadata';

	private IFilesMetadataManager&MockObject $filesMetadataManager;
	private PhotosphereConfig&MockObject $photosphereConfig;
	private PhotospherePlugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->filesMetadataManager = $this->createMock(IFilesMetadataManager::class);
		$this->photosphereConfig = $this->createMock(PhotosphereConfig::class);
		$this->photosphereConfig->method('isPhotosphereSupportEnabled')->willReturn(true);
		$this->plugin = new PhotospherePlugin(
			$this->filesMetadataManager,
			$this->photosphereConfig,
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testInitializeRegistersThePropFindHook(): void {
		$server = $this->createMock(Server::class);
		$server->expects($this->once())
			->method('on')
			->with('propFind', [$this->plugin, 'handleGetProperties']);

		$this->plugin->initialize($server);
	}

	public function testDoesNothingForNonFileNodes(): void {
		$propFind = $this->createMock(PropFind::class);
		$propFind->expects($this->never())->method('getStatus');
		$propFind->expects($this->never())->method('handle');

		$this->plugin->handleGetProperties($propFind, $this->createMock(ICollection::class));
	}

	public function testDoesNothingWhenPropertyNotRequested(): void {
		$propFind = $this->createMock(PropFind::class);
		$propFind->method('getStatus')->with(self::META_PROP)->willReturn(null);
		$propFind->expects($this->never())->method('handle');

		$this->plugin->handleGetProperties($propFind, $this->createMock(File::class));
	}

	public function testDoesNothingWhenFeatureDisabled(): void {
		$this->photosphereConfig = $this->createMock(PhotosphereConfig::class);
		$this->photosphereConfig->method('isPhotosphereSupportEnabled')->willReturn(false);
		$plugin = new PhotospherePlugin($this->filesMetadataManager, $this->photosphereConfig, $this->createMock(LoggerInterface::class));

		$propFind = $this->createMock(PropFind::class);
		$propFind->method('getStatus')->willReturn(200);
		$propFind->expects($this->never())->method('handle');

		$plugin->handleGetProperties($propFind, $this->createMock(File::class));
	}

	public function testReturnsNullForNonJpegFiles(): void {
		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->method('getMimetype')->willReturn('application/pdf');
		$node = $this->createMock(File::class);
		$node->method('getFileInfo')->willReturn($fileInfo);
		$handler = $this->registerAndCapture($node);

		$this->filesMetadataManager->expects($this->never())->method('getMetadata');
		$this->assertNull($handler());
	}

	public function testLogsAndReturnsNullWhenFileHasNoId(): void {
		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->method('getMimetype')->willReturn('image/jpeg');
		$node = $this->createMock(File::class);
		$node->method('getFileInfo')->willReturn($fileInfo);
		$node->method('getId')->willReturn(null);
		$node->method('getName')->willReturn('myfile.jpg');
		$handler = $this->registerAndCapture($node);

		$this->filesMetadataManager->expects($this->never())->method('getMetadata');
		$this->assertNull($handler());
	}

	public function testReturnsNullWhenNoMetadataStoredYet(): void {
		$node = $this->jpegNode(42);
		$handler = $this->registerAndCapture($node);

		$this->filesMetadataManager->method('getMetadata')
			->with(42, false)
			->willThrowException(new FilesMetadataNotFoundException());

		$this->assertNull($handler());
	}

	public function testReturnsNullWhenMetadataStoredButKeyMissing(): void {
		$node = $this->jpegNode(42);
		$handler = $this->registerAndCapture($node);

		$storedMetadata = $this->createMock(IFilesMetadata::class);
		$storedMetadata->method('hasKey')->with(PhotospherePlugin::METADATA_KEY)->willReturn(false);
		$this->filesMetadataManager->method('getMetadata')->willReturn($storedMetadata);

		$this->assertNull($handler());
	}

	public function testReturnsStoredMetadataForAPhotosphere(): void {
		$node = $this->jpegNode(42);
		$handler = $this->registerAndCapture($node);

		$storedMetadata = $this->createMock(IFilesMetadata::class);
		$storedMetadata->method('hasKey')->with(PhotospherePlugin::METADATA_KEY)->willReturn(true);
		$storedMetadata->method('getArray')->with(PhotospherePlugin::METADATA_KEY)->willReturn([
			'usePanoramaViewer' => true,
			'containsCroppingConfig' => false,
			'croppingConfig' => [],
		]);
		$this->filesMetadataManager->method('getMetadata')->willReturn($storedMetadata);

		$result = $handler();
		$this->assertInstanceOf(PhotosphereMetadata::class, $result);
		$this->assertTrue($result->usePanoramaViewer);
	}

	/**
	 * Registers the plugin for the given node and returns the (niladic)
	 * closure it passed to PropFind::handle(), for the caller to invoke
	 * directly. The closure captures $node by reference at registration
	 * time (see PhotospherePlugin::handleGetProperties), so the node must
	 * be fully configured before calling this.
	 */
	private function registerAndCapture(File&MockObject $node): callable {
		$propFind = $this->createMock(PropFind::class);
		$propFind->method('getStatus')->with(self::META_PROP)->willReturn(200);
		$handler = null;
		$propFind->expects($this->once())
			->method('handle')
			->with(self::META_PROP, $this->callback(function ($callback) use (&$handler) {
				$handler = $callback;
				return true;
			}));

		$this->plugin->handleGetProperties($propFind, $node);
		$this->assertIsCallable($handler);
		return $handler;
	}

	private function jpegNode(int $id): File&MockObject {
		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->method('getMimetype')->willReturn('image/jpeg');
		$node = $this->createMock(File::class);
		$node->method('getFileInfo')->willReturn($fileInfo);
		$node->method('getId')->willReturn($id);
		return $node;
	}
}
