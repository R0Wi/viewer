<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Tests\Unit\Service;

use OCA\Viewer\Service\PhotosphereMetadataReader;
use OCP\Files\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Ported from files_photospheres' XmpDataReaderTest, which ran the same
 * assertions against the same corpus (see tests/Testdata) — see
 * PhotosphereMetadataReader for the (verified byte-identical) port of the
 * reading logic itself.
 */
class PhotosphereMetadataReaderTest extends TestCase {
	private PhotosphereMetadataReader $reader;
	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->reader = new PhotosphereMetadataReader($this->logger);
	}

	#[DataProvider('positiveFilesProvider')]
	public function testPositiveFilesAreDetectedAsPhotospheres(string $path): void {
		$metadata = $this->reader->fromFile($this->mockFileFor($path));
		$this->assertTrue($metadata->usePanoramaViewer);

		// If a JSON payload exists for this fixture, its cropping config
		// must match exactly.
		$jsonFile = preg_replace('/\.jpg$/', '.json', $path);
		if (file_exists($jsonFile)) {
			$expected = json_decode(file_get_contents($jsonFile), true);
			$this->assertEquals($expected, $metadata->croppingConfig->toArray());
		}
	}

	#[DataProvider('negativeFilesProvider')]
	public function testNegativeFilesAreNotDetectedAsPhotospheres(string $path): void {
		$metadata = $this->reader->fromFile($this->mockFileFor($path));
		$this->assertFalse($metadata->usePanoramaViewer);
	}

	public function testThrowsOnCannotOpenFile(): void {
		$file = $this->createMock(File::class);
		$file->expects($this->once())
			->method('fopen')
			->willReturn(false);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Could not open file');
		$this->reader->fromFile($file);
	}

	/**
	 * Regression test for a buffer-slicing bug (see PhotosphereMetadataReader::fromString):
	 * a GPano tag occurring in the read buffer but *outside* the actual
	 * <x:xmpmeta>...</x:xmpmeta> block must not be picked up.
	 */
	public function testIgnoresPanoramaTagOutsideOfXmpBlock(): void {
		$fileString = 'GPano:ProjectionType="equirectangular"'
			. str_repeat(' ', 32)
			. '<x:xmpmeta>no GPano data here</x:xmpmeta>';

		$metadata = $this->reader->fromString($fileString);

		$this->assertFalse($metadata->usePanoramaViewer);
		$this->assertFalse($metadata->containsCroppingConfig);
	}

	public function testUsesOnlyTheExtractedXmpBlockForCroppingConfig(): void {
		$fileString = 'GPano:FullPanoWidthPixels="99999"'
			. str_repeat(' ', 32)
			. '<x:xmpmeta>GPano:FullPanoWidthPixels="1234"</x:xmpmeta>';

		$metadata = $this->reader->fromString($fileString);

		$this->assertTrue($metadata->containsCroppingConfig);
		$this->assertSame(1234, $metadata->croppingConfig->fullWidth);
	}

	public static function positiveFilesProvider(): array {
		return self::filesMatching('pos*.jpg');
	}

	public static function negativeFilesProvider(): array {
		return array_merge(
			self::filesMatching('neg*.jpg'),
			self::filesMatching('missing_*.jpg'),
		);
	}

	private static function filesMatching(string $pattern): array {
		$files = glob(__DIR__ . '/../../Testdata/' . $pattern);
		$cases = [];
		foreach ($files as $file) {
			$cases[basename($file)] = [$file];
		}
		return $cases;
	}

	private function mockFileFor(string $path): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('fopen')
			->willReturnCallback(static fn (string $mode) => fopen($path, $mode));
		return $file;
	}
}
