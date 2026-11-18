<?php

declare(strict_types=1);

namespace NhanAZ\CustomAlerts;

use JsonException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\ConfigLoadException;
use pocketmine\utils\Utils;
use RuntimeException;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function basename;
use function count;
use function explode;
use function file_get_contents;
use function glob;
use function is_array;
use function is_file;
use function is_string;
use function pathinfo;
use function preg_match;
use function preg_match_all;
use function rtrim;
use function sort;
use function strtolower;

final class Main extends PluginBase{
	/** @var array<mixed, mixed> */
	private array $settings = [];
	private TranslationCatalog $catalog;
	/** @var array<string, string> */
	private array $textOverrides = [];
	/** @var array<string, string> */
	private array $disconnectOverrides = [];
	/** @var array<string, true> */
	private array $reportedPacketWarnings = [];
	private ?ResourcePack $resourcePack = null;

	protected function onEnable() : void{
		$this->saveDefaultConfig();
		$defaultTranslationPath = rtrim($this->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . "translations" . DIRECTORY_SEPARATOR . "vi_VN.ini";
		if(!is_file($defaultTranslationPath) && !$this->saveResource("translations/vi_VN.ini")){
			throw new RuntimeException("Required translations/vi_VN.ini plugin resource is missing");
		}
		$catalogJson = file_get_contents($this->getResourcePath("translation-catalog.json"));
		if($catalogJson === false){
			throw new \RuntimeException("Required translation-catalog.json plugin resource is missing");
		}
		$this->catalog = TranslationCatalog::fromJson($catalogJson);
		if(!$this->reloadPluginConfig()){
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$manager = $this->getServer()->getPluginManager();
		$manager->registerEvents(new MessageListener($this), $this);
		$manager->registerEvents(new PacketOverrideListener($this), $this);
		$manager->registerEvents(new ResourcePackListener($this), $this);
	}

	protected function onDisable() : void{
		$this->resourcePack = null;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if($command->getName() !== "customalerts"){
			return false;
		}
		if(!isset($args[0]) || strtolower($args[0]) !== "reload" || isset($args[1])){
			return false;
		}
		if(!$this->reloadPluginConfig()){
			$sender->sendMessage("§8[§cCustomAlerts§8] §7Không thể tải cấu hình; cấu hình cũ vẫn đang được dùng.");
			return true;
		}
		$sender->sendMessage("§8[§aCustomAlerts§8] §7Đã tải lại cấu hình.");
		return true;
	}

	public function reloadPluginConfig() : bool{
		try{
			$this->reloadConfig();
		}catch(ConfigLoadException $e){
			$this->getLogger()->error("Could not reload config.yml: " . $e->getMessage());
			return false;
		}

		$this->settings = $this->getConfig()->getAll();
		$this->reportedPacketWarnings = [];
		$this->validateMessageTemplates();
		$this->textOverrides = $this->loadTextOverrides();
		$this->disconnectOverrides = $this->loadDisconnectOverrides();
		$this->resourcePack = $this->loadConfiguredResourcePack();
		return true;
	}

	/** @return array<mixed, mixed> */
	public function section(string $path) : array{
		$value = $this->settings;
		foreach(explode(".", $path, 20) as $key){
			if(!is_array($value) || !array_key_exists($key, $value)){
				return [];
			}
			$value = $value[$key];
		}
		return is_array($value) ? $value : [];
	}

	public function getCatalog() : TranslationCatalog{
		return $this->catalog;
	}

	/** @return array<string, string> */
	public function getTextOverrides() : array{
		return $this->textOverrides;
	}

	/** @return array<string, string> */
	public function getDisconnectOverrides() : array{
		return $this->disconnectOverrides;
	}

	public function getResourcePack() : ?ResourcePack{
		return $this->resourcePack;
	}

	public function resourcePackIsRequired() : bool{
		$section = $this->section("resource-pack");
		return ($section["enabled"] ?? false) === true && ($section["required"] ?? false) === true && $this->resourcePack !== null;
	}

	public function warnMissingPacketParameter(string $key, int $parameter) : void{
		$warningKey = $key . ":" . $parameter;
		if(isset($this->reportedPacketWarnings[$warningKey])){
			return;
		}
		$this->reportedPacketWarnings[$warningKey] = true;
		$this->getLogger()->warning("Skipped packet override for '$key': packet parameter {$parameter} was not supplied.");
	}

	/** @return array<string, string> */
	private function loadTextOverrides() : array{
		$overrides = [];
		$settings = $this->section("packets.text");
		foreach(array_keys($settings) as $key){
			$template = $settings[$key];
			if(!is_string($key) || !is_string($template)){
				$this->getLogger()->warning("Ignored an invalid packets.text entry; both key and value must be strings.");
				continue;
			}
			$placeholders = $this->catalog->getPlaceholders($key);
			if($placeholders === null || !$this->catalog->isClientKey($key)){
				$this->getLogger()->warning("Ignored packets.text key '$key': only catalogued client-side translation keys can be intercepted.");
				continue;
			}
			if(preg_match('/\{[A-Za-z_][A-Za-z0-9_]*\}/', $template) === 1){
				$this->getLogger()->warning("Ignored packets.text key '$key': packet templates use numeric placeholders such as {0}.");
				continue;
			}
			preg_match_all('/\{([0-9]+)\}/', $template, $matches);
			$valid = true;
			foreach($matches[1] as $index){
				if(!in_array((int) $index, $placeholders, true)){
					$this->getLogger()->warning("Ignored packets.text key '$key': placeholder {{$index}} is not present in the source translation.");
					$valid = false;
				}
			}
			if($valid){
				$overrides[$key] = $template;
			}
		}
		return $overrides;
	}

	/** @return array<string, string> */
	private function loadDisconnectOverrides() : array{
		$overrides = [];
		$settings = $this->section("packets.disconnect");
		foreach(array_keys($settings) as $source){
			$replacement = $settings[$source];
			if(!is_string($source) || $source === "" || !is_string($replacement)){
				$this->getLogger()->warning("Ignored an invalid packets.disconnect entry; use a non-empty exact source message and a string replacement.");
				continue;
			}
			$overrides[$source] = $replacement;
		}
		return $overrides;
	}

	private function loadConfiguredResourcePack() : ?ResourcePack{
		$settings = $this->section("resource-pack");
		if(($settings["enabled"] ?? false) !== true){
			return null;
		}
		$translations = $this->loadTranslationFiles();
		$rawTranslations = $settings["translations"] ?? [];
		if(!is_array($rawTranslations)){
			$this->getLogger()->warning("resource-pack.translations must be a locale-to-translations map.");
			$rawTranslations = [];
		}

		foreach(array_keys($rawTranslations) as $locale){
			$entries = $rawTranslations[$locale];
			if(!is_string($locale) || preg_match('/^[a-z]{2,3}_[A-Z]{2,3}$/D', $locale) !== 1 || !is_array($entries)){
				$this->getLogger()->warning("Ignored an invalid resource-pack locale entry.");
				continue;
			}
			foreach(array_keys($entries) as $key){
				$value = $entries[$key];
				if(!is_string($key) || !is_string($value)){
					$this->getLogger()->warning("Ignored a resource-pack translation with a non-string key or value in $locale.");
					continue;
				}
				if(!$this->catalog->isClientKey($key)){
					$this->getLogger()->warning("Ignored resource-pack key '$key': it is not a catalogued client-side translation key.");
					continue;
				}
				$translations[$locale][$key] = $value;
			}
		}
		if($translations === []){
			$this->getLogger()->warning("Resource pack is enabled but no valid client-side translations were configured.");
			return null;
		}

		try{
			$path = LanguagePackBuilder::build($this->getDataFolder(), $translations);
			return new ZippedResourcePack($path);
		}catch(JsonException|\RuntimeException $e){
			$this->getLogger()->error("Could not prepare generated language resource pack: " . $e->getMessage());
			return null;
		}
	}

	/** @return array<string, array<string, string>> */
	private function loadTranslationFiles() : array{
		$directory = rtrim($this->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . "translations";
		$paths = glob($directory . DIRECTORY_SEPARATOR . "*.ini");
		if($paths === false){
			$this->getLogger()->warning("Could not list translation files in the plugin data folder.");
			return [];
		}

		$translations = [];
		foreach($paths as $path){
			$filename = basename($path);
			$locale = pathinfo($filename, PATHINFO_FILENAME);
			if(preg_match('/^[a-z]{2,3}_[A-Z]{2,3}$/D', $locale) !== 1){
				$this->getLogger()->warning("Ignored translation file with invalid locale name: $filename");
				continue;
			}
			$contents = file_get_contents($path);
			if($contents === false){
				$this->getLogger()->warning("Could not read translation file $filename");
				continue;
			}
			try{
				$entries = TranslationFileParser::parse($contents);
			}catch(RuntimeException $e){
				$this->getLogger()->warning("Ignored translation file $filename: " . $e->getMessage());
				continue;
			}

			$serverEntries = 0;
			$unknownEntries = 0;
			$invalidPlaceholders = 0;
			foreach(Utils::stringifyKeys($entries) as $key => $value){
				if(!$this->catalog->hasKey($key)){
					++$unknownEntries;
					continue;
				}
				if(!$this->catalog->isClientKey($key)){
					++$serverEntries;
					continue;
				}
				$expectedPlaceholders = $this->catalog->getPlaceholders($key) ?? [];
				preg_match_all('/\{%([0-9]+)\}/', $value, $matches);
				$actualPlaceholders = array_values(array_unique(array_map("intval", $matches[1])));
				sort($actualPlaceholders);
				if($actualPlaceholders !== $expectedPlaceholders){
					++$invalidPlaceholders;
					continue;
				}
				$translations[$locale][$key] = $value;
			}
			$this->getLogger()->info("Loaded " . count($translations[$locale] ?? []) . " client translation(s) from $filename; skipped $serverEntries server-side, $unknownEntries unknown, and $invalidPlaceholders with mismatched placeholders.");
		}
		return $translations;
	}

	private function validateMessageTemplates() : void{
		$definitions = [
			"messages.join.format" => ["player", "display_name", "world"],
			"messages.quit.format" => ["player", "display_name", "world", "reason"],
			"messages.chat.format" => ["player", "display_name", "world", "message", "x", "y", "z"],
			"messages.death.broadcast" => ["player", "display_name", "world", "killer", "x", "y", "z"],
			"messages.death.screen" => ["player", "display_name", "world", "killer", "x", "y", "z"],
			"messages.kick.broadcast" => ["player", "display_name", "world", "reason", "screen"],
			"messages.kick.reason" => ["player", "display_name", "world", "reason", "screen"],
			"messages.kick.screen" => ["player", "display_name", "world", "reason", "screen"],
			"messages.login.message" => ["player", "display_name", "world", "reason"],
			"messages.pre-login.server-full.reason" => ["player"],
			"messages.pre-login.server-full.screen" => ["player"],
			"messages.pre-login.whitelist.reason" => ["player"],
			"messages.pre-login.whitelist.screen" => ["player"],
			"messages.pre-login.banned.reason" => ["player"],
			"messages.pre-login.banned.screen" => ["player"]
		];
		foreach($definitions as $path => $allowed){
			$parts = explode(".", $path, 20);
			$field = array_pop($parts);
			$section = $this->section(implode(".", $parts));
			$template = $section[$field] ?? null;
			if(!is_string($template)){
				continue;
			}
			preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $template, $matches);
			foreach($matches[1] as $placeholder){
				if(!in_array($placeholder, $allowed, true)){
					$this->getLogger()->warning("Template '$path' uses unknown placeholder '{$placeholder}'. It will be left unchanged.");
				}
			}
		}
	}
}
