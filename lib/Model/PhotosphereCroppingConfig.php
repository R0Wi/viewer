<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Model;

/**
 * Cropping and pose information of a photosphere as described by the
 * Google Photo Sphere XMP metadata (GPano namespace, see
 * https://developers.google.com/streetview/spherical-metadata).
 *
 * The structure matches the "panoData" object of photo-sphere-viewer.
 *
 * @psalm-api
 */
class PhotosphereCroppingConfig {
	public ?int $fullWidth = null;
	public ?int $fullHeight = null;
	public ?int $croppedWidth = null;
	public ?int $croppedHeight = null;
	public ?int $croppedX = null;
	public ?int $croppedY = null;
	public ?float $poseHeading = null;
	public ?float $posePitch = null;
	public ?float $poseRoll = null;

	public static function fromArray(array $data): self {
		$croppingConfig = new self();
		$croppingConfig->fullWidth = self::toIntOrNull($data['fullWidth'] ?? null);
		$croppingConfig->fullHeight = self::toIntOrNull($data['fullHeight'] ?? null);
		$croppingConfig->croppedWidth = self::toIntOrNull($data['croppedWidth'] ?? null);
		$croppingConfig->croppedHeight = self::toIntOrNull($data['croppedHeight'] ?? null);
		$croppingConfig->croppedX = self::toIntOrNull($data['croppedX'] ?? null);
		$croppingConfig->croppedY = self::toIntOrNull($data['croppedY'] ?? null);
		$croppingConfig->poseHeading = self::toFloatOrNull($data['poseHeading'] ?? null);
		$croppingConfig->posePitch = self::toFloatOrNull($data['posePitch'] ?? null);
		$croppingConfig->poseRoll = self::toFloatOrNull($data['poseRoll'] ?? null);
		return $croppingConfig;
	}

	private static function toIntOrNull(mixed $value): ?int {
		return is_numeric($value) ? (int)$value : null;
	}

	private static function toFloatOrNull(mixed $value): ?float {
		return is_numeric($value) ? (float)$value : null;
	}

	public function toArray(): array {
		return [
			'fullWidth' => $this->fullWidth,
			'fullHeight' => $this->fullHeight,
			'croppedWidth' => $this->croppedWidth,
			'croppedHeight' => $this->croppedHeight,
			'croppedX' => $this->croppedX,
			'croppedY' => $this->croppedY,
			'poseHeading' => $this->poseHeading,
			'posePitch' => $this->posePitch,
			'poseRoll' => $this->poseRoll,
		];
	}
}
