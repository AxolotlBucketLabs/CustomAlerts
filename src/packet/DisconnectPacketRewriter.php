<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\packet;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;

final readonly class DisconnectPacketRewriter implements PacketRewriter{
	/**
	 * @param array<string, string> $overrides
	 */
	public function __construct(
		private array $overrides
	){}

	public function rewrite(ClientboundPacket $packet) : ?ClientboundPacket{
		if(!$packet instanceof DisconnectPacket || $packet->message === null){
			return null;
		}
		$replacement = $this->overrides[$packet->message] ?? null;
		if($replacement === null){
			return null;
		}
		return DisconnectPacket::create($packet->reason, $replacement, $packet->filteredMessage);
	}
}
