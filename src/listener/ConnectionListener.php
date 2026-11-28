<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\listener;

use AxolotlBucketLabs\CustomAlerts\alert\AlertContext;
use AxolotlBucketLabs\CustomAlerts\alert\AlertService;
use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;
use function spl_object_id;

final class ConnectionListener implements Listener{
	/** @var array<int, string> */
	private array $kickBroadcasts = [];

	public function __construct(
		private readonly AlertService $alerts
	){}

	/**
	 * @priority HIGHEST
	 */
	public function onJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$type = $player->hasPlayedBefore() ? AlertType::Join : AlertType::FirstJoin;
		$message = $this->alerts->render(AlertContext::forPlayer($type, $player));
		if($message === null && $type === AlertType::FirstJoin){
			$message = $this->alerts->render(AlertContext::forPlayer(AlertType::Join, $player));
		}
		if($message !== null){
			$event->setJoinMessage($message);
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		$playerId = spl_object_id($player);
		$kickBroadcast = $this->kickBroadcasts[$playerId] ?? null;
		unset($this->kickBroadcasts[$playerId]);
		if($kickBroadcast !== null && $kickBroadcast === $event->getQuitMessage()){
			return;
		}

		$message = $this->alerts->render(AlertContext::forPlayer(AlertType::Quit, $player));
		if($message !== null){
			$event->setQuitMessage($message);
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onKick(PlayerKickEvent $event) : void{
		$message = $this->alerts->render(AlertContext::forPlayer(AlertType::KickBroadcast, $event->getPlayer()));
		if($message === null){
			return;
		}

		$event->setQuitMessage($message);
		$this->kickBroadcasts[spl_object_id($event->getPlayer())] = $message;
	}
}
