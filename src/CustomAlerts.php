<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts;

use AxolotlBucketLabs\CustomAlerts\alert\AlertService;
use AxolotlBucketLabs\CustomAlerts\config\ConfigLoader;
use AxolotlBucketLabs\CustomAlerts\config\ConfigLoadResult;
use AxolotlBucketLabs\CustomAlerts\listener\ConnectionListener;
use AxolotlBucketLabs\CustomAlerts\listener\DeathListener;
use AxolotlBucketLabs\CustomAlerts\listener\WorldChangeListener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\ConfigLoadException;

final class CustomAlerts extends PluginBase{
	private ConfigLoader $configLoader;
	private AlertService $alerts;

	protected function onEnable() : void{
		$this->saveDefaultConfig();
		$this->configLoader = new ConfigLoader();

		try{
			$loaded = $this->loadAlertsConfig();
		}catch(ConfigLoadException $e){
			$this->getLogger()->error("Could not load config.yml: " . $e->getMessage());
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$this->alerts = new AlertService($this->getLogger(), $loaded->getConfig());

		$manager = $this->getServer()->getPluginManager();
		$manager->registerEvents(new ConnectionListener($this->alerts), $this);
		$manager->registerEvents(new DeathListener($this->alerts), $this);
		$manager->registerEvents(new WorldChangeListener($this->alerts), $this);
	}

	/**
	 * @throws ConfigLoadException
	 */
	private function loadAlertsConfig() : ConfigLoadResult{
		$this->reloadConfig();
		$result = $this->configLoader->load($this->getConfig()->getAll());
		foreach($result->getWarnings() as $warning){
			$this->getLogger()->warning($warning);
		}
		return $result;
	}
}
