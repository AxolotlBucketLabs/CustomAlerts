<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\config;

use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use AxolotlBucketLabs\CustomAlerts\template\Template;
use function count;

final readonly class AlertsConfig{
	/**
	 * @param array<string, Template|false> $alerts
	 */
	public function __construct(
		private array                $alerts,
		private string               $killerFallback,
		private PacketOverrides      $packetOverrides,
		private ResourcePackSettings $resourcePack
	){}

	public function getAlert(AlertType $type) : Template|false|null{
		return $this->alerts[$type->value] ?? null;
	}

	public function countAlerts() : int{
		return count($this->alerts);
	}

	public function getKillerFallback() : string{
		return $this->killerFallback;
	}

	public function getPacketOverrides() : PacketOverrides{
		return $this->packetOverrides;
	}

	public function getResourcePack() : ResourcePackSettings{
		return $this->resourcePack;
	}
}
