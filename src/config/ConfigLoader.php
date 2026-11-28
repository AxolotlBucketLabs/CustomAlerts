<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\config;

use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use AxolotlBucketLabs\CustomAlerts\template\Template;
use function array_key_exists;
use function in_array;
use function is_int;
use function is_string;
use function preg_match;
use function trim;

final class ConfigLoader{
	public const int VERSION = 3;

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
		$alerts = [];
		foreach(AlertType::cases() as $type){
			$key = $type->value;
			if(!array_key_exists($key, $data)){
				continue;
			}

			$value = $data[$key];
			if(!is_string($value) || trim($value) === ""){
				$this->warnings[] = "Ignored '$key': expected a non-empty message string.";
				continue;
			}
			if(preg_match('/\{%[0-9]+}/', $value) === 1){
				$this->warnings[] = "Ignored '$key': packet placeholders are not available in broadcast messages.";
				continue;
			}

			$template = Template::parse($value);
			foreach($template->getNames() as $name){
				if(AlertType::isBuiltInPlaceholder($name) && !in_array($name, $type->getPlaceholders(), true)){
					$this->warnings[] = "Template '$key' uses '{{$name}}', which is not available for this message. It will be left unchanged.";
				}
			}
			$alerts[$key] = $template;
		}

		return new ConfigLoadResult(new AlertsConfig($alerts), $this->warnings);
	}
}
