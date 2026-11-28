<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\config;

use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use AxolotlBucketLabs\CustomAlerts\template\Template;
use function array_filter;
use function count;

final readonly class AlertsConfig{
	/**
	 * @param array<string, Template|false> $alerts
	 */
	public function __construct(
		private array $alerts
	){}

	public function getAlert(AlertType $type) : Template|false|null{
		return $this->alerts[$type->value] ?? null;
	}

	public function countAlerts() : int{
		return count(array_filter($this->alerts, static fn(Template|false $alert) : bool => $alert instanceof Template));
	}
}
