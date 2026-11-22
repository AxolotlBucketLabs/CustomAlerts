<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\pack;

use AxolotlBucketLabs\CustomAlerts\catalog\TranslationCatalog;
use AxolotlBucketLabs\CustomAlerts\catalog\TranslationSide;
use AxolotlBucketLabs\CustomAlerts\config\ResourcePackSettings;
use JsonException;
use Logger;
use pocketmine\resourcepacks\ZippedResourcePack;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function array_map;
use function array_sum;
use function array_unique;
use function array_values;
use function basename;
use function count;
use function file_get_contents;
use function glob;
use function pathinfo;
use function preg_match;
use function preg_match_all;
use function sort;

final readonly class LanguagePackLoader{
	public function __construct(
		private TranslationCatalog  $catalog,
		private LanguagePackBuilder $builder,
		private string              $localeDirectory,
		private Logger              $logger
	){}

	public function load(ResourcePackSettings $settings) : ?LoadedLanguagePack{
		if(!$settings->isEnabled()){
			return null;
		}

		$translations = $this->loadLocaleFiles();
		foreach($settings->getTranslations() as $locale => $entries){
			foreach($this->filter($entries, "resource-pack.translations.$locale") as $key => $value){
				$translations[$locale][$key] = $value;
			}
		}
		if($translations === []){
			$this->logger->warning("Resource pack is enabled but no valid client-side translations were configured.");
			return null;
		}

		try{
			$pack = new ZippedResourcePack($this->builder->build($translations));
			return new LoadedLanguagePack($pack, count($translations), array_sum(array_map(count(...), $translations)));
		}catch(JsonException|RuntimeException $e){
			$this->logger->error("Could not prepare generated language resource pack: " . $e->getMessage());
			return null;
		}
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	private function loadLocaleFiles() : array{
		$paths = glob(Path::join($this->localeDirectory, "*.ini"));
		if($paths === false){
			$this->logger->warning("Could not list translation files in $this->localeDirectory");
			return [];
		}

		$translations = [];
		foreach($paths as $path){
			$filename = basename($path);
			$locale = pathinfo($filename, PATHINFO_FILENAME);
			if(preg_match(ResourcePackSettings::LOCALE_PATTERN, $locale) !== 1){
				$this->logger->warning("Ignored translation file with invalid locale name: $filename");
				continue;
			}
			$contents = file_get_contents($path);
			if($contents === false){
				$this->logger->warning("Could not read translation file $filename");
				continue;
			}
			try{
				$entries = $this->filter(TranslationFileParser::parse($contents), $filename);
			}catch(RuntimeException $e){
				$this->logger->warning("Ignored translation file $filename: " . $e->getMessage());
				continue;
			}
			if($entries !== []){
				$translations[$locale] = $entries;
			}
		}
		return $translations;
	}

	/**
	 * @param array<string, string> $entries
	 * @return array<string, string>
	 */
	private function filter(array $entries, string $source) : array{
		$accepted = [];
		$serverEntries = 0;
		$invalidPlaceholders = 0;
		$uncatalogued = 0;
		foreach($entries as $key => $value){
			$entry = $this->catalog->get($key);
			if($entry === null){
				++$uncatalogued;
				$accepted[$key] = $value;
				continue;
			}
			if($entry->getSide() === TranslationSide::Server){
				++$serverEntries;
				continue;
			}
			preg_match_all('/\{%([0-9]+)}/', $value, $matches);
			$placeholders = array_values(array_unique(array_map(intval(...), $matches[1])));
			sort($placeholders);
			if($placeholders !== $entry->getPlaceholders()){
				++$invalidPlaceholders;
				continue;
			}
			$accepted[$key] = $value;
		}

		$this->logger->info("Loaded " . count($accepted) . " client translation(s) from $source. Skipped $serverEntries server-side and $invalidPlaceholders with mismatched placeholders. $uncatalogued uncatalogued key(s) were included without placeholder checks.");
		return $accepted;
	}
}
