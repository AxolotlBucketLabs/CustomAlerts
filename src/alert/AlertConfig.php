<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use AxolotlBucketLabs\CustomAlerts\template\Template;
use function array_key_exists;
use function in_array;
use function is_string;
use function preg_match;
use function trim;

final readonly class AlertConfig{
	/**
	 * @param array<string, Template|false> $alerts
	 * @param list<string>                  $warnings
	 */
	private function __construct(
		private array $alerts,
		private array $warnings
	){}

	/**
	 * @param array<mixed> $data
	 */
	public static function from(array $data) : self{
		$alerts = [];
		$warnings = [];
		foreach(AlertType::cases() as $type){
			$key = $type->value;
			if(!array_key_exists($key, $data)){
				continue;
			}

			$value = $data[$key];
			if($value === false){
				$alerts[$key] = false;
				continue;
			}
			if(!is_string($value) || trim($value) === ""){
				$warnings[] = "Ignored '$key': expected a non-empty message string or false to hide it.";
				continue;
			}
			if(preg_match('/\{%[0-9]+}/', $value) === 1){
				$warnings[] = "Ignored '$key': packet placeholders are not available in broadcast messages.";
				continue;
			}

			$template = Template::parse($value);
			foreach($template->getNames() as $name){
				if(AlertType::isBuiltInPlaceholder($name) && !in_array($name, $type->getPlaceholders(), true)){
					$warnings[] = "Template '$key' uses '{{$name}}', which is not available for this message. It will be left unchanged.";
				}
			}
			$alerts[$key] = $template;
		}

		return new self($alerts, $warnings);
	}

	public function getAlert(AlertType $type) : Template|false|null{
		return $this->alerts[$type->value] ?? null;
	}

	/**
	 * @return list<string>
	 */
	public function getWarnings() : array{
		return $this->warnings;
	}
}
