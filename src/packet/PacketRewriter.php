<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\packet;

use pocketmine\network\mcpe\protocol\ClientboundPacket;

interface PacketRewriter{
	public function rewrite(ClientboundPacket $packet) : ?ClientboundPacket;
}
