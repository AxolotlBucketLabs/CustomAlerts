<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\listener;

use AxolotlBucketLabs\CustomAlerts\alert\AlertChatFormatter;
use AxolotlBucketLabs\CustomAlerts\alert\AlertService;
use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;

final readonly class ChatListener implements Listener{
	public function __construct(
		private AlertService $alerts
	){}

	/**
	 * @priority HIGHEST
	 */
	public function onChat(PlayerChatEvent $event) : void{
		if($this->alerts->isHidden(AlertType::Chat)){
			$event->cancel();
			return;
		}
		if($this->alerts->isConfigured(AlertType::Chat)){
			$event->setFormatter(new AlertChatFormatter($this->alerts, $event->getPlayer(), $event->getFormatter()));
		}
	}
}
