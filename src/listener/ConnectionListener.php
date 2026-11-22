<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\listener;

use AxolotlBucketLabs\CustomAlerts\alert\AlertContext;
use AxolotlBucketLabs\CustomAlerts\alert\AlertService;
use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use function spl_object_id;

final class ConnectionListener implements Listener{
	private const array PRE_LOGIN_ALERTS = [
		PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL => [AlertType::ServerFullReason, AlertType::ServerFullScreen],
		PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED => [AlertType::WhitelistReason, AlertType::WhitelistScreen],
		PlayerPreLoginEvent::KICK_FLAG_BANNED => [AlertType::BannedReason, AlertType::BannedScreen]
	];

	/** @var array<int, string> */
	private array $kickBroadcasts = [];

	public function __construct(
		private readonly AlertService $alerts
	){}

	/**
	 * @priority HIGHEST
	 */
	public function onJoin(PlayerJoinEvent $event) : void{
		if($this->alerts->isHidden(AlertType::Join)){
			$event->setJoinMessage("");
			return;
		}
		$message = $this->alerts->render(AlertContext::forPlayer(AlertType::Join, $event->getPlayer()));
		if($message !== null){
			$event->setJoinMessage($message);
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		$kickBroadcast = $this->kickBroadcasts[spl_object_id($player)] ?? null;
		unset($this->kickBroadcasts[spl_object_id($player)]);
		if($kickBroadcast !== null && $kickBroadcast === $event->getQuitMessage()){
			return;
		}

		if($this->alerts->isHidden(AlertType::Quit)){
			$event->setQuitMessage("");
			return;
		}
		$message = $this->alerts->render(AlertContext::forPlayer(AlertType::Quit, $player, [
			"reason" => self::translate($player, $event->getQuitReason())
		]));
		if($message !== null){
			$event->setQuitMessage($message);
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onKick(PlayerKickEvent $event) : void{
		$player = $event->getPlayer();
		$values = [
			"reason" => self::translate($player, $event->getDisconnectReason()),
			"screen" => self::translate($player, $event->getDisconnectScreenMessage())
		];

		$broadcast = $this->alerts->isHidden(AlertType::KickBroadcast) ? "" : $this->alerts->render(AlertContext::forPlayer(AlertType::KickBroadcast, $player, $values));
		if($broadcast !== null){
			$event->setQuitMessage($broadcast);
			$this->kickBroadcasts[spl_object_id($player)] = $broadcast;
		}
		$reason = $this->alerts->render(AlertContext::forPlayer(AlertType::KickReason, $player, $values));
		if($reason !== null){
			$event->setDisconnectReason($reason);
		}
		$screen = $this->alerts->render(AlertContext::forPlayer(AlertType::KickScreen, $player, $values));
		if($screen !== null){
			$event->setDisconnectScreenMessage($screen);
		}
	}

	/**
	 * @priority HIGHEST
	 * @handleCancelled
	 */
	public function onLogin(PlayerLoginEvent $event) : void{
		if(!$event->isCancelled()){
			return;
		}
		$player = $event->getPlayer();
		$message = $this->alerts->render(AlertContext::forPlayer(AlertType::Login, $player, [
			"reason" => self::translate($player, $event->getKickMessage())
		]));
		if($message !== null){
			$event->setKickMessage($message);
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPreLogin(PlayerPreLoginEvent $event) : void{
		$playerName = $event->getPlayerInfo()->getUsername();
		foreach(self::PRE_LOGIN_ALERTS as $flag => [$reasonType, $screenType]){
			$oldReason = $event->getDisconnectReason($flag);
			if(!$event->isKickFlagSet($flag) || $oldReason === null){
				continue;
			}
			$reason = $this->alerts->render(AlertContext::forPlayerName($reasonType, $playerName));
			$screen = $this->alerts->render(AlertContext::forPlayerName($screenType, $playerName));
			if($reason === null && $screen === null){
				continue;
			}
			$event->setKickFlag($flag, $reason ?? $oldReason, $screen ?? $event->getDisconnectScreenMessage($flag) ?? $oldReason);
		}
	}

	private static function translate(Player $player, Translatable|string|null $message) : string{
		if($message instanceof Translatable){
			return $player->getLanguage()->translate($message);
		}
		return $message ?? "";
	}
}
