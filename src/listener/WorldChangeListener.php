<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\listener;

use AxolotlBucketLabs\CustomAlerts\alert\AlertContext;
use AxolotlBucketLabs\CustomAlerts\alert\AlertService;
use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

final readonly class WorldChangeListener implements Listener{
	public function __construct(
		private AlertService $alerts
	){}

	/**
	 * @priority MONITOR
	 */
	public function onTeleport(EntityTeleportEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$player = $event->getEntity();
		if(!$player instanceof Player){
			return;
		}

		$origin = $event->getFrom()->getWorld();
		$target = $event->getTo()->getWorld();
		if($origin === $target){
			return;
		}

		$context = AlertContext::forPlayer(AlertType::WorldChange, $player, [
			"ORIGIN" => $origin->getDisplayName(),
			"TARGET" => $target->getDisplayName()
		]);
		$message = $this->alerts->render($context);
		if($message !== null){
			$player->getServer()->broadcastMessage($message);
		}
	}
}
