<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\config;

use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use AxolotlBucketLabs\CustomAlerts\template\Template;
use function array_key_exists;
use function explode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function preg_match;

final class ConfigLoader{
	public const int VERSION = 2;
	private const string DEFAULT_KILLER_FALLBACK = "the server";

	/** @var list<string> */
	private array $warnings = [];

	/**
	 * @param array<mixed> $data
	 * @throws ConfigException
	 */
	public function load(array $data) : ConfigLoadResult{
		$version = $data["config-version"] ?? null;
		if($version !== self::VERSION){
			$found = is_int($version) ? (string) $version : "missing";
			throw new ConfigException("Unsupported config-version ($found), expected " . self::VERSION . ". Move the old config.yml aside to generate a new one.");
		}

		$this->warnings = [];
		return new ConfigLoadResult(
			new AlertsConfig($this->loadAlerts($data), $this->loadKillerFallback($data)),
			$this->warnings
		);
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
				$alerts[$path] = false;
				continue;
			}
			if(!is_string($value)){
				$this->warnings[] = "Ignored '$path': expected a message string or false.";
				continue;
			}
			if(preg_match('/\{%[0-9]+}/', $value) === 1){
				$this->warnings[] = "Ignored '$path': packet placeholders are not available in broadcast messages.";
				continue;
			}

			$template = Template::parse($value);
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
