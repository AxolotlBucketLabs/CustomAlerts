<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\listener;

use AxolotlBucketLabs\CustomAlerts\alert\AlertContext;
use AxolotlBucketLabs\CustomAlerts\alert\AlertService;
use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;

final readonly class DeathListener implements Listener{
	public function __construct(
		private AlertService $alerts
	){}

	/**
	 * @priority HIGHEST
	 */
	public function onDeath(PlayerDeathEvent $event) : void{
		$player = $event->getPlayer();
		$cause = $player->getLastDamageCause();
		$type = self::getAlertType($cause);
		$values = [];

		if($type === AlertType::DeathContact){
			if(!$cause instanceof EntityDamageByBlockEvent){
				$type = AlertType::Death;
			}else{
				$values["BLOCK"] = $cause->getDamager()->getName();
			}
		}elseif($type === AlertType::DeathKill || $type === AlertType::DeathProjectile){
			$damager = $cause instanceof EntityDamageByEntityEvent ? $cause->getDamager() : null;
			if($damager instanceof Projectile){
				$damager = $damager->getOwningEntity();
			}
			if($damager instanceof Living){
				$values["KILLER"] = $damager->getName();
			}else{
				$type = AlertType::Death;
			}
		}

		$message = $this->alerts->render(AlertContext::forPlayer($type, $player, $values));
		if($message === null && $type !== AlertType::Death){
			$message = $this->alerts->render(AlertContext::forPlayer(AlertType::Death, $player));
		}
		if($message !== null){
			$event->setDeathMessage($message);
		}
	}

	private static function getAlertType(?EntityDamageEvent $cause) : AlertType{
		if($cause === null){
			return AlertType::Death;
		}

		return match($cause->getCause()){
			EntityDamageEvent::CAUSE_CONTACT => AlertType::DeathContact,
			EntityDamageEvent::CAUSE_ENTITY_ATTACK => AlertType::DeathKill,
			EntityDamageEvent::CAUSE_PROJECTILE => AlertType::DeathProjectile,
			EntityDamageEvent::CAUSE_SUFFOCATION => AlertType::DeathSuffocation,
			EntityDamageEvent::CAUSE_FALL => AlertType::DeathFall,
			EntityDamageEvent::CAUSE_FIRE => AlertType::DeathFire,
			EntityDamageEvent::CAUSE_FIRE_TICK => AlertType::DeathOnFire,
			EntityDamageEvent::CAUSE_LAVA => AlertType::DeathLava,
			EntityDamageEvent::CAUSE_DROWNING => AlertType::DeathDrowning,
			EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION => AlertType::DeathExplosion,
			EntityDamageEvent::CAUSE_VOID => AlertType::DeathVoid,
			EntityDamageEvent::CAUSE_SUICIDE => AlertType::DeathSuicide,
			EntityDamageEvent::CAUSE_MAGIC => AlertType::DeathMagic,
			default => AlertType::Death
		};
	}
}
