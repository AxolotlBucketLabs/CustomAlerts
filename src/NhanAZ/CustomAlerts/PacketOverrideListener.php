<?php

declare(strict_types=1);

namespace NhanAZ\CustomAlerts;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use function array_key_exists;

final class PacketOverrideListener implements Listener{
	public function __construct(private Main $plugin){}

	public function onPacketSend(DataPacketSendEvent $event) : void{
		$textOverrides = $this->plugin->getTextOverrides();
		$disconnectOverrides = $this->plugin->getDisconnectOverrides();
		if($textOverrides === [] && $disconnectOverrides === []){
			return;
		}

		$packets = $event->getPackets();
		$changed = false;
		foreach(array_keys($packets) as $index){
			$packet = $packets[$index];
			if($packet instanceof TextPacket && $packet->needsTranslation && isset($textOverrides[$packet->message]) &&
				in_array($packet->type, [TextPacket::TYPE_TRANSLATION, TextPacket::TYPE_POPUP, TextPacket::TYPE_JUKEBOX_POPUP], true)
			){
				$missingParameter = null;
				$replacement = PacketTextTemplate::render($textOverrides[$packet->message], $packet->parameters, $missingParameter);
				if($replacement === null){
					$this->plugin->warnMissingPacketParameter($packet->message, $missingParameter ?? -1);
					continue;
				}
				$raw = TextPacket::raw($replacement);
				$packetProperties = get_object_vars($packet);
				$raw->xboxUserId = $packetProperties["xboxUserId"] ?? "";
				$raw->platformChatId = $packetProperties["platformChatId"] ?? "";
				$raw->filteredMessage = $packet->filteredMessage;
				$packets[$index] = $raw;
				$changed = true;
				continue;
			}

			if($packet instanceof DisconnectPacket && $packet->message !== null && array_key_exists($packet->message, $disconnectOverrides)){
				$packets[$index] = DisconnectPacket::create($packet->reason, $disconnectOverrides[$packet->message], $packet->filteredMessage);
				$changed = true;
			}
		}

		if($changed){
			/** @var ClientboundPacket[] $packets */
			$event->setPackets($packets);
		}
	}
}
