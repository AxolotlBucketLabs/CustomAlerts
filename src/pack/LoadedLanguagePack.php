<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\pack;

use pocketmine\resourcepacks\ResourcePack;

final readonly class LoadedLanguagePack{
	public function __construct(
		private ResourcePack $pack,
		private int $localeCount,
		private int $translationCount
	){}

	public function getPack() : ResourcePack{
		return $this->pack;
	}

	public function getLocaleCount() : int{
		return $this->localeCount;
	}

	public function getTranslationCount() : int{
		return $this->translationCount;
	}
}
