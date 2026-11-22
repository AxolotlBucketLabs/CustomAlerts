<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\config;

use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use AxolotlBucketLabs\CustomAlerts\catalog\TranslationCatalog;
use AxolotlBucketLabs\CustomAlerts\catalog\TranslationSide;
use AxolotlBucketLabs\CustomAlerts\template\Template;
use function array_key_exists;
use function explode;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function preg_match;

final class ConfigLoader{
	public const int VERSION = 2;
	private const string DEFAULT_KILLER_FALLBACK = "environment";

	/** @var list<string> */
	private array $warnings = [];

	public function __construct(
		private readonly TranslationCatalog $catalog
	){}

	/**
	 * @param array<mixed> $data
	 * @return ConfigLoadResult
	 * @throws ConfigException
	 */
	public function load(array $data) : ConfigLoadResult{
		$version = $data["config-version"] ?? null;
		if($version !== self::VERSION){
			$found = is_int($version) ? (string) $version : "missing";
			throw new ConfigException("Unsupported config-version ($found), expected " . self::VERSION . ". Move the old config.yml aside to generate a new one.");
		}

		$this->warnings = [];
		$customMessages = $data["custom-messages"] ?? true;
		if(!is_bool($customMessages)){
			$this->warnings[] = "Ignored 'custom-messages': expected true or false.";
			$customMessages = true;
		}
		if(!$customMessages){
			unset($data["messages"]);
		}

		$config = new AlertsConfig($this->loadAlerts($data), $this->loadKillerFallback($data), $this->loadPacketOverrides($data), $this->loadResourcePack($data));
		return new ConfigLoadResult($config, $this->warnings);
	}

	/**
	 * @param array<mixed> $data
	 */
	private function loadKillerFallback(array $data) : string{
		$value = self::find($data, "messages.death.killer-fallback");
		if($value === null){
			return self::DEFAULT_KILLER_FALLBACK;
		}
		if(!is_string($value)){
			$this->warnings[] = "Ignored 'messages.death.killer-fallback': expected a string.";
			return self::DEFAULT_KILLER_FALLBACK;
		}
		return $value;
	}

	/**
	 * @param array<mixed> $data
	 * @return array<string, Template|false>
	 */
	private function loadAlerts(array $data) : array{
		$alerts = [];
		foreach(AlertType::cases() as $type){
			$path = $type->value;
			$value = self::find($data, $path);
			if($value === null){
				continue;
			}
			if($value === false){
				if($type->isHideable()){
					$alerts[$path] = false;
				}else{
					$this->warnings[] = "Ignored '$path': this message cannot be hidden. Set a message or remove the key.";
				}
				continue;
			}
			if(!is_string($value)){
				$this->warnings[] = "Ignored '$path': expected a message string" . ($type->isHideable() ? " or false." : ".");
				continue;
			}

			$template = Template::parse($value);
			if($template->getIndices() !== []){
				$this->warnings[] = "Ignored '$path': packet placeholders such as {%0} are not available in event messages.";
				continue;
			}
			foreach($template->getNames() as $name){
				if(AlertType::isBuiltInPlaceholder($name) && !in_array($name, $type->getPlaceholders(), true)){
					$this->warnings[] = "Template '$path' uses '{{$name}}', which is not available for this message. It will be left unchanged.";
				}
			}
			$alerts[$path] = $template;
		}
		return $alerts;
	}

	/**
	 * @param array<mixed> $data
	 */
	private function loadPacketOverrides(array $data) : PacketOverrides{
		$text = [];
		foreach($this->findMap($data, "packets.text") as $key => $value){
			$key = (string) $key;
			if(!is_string($value)){
				$this->warnings[] = "Ignored packets.text '$key': the replacement must be a string.";
				continue;
			}
			$template = Template::parse($value);
			if($template->getNames() !== []){
				$this->warnings[] = "Ignored packets.text '$key': packet templates use numeric placeholders such as {%0}.";
				continue;
			}

			$entry = $this->catalog->get($key);
			if($entry === null){
				$this->warnings[] = "packets.text '$key' is not in the translation catalog, so its placeholders were not checked.";
			}elseif($entry->getSide() === TranslationSide::Server){
				$this->warnings[] = "Ignored packets.text '$key': server-side keys are translated before packets are sent.";
				continue;
			}else{
				foreach($template->getIndices() as $index){
					if(!in_array($index, $entry->getPlaceholders(), true)){
						$this->warnings[] = "Ignored packets.text '$key': placeholder {%$index} is not present in the source translation.";
						continue 2;
					}
				}
			}
			$text[$key] = $template;
		}

		$disconnect = [];
		foreach($this->findMap($data, "packets.disconnect") as $source => $replacement){
			$source = (string) $source;
			if($source === "" || !is_string($replacement)){
				$this->warnings[] = "Ignored a packets.disconnect entry. Use a non-empty exact source message and a string replacement.";
				continue;
			}
			$disconnect[$source] = $replacement;
		}

		return new PacketOverrides($text, $disconnect);
	}

	/**
	 * @param array<mixed> $data
	 */
	private function loadResourcePack(array $data) : ResourcePackSettings{
		$enabled = self::find($data, "resource-pack.enabled") ?? false;
		$required = self::find($data, "resource-pack.required") ?? false;
		if(!is_bool($enabled) || !is_bool($required)){
			$this->warnings[] = "resource-pack.enabled and resource-pack.required must be true or false. The resource pack was disabled.";
			return new ResourcePackSettings(false, false, []);
		}

		$translations = [];
		foreach($this->findMap($data, "resource-pack.translations") as $locale => $entries){
			$locale = (string) $locale;
			if(preg_match(ResourcePackSettings::LOCALE_PATTERN, $locale) !== 1 || !is_array($entries)){
				$this->warnings[] = "Ignored resource-pack.translations '$locale': expected a Bedrock locale such as vi_VN mapped to translations.";
				continue;
			}
			foreach($entries as $key => $value){
				if(!is_string($value)){
					$this->warnings[] = "Ignored resource-pack.translations.$locale '$key': the value must be a string.";
					continue;
				}
				$translations[$locale][(string) $key] = $value;
			}
		}

		return new ResourcePackSettings($enabled, $required, $translations);
	}

	/**
	 * @param array<mixed> $data
	 * @param string $path
	 * @return array<mixed>
	 */
	private function findMap(array $data, string $path) : array{
		$value = self::find($data, $path);
		if($value === null){
			return [];
		}
		if(!is_array($value)){
			$this->warnings[] = "Ignored '$path': expected a map.";
			return [];
		}
		return $value;
	}

	/**
	 * @param array<mixed> $data
	 * @param string $path
	 * @return mixed
	 */
	private static function find(array $data, string $path) : mixed{
		$value = $data;
		foreach(explode(".", $path) as $key){
			if(!is_array($value) || !array_key_exists($key, $value)){
				return null;
			}
			$value = $value[$key];
		}
		return $value;
	}
}
