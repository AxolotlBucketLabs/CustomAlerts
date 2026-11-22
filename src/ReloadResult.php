<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts;

use AxolotlBucketLabs\CustomAlerts\config\AlertsConfig;
use AxolotlBucketLabs\CustomAlerts\pack\LoadedLanguagePack;

final readonly class ReloadResult{
	/**
	 * @param list<string> $warnings
	 */
	private function __construct(
		private ?string $error,
		private array $warnings,
		private ?AlertsConfig $config,
		private ?LoadedLanguagePack $languagePack
	){}

	public static function failed(string $error) : self{
		return new self($error, [], null, null);
	}

	/**
	 * @param list<string> $warnings
	 */
	public static function loaded(AlertsConfig $config, array $warnings, ?LoadedLanguagePack $languagePack) : self{
		return new self(null, $warnings, $config, $languagePack);
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

	public function getLanguagePack() : ?LoadedLanguagePack{
		return $this->languagePack;
	}
}
