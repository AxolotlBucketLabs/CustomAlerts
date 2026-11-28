<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\listener;

use AxolotlBucketLabs\CustomAlerts\alert\AlertContext;
use AxolotlBucketLabs\CustomAlerts\alert\AlertService;
use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;

final readonly class DeathListener implements Listener{
	public function __construct(
		private AlertService $alerts
	){}

	/**
	 * @priority HIGHEST
	 */
	public function onDeath(PlayerDeathEvent $event) : void{
		if($this->alerts->isHidden(AlertType::DeathBroadcast)){
			$event->setDeathMessage("");
			return;
		}

		$player = $event->getPlayer();
		$cause = $player->getLastDamageCause();
		$damager = $cause instanceof EntityDamageByEntityEvent ? $cause->getDamager() : null;
		$killer = match(true){
			$damager instanceof Player => $damager->getDisplayName(),
			$damager instanceof Living => $damager->getName(),
			default => $this->alerts->getKillerFallback()
		};
		$message = $this->alerts->render(AlertContext::forPlayer(AlertType::DeathBroadcast, $player, ["killer" => $killer]));
		if($message !== null){
			$event->setDeathMessage($message);
		}
	}
}
