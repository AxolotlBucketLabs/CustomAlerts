<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\config;

use AxolotlBucketLabs\CustomAlerts\template\Template;

final readonly class PacketOverrides{
	/**
	 * @param array<string, Template> $text
	 * @param array<string, string>   $disconnect
	 */
	public function __construct(
		private array $text,
		private array $disconnect
	){}

	/**
	 * @return array<string, Template>
	 */
	public function getText() : array{
		return $this->text;
	}

	/**
	 * @return array<string, string>
	 */
	public function getDisconnect() : array{
		return $this->disconnect;
	}
}
