<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\catalog;

final readonly class CatalogEntry{
	/**
	 * @param list<int> $placeholders
	 */
	public function __construct(
		private string          $key,
		private TranslationSide $side,
		private array           $placeholders,
		private string          $english
	){}

	public function getKey() : string{
		return $this->key;
	}

	public function getSide() : TranslationSide{
		return $this->side;
	}

	/**
	 * @return list<int>
	 */
	public function getPlaceholders() : array{
		return $this->placeholders;
	}

	public function getEnglish() : string{
		return $this->english;
	}
}
