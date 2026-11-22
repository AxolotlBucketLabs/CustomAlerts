<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerResourcePackOfferEvent;
use pocketmine\resourcepacks\ResourcePack;

final class ResourcePackListener implements Listener{
	private ?ResourcePack $pack = null;
	private bool $required = false;

	public function setPack(?ResourcePack $pack, bool $required) : void{
		$this->pack = $pack;
		$this->required = $required;
	}

	public function onResourcePackOffer(PlayerResourcePackOfferEvent $event) : void{
		if($this->pack === null){
			return;
		}
		$event->addResourcePack($this->pack);
		if($this->required){
			$event->setMustAccept(true);
		}
	}
}
