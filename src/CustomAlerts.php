<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts;

use AxolotlBucketLabs\CustomAlerts\alert\AlertConfig;
use AxolotlBucketLabs\CustomAlerts\alert\AlertService;
use AxolotlBucketLabs\CustomAlerts\listener\ConnectionListener;
use AxolotlBucketLabs\CustomAlerts\listener\DeathListener;
use AxolotlBucketLabs\CustomAlerts\listener\WorldChangeListener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\ConfigLoadException;

final class CustomAlerts extends PluginBase{
	protected function onEnable() : void{
		try{
			$config = AlertConfig::from($this->getConfig()->getAll());
		}catch(ConfigLoadException $e){
			$this->getLogger()->error("Could not load config.yml: " . $e->getMessage());
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		foreach($config->getWarnings() as $warning){
			$this->getLogger()->warning($warning);
		}

		$alerts = new AlertService($this->getLogger(), $config);

		$manager = $this->getServer()->getPluginManager();
		$manager->registerEvents(new ConnectionListener($alerts), $this);
		$manager->registerEvents(new DeathListener($alerts), $this);
		$manager->registerEvents(new WorldChangeListener($alerts), $this);
	}
}
