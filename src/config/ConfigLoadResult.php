<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\config;

final readonly class ConfigLoadResult{
	/**
	 * @param list<string> $warnings
	 */
	public function __construct(
		private AlertsConfig $config,
		private array        $warnings
	){}

	public function getConfig() : AlertsConfig{
		return $this->config;
	}

	/**
	 * @return list<string>
	 */
	public function getWarnings() : array{
		return $this->warnings;
	}
}
