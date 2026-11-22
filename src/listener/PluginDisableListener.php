<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\listener;

use AxolotlBucketLabs\CustomAlerts\template\PlaceholderRegistry;
use pocketmine\event\Listener;
use pocketmine\event\plugin\PluginDisableEvent;

//This is for other plugins disabling
final readonly class PluginDisableListener implements Listener{
	public function __construct(
		private PlaceholderRegistry $placeholders
	){}

	public function onPluginDisable(PluginDisableEvent $event) : void{
		$this->placeholders->unregisterAll($event->getPlugin());
	}
}
