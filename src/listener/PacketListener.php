<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\listener;

use AxolotlBucketLabs\CustomAlerts\packet\PacketRewriter;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;

final class PacketListener implements Listener{
	/** @var list<PacketRewriter> */
	private array $rewriters = [];

	/**
	 * @param list<PacketRewriter> $rewriters
	 */
	public function setRewriters(array $rewriters) : void{
		$this->rewriters = $rewriters;
	}

	public function onPacketSend(DataPacketSendEvent $event) : void{
		if($this->rewriters === []){
			return;
		}

		$packets = $event->getPackets();
		$changed = false;
		foreach($packets as $index => $packet){
			foreach($this->rewriters as $rewriter){
				$replacement = $rewriter->rewrite($packet);
				if($replacement !== null){
					$packets[$index] = $replacement;
					$changed = true;
					break;
				}
			}
		}
		if($changed){
			$event->setPackets($packets);
		}
	}
}
