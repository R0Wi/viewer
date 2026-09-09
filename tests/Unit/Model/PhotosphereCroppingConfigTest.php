<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Tests\Unit\Model;

use OCA\Viewer\Model\PhotosphereCroppingConfig;
use PHPUnit\Framework\TestCase;

/**
 * Ported from files_photospheres' CroppingConfigModelTest.
 */
class PhotosphereCroppingConfigTest extends TestCase {
	public function testFromArray(): void {
		$croppingConfig = PhotosphereCroppingConfig::fromArray([
			'fullWidth' => 1,
			'fullHeight' => 2,
			'croppedWidth' => 3,
			'croppedHeight' => 4,
			'croppedX' => 5,
			'croppedY' => 6,
			'poseHeading' => 7,
			'posePitch' => 8,
			'poseRoll' => 9,
		]);

		$this->assertSame(1, $croppingConfig->fullWidth);
		$this->assertSame(2, $croppingConfig->fullHeight);
		$this->assertSame(3, $croppingConfig->croppedWidth);
		$this->assertSame(4, $croppingConfig->croppedHeight);
		$this->assertSame(5, $croppingConfig->croppedX);
		$this->assertSame(6, $croppingConfig->croppedY);
		$this->assertSame(7.0, $croppingConfig->poseHeading);
		$this->assertSame(8.0, $croppingConfig->posePitch);
		$this->assertSame(9.0, $croppingConfig->poseRoll);
	}

	public function testFromArrayWithMissingAndNonNumericValues(): void {
		$croppingConfig = PhotosphereCroppingConfig::fromArray([
			'fullWidth' => 'not-a-number',
		]);

		$this->assertNull($croppingConfig->fullWidth);
		$this->assertNull($croppingConfig->fullHeight);
		$this->assertNull($croppingConfig->poseHeading);
	}

	public function testToArrayRoundTrip(): void {
		$data = [
			'fullWidth' => 1,
			'fullHeight' => 2,
			'croppedWidth' => 3,
			'croppedHeight' => 4,
			'croppedX' => 5,
			'croppedY' => 6,
			'poseHeading' => 7.5,
			'posePitch' => 8.5,
			'poseRoll' => 9.5,
		];

		$croppingConfig = PhotosphereCroppingConfig::fromArray($data);

		$this->assertEquals($data, $croppingConfig->toArray());
	}
}
