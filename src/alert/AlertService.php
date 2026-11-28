<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use AxolotlBucketLabs\CustomAlerts\config\AlertsConfig;
use AxolotlBucketLabs\CustomAlerts\template\Template;
use Logger;
use pocketmine\utils\TextFormat;

final class AlertService{
	/** @var array<string, true> */
	private array $reportedPlaceholders = [];

	public function __construct(
		private readonly Logger $logger,
		private AlertsConfig $config
	){}

	public function setConfig(AlertsConfig $config) : void{
		$this->config = $config;
		$this->reportedPlaceholders = [];
	}

	public function render(AlertContext $context) : ?string{
		$template = $this->config->getAlert($context->getType());
		if(!$template instanceof Template){
			return null;
		}

		$message = $template->renderNamed(function(string $name) use($context) : ?string{
			$value = $context->getValue($name);
			if($value !== null){
				return $value;
			}

			$path = $context->getType()->value;
			if(!isset($this->reportedPlaceholders["$path:$name"])){
				$this->reportedPlaceholders["$path:$name"] = true;
				$this->logger->warning("Template '$path' uses unknown placeholder '{{$name}}'. It was left unchanged.");
			}
			return null;
		});

		return TextFormat::colorize($message);
	}
}
