<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts;

use AxolotlBucketLabs\CustomAlerts\config\AlertsConfig;

final readonly class ReloadResult{
	/**
	 * @param list<string> $warnings
	 */
	private function __construct(
		private ?string $error,
		private array $warnings,
		private ?AlertsConfig $config
	){}

	public static function failed(string $error) : self{
		return new self($error, [], null);
	}

	/**
	 * @param list<string> $warnings
	 */
	public static function loaded(AlertsConfig $config, array $warnings) : self{
		return new self(null, $warnings, $config);
	}

	public function isSuccessful() : bool{
		return $this->error === null;
	}

	public function getError() : ?string{
		return $this->error;
	}

	/**
	 * @return list<string>
	 */
	public function getWarnings() : array{
		return $this->warnings;
	}

	public function getConfig() : ?AlertsConfig{
		return $this->config;
	}
}
