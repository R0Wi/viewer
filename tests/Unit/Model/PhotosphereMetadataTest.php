<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Tests\Unit\Model;

use OCA\Viewer\Model\PhotosphereMetadata;
use PHPUnit\Framework\TestCase;
use Sabre\Xml\Writer;

/**
 * Ported from files_photospheres' XmpResultModelTest.
 */
class PhotosphereMetadataTest extends TestCase {
	public function testXmlSerialize(): void {
		$writer = $this->createMock(Writer::class);
		$metadata = $this->buildMetadata();

		$expectedJson = json_encode([
			'usePanoramaViewer' => false,
			'containsCroppingConfig' => true,
			'croppingConfig' => [
				'fullWidth' => 1,
				'fullHeight' => 2,
				'croppedWidth' => 3,
				'croppedHeight' => 4,
				'croppedX' => 5,
				'croppedY' => 6,
				'poseHeading' => 7.0,
				'posePitch' => 8.0,
				'poseRoll' => 9.0,
			],
		]);

		$writer->expects($this->once())
			->method('write')
			->with($this->equalTo($expectedJson));

		$metadata->xmlSerialize($writer);
	}

	public function testFromArray(): void {
		$data = [
			'usePanoramaViewer' => true,
			'containsCroppingConfig' => true,
			'croppingConfig' => [
				'fullWidth' => 1,
				'fullHeight' => 2,
				'croppedWidth' => 3,
				'croppedHeight' => 4,
				'croppedX' => 5,
				'croppedY' => 6,
				'poseHeading' => 7,
				'posePitch' => 8,
				'poseRoll' => 9,
			],
		];

		$metadata = PhotosphereMetadata::fromArray($data);

		$this->assertTrue($metadata->usePanoramaViewer);
		$this->assertTrue($metadata->containsCroppingConfig);
		$this->assertSame(1, $metadata->croppingConfig->fullWidth);
		$this->assertSame(9.0, $metadata->croppingConfig->poseRoll);
	}

	public function testFromArrayWithMissingKeys(): void {
		$metadata = PhotosphereMetadata::fromArray([]);

		$this->assertFalse($metadata->usePanoramaViewer);
		$this->assertFalse($metadata->containsCroppingConfig);
		$this->assertNull($metadata->croppingConfig->fullWidth);
	}

	public function testToArrayFromArrayRoundTrip(): void {
		$metadata = $this->buildMetadata();

		$roundTripped = PhotosphereMetadata::fromArray($metadata->toArray());

		$this->assertEquals($metadata->toArray(), $roundTripped->toArray());
	}

	private function buildMetadata(): PhotosphereMetadata {
		$metadata = new PhotosphereMetadata();
		$metadata->usePanoramaViewer = false;
		$metadata->containsCroppingConfig = true;
		$metadata->croppingConfig->fullWidth = 1;
		$metadata->croppingConfig->fullHeight = 2;
		$metadata->croppingConfig->croppedWidth = 3;
		$metadata->croppingConfig->croppedHeight = 4;
		$metadata->croppingConfig->croppedX = 5;
		$metadata->croppingConfig->croppedY = 6;
		$metadata->croppingConfig->poseHeading = 7;
		$metadata->croppingConfig->posePitch = 8;
		$metadata->croppingConfig->poseRoll = 9;
		return $metadata;
	}
}
