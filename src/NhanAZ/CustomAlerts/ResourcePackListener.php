<?php

declare(strict_types=1);

namespace NhanAZ\CustomAlerts;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerResourcePackOfferEvent;

final class ResourcePackListener implements Listener{
	public function __construct(private Main $plugin){}

	public function onResourcePackOffer(PlayerResourcePackOfferEvent $event) : void{
		$pack = $this->plugin->getResourcePack();
		if($pack === null){
			return;
		}
		$event->addResourcePack($pack);
		if($this->plugin->resourcePackIsRequired()){
			$event->setMustAccept(true);
		}
	}
}
