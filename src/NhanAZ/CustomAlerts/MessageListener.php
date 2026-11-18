<?php

declare(strict_types=1);

namespace NhanAZ\CustomAlerts;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use function is_string;

final class MessageListener implements Listener{
	public function __construct(private Main $plugin){}

	/** @priority HIGHEST */
	public function onJoin(PlayerJoinEvent $event) : void{
		$settings = $this->plugin->section("messages.join");
		if(($settings["enabled"] ?? false) !== true){
			return;
		}
		if(($settings["hide"] ?? false) === true){
			$event->setJoinMessage("");
			return;
		}
		if(is_string($settings["format"] ?? null)){
			$event->setJoinMessage(TemplateRenderer::render($settings["format"], $this->playerValues($event->getPlayer())));
		}
	}

	/** @priority HIGHEST */
	public function onQuit(PlayerQuitEvent $event) : void{
		$settings = $this->plugin->section("messages.quit");
		if(($settings["enabled"] ?? false) !== true){
			return;
		}
		if(($settings["hide"] ?? false) === true){
			$event->setQuitMessage("");
			return;
		}
		if(is_string($settings["format"] ?? null)){
			$values = $this->playerValues($event->getPlayer());
			$values["reason"] = $this->toString($event->getPlayer(), $event->getQuitReason());
			$event->setQuitMessage(TemplateRenderer::render($settings["format"], $values));
		}
	}

	/** @priority HIGHEST */
	public function onChat(PlayerChatEvent $event) : void{
		$settings = $this->plugin->section("messages.chat");
		if(($settings["enabled"] ?? false) !== true){
			return;
		}
		if(($settings["hide"] ?? false) === true){
			$event->cancel();
			return;
		}
		$template = $settings["format"] ?? null;
		if(is_string($template)){
			$event->setFormatter(new TemplateChatFormatter($template, $event->getPlayer()));
		}
	}

	/** @priority HIGHEST */
	public function onDeath(PlayerDeathEvent $event) : void{
		$settings = $this->plugin->section("messages.death");
		if(($settings["enabled"] ?? false) !== true){
			return;
		}
		$player = $event->getPlayer();
		$values = $this->playerValues($player);
		$damageCause = $player->getLastDamageCause();
		$damager = $damageCause instanceof EntityDamageByEntityEvent ? $damageCause->getDamager() : null;
		$values["killer"] = $damager instanceof Player ? $damager->getDisplayName() : "environment";
		if(is_string($settings["broadcast"] ?? null)){
			$event->setDeathMessage(TemplateRenderer::render($settings["broadcast"], $values));
		}
		if(is_string($settings["screen"] ?? null)){
			$event->setDeathScreenMessage(TemplateRenderer::render($settings["screen"], $values));
		}
	}

	/** @priority HIGHEST */
	public function onKick(PlayerKickEvent $event) : void{
		if($event->isCancelled()){
			return;
		}
		$settings = $this->plugin->section("messages.kick");
		if(($settings["enabled"] ?? false) !== true){
			return;
		}
		$player = $event->getPlayer();
		$values = $this->playerValues($player);
		$values["reason"] = $this->toString($player, $event->getDisconnectReason());
		$values["screen"] = $this->toString($player, $event->getDisconnectScreenMessage() ?? $event->getDisconnectReason());
		if(is_string($settings["broadcast"] ?? null)){
			$event->setQuitMessage(TemplateRenderer::render($settings["broadcast"], $values));
		}
		if(is_string($settings["reason"] ?? null)){
			$event->setDisconnectReason(TemplateRenderer::render($settings["reason"], $values));
		}
		if(is_string($settings["screen"] ?? null)){
			$event->setDisconnectScreenMessage(TemplateRenderer::render($settings["screen"], $values));
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
		$settings = $this->plugin->section("messages.login");
		if(($settings["enabled"] ?? false) !== true || !is_string($settings["message"] ?? null)){
			return;
		}
		$player = $event->getPlayer();
		$values = $this->playerValues($player);
		$values["reason"] = $this->toString($player, $event->getKickMessage());
		$event->setKickMessage(TemplateRenderer::render($settings["message"], $values));
	}

	/** @priority HIGHEST */
	public function onPreLogin(PlayerPreLoginEvent $event) : void{
		$playerName = $event->getPlayerInfo()->getUsername();
		$checks = [
			PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL => "server-full",
			PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED => "whitelist",
			PlayerPreLoginEvent::KICK_FLAG_BANNED => "banned"
		];
		foreach($checks as $flag => $name){
			if(!$event->isKickFlagSet($flag)){
				continue;
			}
			$settings = $this->plugin->section("messages.pre-login.$name");
			if(($settings["enabled"] ?? false) !== true){
				continue;
			}
			$values = ["player" => $playerName];
			$oldReason = $event->getDisconnectReason($flag);
			$oldScreen = $event->getDisconnectScreenMessage($flag);
			$reason = is_string($settings["reason"] ?? null) ? TemplateRenderer::render($settings["reason"], $values) : $this->translateServerMessage($oldReason);
			$screen = is_string($settings["screen"] ?? null) ? TemplateRenderer::render($settings["screen"], $values) : $this->translateServerMessage($oldScreen ?? $oldReason);
			$event->setKickFlag($flag, $reason, $screen);
		}
	}

	/** @return array<string, string> */
	private function playerValues(Player $player) : array{
		$position = $player->getPosition();
		return [
			"player" => $player->getName(),
			"display_name" => $player->getDisplayName(),
			"world" => $player->getWorld()->getFolderName(),
			"x" => (string) $position->getX(),
			"y" => (string) $position->getY(),
			"z" => (string) $position->getZ()
		];
	}

	private function toString(Player $player, Translatable|string|null $value) : string{
		if($value === null){
			return "";
		}
		return $value instanceof Translatable ? $player->getLanguage()->translate($value) : $value;
	}

	private function translateServerMessage(Translatable|string|null $value) : string{
		return $value instanceof Translatable ? $this->plugin->getServer()->getLanguage()->translate($value) : ($value ?? "");
	}
}
