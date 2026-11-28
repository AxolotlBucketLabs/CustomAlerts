<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\config;

use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use AxolotlBucketLabs\CustomAlerts\template\Template;
use function count;

final readonly class AlertsConfig{
	/**
	 * @param array<string, Template> $alerts
	 */
	public function __construct(
		private array $alerts
	){}

	public function getAlert(AlertType $type) : ?Template{
		return $this->alerts[$type->value] ?? null;
	}

	public function countAlerts() : int{
		return count($this->alerts);
	}
}
