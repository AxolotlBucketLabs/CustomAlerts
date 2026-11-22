<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use AxolotlBucketLabs\CustomAlerts\config\AlertsConfig;
use AxolotlBucketLabs\CustomAlerts\template\PlaceholderRegistry;
use AxolotlBucketLabs\CustomAlerts\template\Template;
use Logger;

final class AlertService{
	/** @var array<string, true> */
	private array $reportedPlaceholders = [];

	public function __construct(
		private readonly PlaceholderRegistry $placeholders,
		private readonly Logger              $logger,
		private AlertsConfig                 $config
	){}

	public function setConfig(AlertsConfig $config) : void{
		$this->config = $config;
		$this->reportedPlaceholders = [];
	}

	public function isConfigured(AlertType $type) : bool{
		return $this->config->getAlert($type) !== null;
	}

	public function getKillerFallback() : string{
		return $this->config->getKillerFallback();
	}

	public function isHidden(AlertType $type) : bool{
		return $this->config->getAlert($type) === false;
	}

	public function render(AlertContext $context) : ?string{
		$template = $this->config->getAlert($context->getType());
		if(!$template instanceof Template){
			return null;
		}

		$message = $template->renderNamed(fn(string $name) : ?string => $context->getValue($name) ?? $this->resolveExternal($name, $context));
		$event = new AlertRenderEvent($context, $message);
		$event->call();
		return $event->isCancelled() ? null : $event->getMessage();
	}

	private function resolveExternal(string $name, AlertContext $context) : ?string{
		if($this->placeholders->isRegistered($name)){
			return $this->placeholders->resolve($name, $context);
		}

		$path = $context->getType()->value;
		if(!isset($this->reportedPlaceholders["$path:$name"])){
			$this->reportedPlaceholders["$path:$name"] = true;
			$this->logger->warning("Template '$path' uses unknown placeholder '{{$name}}'. It was left unchanged.");
		}
		return null;
	}
}
