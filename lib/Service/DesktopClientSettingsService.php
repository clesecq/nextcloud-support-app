<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Support\Service;

use OCP\IConfig;

/**
 * Exposes the admin defined desktop client managed settings from config.php,
 * allow list filtered so unknown keys and secrets never reach the client.
 */
class DesktopClientSettingsService {
	public const SCHEMA_VERSION = 1;

	private const TYPE_BOOL = 'bool';
	private const TYPE_INT = 'int';
	private const TYPE_STRING = 'string';

	/**
	 * Keys the client accepts, with type and whether the server may enforce each.
	 * Update and proxy keys are default only; only device policy enforces them, so
	 * a server cannot disable updates or reroute traffic.
	 * Names and types must match the client ManagedSettingsSchema.
	 */
	private const ALLOWED = [
		'skipUpdateCheck' => ['type' => self::TYPE_BOOL, 'enforce' => false],
		'autoUpdateCheck' => ['type' => self::TYPE_BOOL, 'enforce' => false],
		'virtualFilesMode' => ['type' => self::TYPE_STRING, 'enforce' => true],
		'proxyHost' => ['type' => self::TYPE_STRING, 'enforce' => false],
		'proxyPort' => ['type' => self::TYPE_INT, 'enforce' => false],
		'proxyType' => ['type' => self::TYPE_STRING, 'enforce' => false],
		'newBigFolderSizeLimit' => ['type' => self::TYPE_INT, 'enforce' => true],
		'confirmExternalStorage' => ['type' => self::TYPE_BOOL, 'enforce' => true],
		'useNewBigFolderSizeLimit' => ['type' => self::TYPE_BOOL, 'enforce' => true],
		'notifyExistingFoldersOverLimit' => ['type' => self::TYPE_BOOL, 'enforce' => true],
		'stopSyncingExistingFoldersOverLimit' => ['type' => self::TYPE_BOOL, 'enforce' => true],
	];

	public function __construct(
		private readonly IConfig $config,
	) {
	}

	/**
	 * @return array{schemaVersion: int, defaults: array<string, bool|int|string>, enforced: array<string, bool|int|string>}
	 */
	public function getManagedSettings(): array {
		return [
			'schemaVersion' => self::SCHEMA_VERSION,
			'defaults' => $this->filter($this->config->getSystemValue('desktopclient.defaults', []), false),
			'enforced' => $this->filter($this->config->getSystemValue('desktopclient.enforced', []), true),
		];
	}

	/**
	 * @return array<string, bool|int|string>
	 */
	private function filter(mixed $raw, bool $requireEnforce): array {
		if (!is_array($raw)) {
			return [];
		}

		$filtered = [];
		foreach (self::ALLOWED as $key => $meta) {
			if (!array_key_exists($key, $raw)) {
				continue;
			}
			if ($requireEnforce && !$meta['enforce']) {
				continue;
			}
			$value = $raw[$key];
			if (!is_scalar($value)) {
				continue;
			}
			$filtered[$key] = $this->convert($value, $meta['type']);
		}
		return $filtered;
	}

	private function convert(bool|int|float|string $value, string $type): bool|int|string {
		return match ($type) {
			self::TYPE_BOOL => filter_var($value, FILTER_VALIDATE_BOOLEAN),
			self::TYPE_INT => (int)$value,
			default => (string)$value,
		};
	}
}
