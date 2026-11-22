<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\config;

final readonly class ResourcePackSettings{
	public const string LOCALE_PATTERN = '/^[a-z]{2,3}_[A-Z]{2,3}$/D';

	/**
	 * @param array<string, array<string, string>> $translations
	 */
	public function __construct(
		private bool $enabled,
		private bool $required,
		private array $translations
	){}

	public function isEnabled() : bool{
		return $this->enabled;
	}

	public function isRequired() : bool{
		return $this->required;
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public function getTranslations() : array{
		return $this->translations;
	}
}
