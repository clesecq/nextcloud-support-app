<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Support\Repair;

use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MigrateLazyAppConfig implements IRepairStep {
	public function __construct(
		protected readonly IAppConfig $appConfig,
	) {
	}

	public function getName(): string {
		return 'Migrate some config values to lazy loading';
	}

	public function run(IOutput $output): void {
		$this->appConfig->updateLazy('support', 'last_response', true);

		// if more config values needs to be switched to lazy, just add them here
	}
}
