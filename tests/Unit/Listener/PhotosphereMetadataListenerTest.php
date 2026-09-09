<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Tests\Unit\Listener;

use OCA\Viewer\Listener\PhotosphereMetadataListener;
use OCA\Viewer\Sabre\PhotospherePlugin;
use OCA\Viewer\Service\PhotosphereConfig;
use OCA\Viewer\Service\PhotosphereMetadataReader;
use OCP\EventDispatcher\Event;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\FilesMetadata\Event\MetadataLiveEvent;
use OCP\FilesMetadata\Event\MetadataNamedEvent;
use OCP\FilesMetadata\Model\IFilesMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PhotosphereMetadataListenerTest extends TestCase {
	private PhotosphereMetadataReader&MockObject $metadataReader;
	private PhotosphereConfig&MockObject $photosphereConfig;
	private PhotosphereMetadataListener $listener;

	protected function setUp(): void {
		parent::setUp();
		$this->metadataReader = $this->createMock(PhotosphereMetadataReader::class);
		$this->photosphereConfig = $this->createMock(PhotosphereConfig::class);
		$this->photosphereConfig->method('isPhotosphereSupportEnabled')->willReturn(true);
		$this->listener = new PhotosphereMetadataListener(
			$this->metadataReader,
			$this->photosphereConfig,
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testIgnoresUnrelatedEvents(): void {
		$this->metadataReader->expects($this->never())->method('fromFile');
		$this->listener->handle($this->createMock(Event::class));
	}

	public function testIgnoresNamedEventsWithAnUnexpectedName(): void {
		$node = $this->jpegNode();
		$storedMetadata = $this->createMock(IFilesMetadata::class);
		$storedMetadata->expects($this->never())->method('setArray');

		$this->metadataReader->expects($this->never())->method('fromFile');
		$this->listener->handle(new MetadataNamedEvent($node, $storedMetadata, 'some-other-app'));
	}

	public function testIgnoresNonJpegFiles(): void {
		$node = $this->createMock(File::class);
		$node->method('getMimetype')->willReturn('image/png');
		$storedMetadata = $this->createMock(IFilesMetadata::class);

		$this->metadataReader->expects($this->never())->method('fromFile');
		$this->listener->handle(new MetadataLiveEvent($node, $storedMetadata));
	}

	public function testIgnoresFolders(): void {
		$node = $this->createMock(Folder::class);
		$storedMetadata = $this->createMock(IFilesMetadata::class);

		$this->metadataReader->expects($this->never())->method('fromFile');
		$this->listener->handle(new MetadataLiveEvent($node, $storedMetadata));
	}

	public function testSkipsComputationWhenFeatureDisabled(): void {
		$this->photosphereConfig = $this->createMock(PhotosphereConfig::class);
		$this->photosphereConfig->method('isPhotosphereSupportEnabled')->willReturn(false);
		$listener = new PhotosphereMetadataListener($this->metadataReader, $this->photosphereConfig, $this->createMock(LoggerInterface::class));

		$node = $this->jpegNode();
		$storedMetadata = $this->createMock(IFilesMetadata::class);

		$this->metadataReader->expects($this->never())->method('fromFile');
		$listener->handle(new MetadataLiveEvent($node, $storedMetadata));
	}

	public function testStoresMetadataForALiveEventOnAPhotosphere(): void {
		$node = $this->jpegNode();
		$metadata = $this->photosphereMetadataFixture(usePanoramaViewer: true);
		$this->metadataReader->method('fromFile')->with($node)->willReturn($metadata);

		$storedMetadata = $this->createMock(IFilesMetadata::class);
		$storedMetadata->expects($this->once())
			->method('setArray')
			->with(PhotospherePlugin::METADATA_KEY, $metadata->toArray());
		$storedMetadata->expects($this->never())->method('unset');

		$this->listener->handle(new MetadataLiveEvent($node, $storedMetadata));
	}

	public function testStoresMetadataForAMatchingNamedEvent(): void {
		$node = $this->jpegNode();
		$metadata = $this->photosphereMetadataFixture(usePanoramaViewer: true);
		$this->metadataReader->method('fromFile')->with($node)->willReturn($metadata);

		$storedMetadata = $this->createMock(IFilesMetadata::class);
		$storedMetadata->expects($this->once())
			->method('setArray')
			->with(PhotospherePlugin::METADATA_KEY, $metadata->toArray());

		$this->listener->handle(new MetadataNamedEvent($node, $storedMetadata, PhotosphereMetadataListener::GENERATE_METADATA_NAME));
	}

	public function testUnsetsMetadataWhenFileIsNotAPhotosphere(): void {
		$node = $this->jpegNode();
		$metadata = $this->photosphereMetadataFixture(usePanoramaViewer: false);
		$this->metadataReader->method('fromFile')->with($node)->willReturn($metadata);

		$storedMetadata = $this->createMock(IFilesMetadata::class);
		$storedMetadata->expects($this->once())->method('unset')->with(PhotospherePlugin::METADATA_KEY);
		$storedMetadata->expects($this->never())->method('setArray');

		$this->listener->handle(new MetadataLiveEvent($node, $storedMetadata));
	}

	public function testDoesNotStoreAnythingWhenReadingFails(): void {
		$node = $this->jpegNode();
		$this->metadataReader->method('fromFile')->willThrowException(new \Exception('could not open file'));

		$storedMetadata = $this->createMock(IFilesMetadata::class);
		$storedMetadata->expects($this->never())->method('setArray');
		$storedMetadata->expects($this->never())->method('unset');

		$this->listener->handle(new MetadataLiveEvent($node, $storedMetadata));
	}

	private function jpegNode(): File&MockObject {
		$node = $this->createMock(File::class);
		$node->method('getMimetype')->willReturn('image/jpeg');
		$node->method('getName')->willReturn('photosphere.jpg');
		return $node;
	}

	private function photosphereMetadataFixture(bool $usePanoramaViewer): \OCA\Viewer\Model\PhotosphereMetadata {
		$metadata = new \OCA\Viewer\Model\PhotosphereMetadata();
		$metadata->usePanoramaViewer = $usePanoramaViewer;
		return $metadata;
	}
}
