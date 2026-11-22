<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts;

use AxolotlBucketLabs\CustomAlerts\alert\AlertService;
use AxolotlBucketLabs\CustomAlerts\catalog\TranslationCatalog;
use AxolotlBucketLabs\CustomAlerts\command\CustomAlertsCommand;
use AxolotlBucketLabs\CustomAlerts\config\AlertsConfig;
use AxolotlBucketLabs\CustomAlerts\config\ConfigException;
use AxolotlBucketLabs\CustomAlerts\config\ConfigLoader;
use AxolotlBucketLabs\CustomAlerts\config\ConfigLoadResult;
use AxolotlBucketLabs\CustomAlerts\listener\ChatListener;
use AxolotlBucketLabs\CustomAlerts\listener\ConnectionListener;
use AxolotlBucketLabs\CustomAlerts\listener\DeathListener;
use AxolotlBucketLabs\CustomAlerts\listener\PacketListener;
use AxolotlBucketLabs\CustomAlerts\listener\PluginDisableListener;
use AxolotlBucketLabs\CustomAlerts\listener\ResourcePackListener;
use AxolotlBucketLabs\CustomAlerts\pack\LanguagePackBuilder;
use AxolotlBucketLabs\CustomAlerts\pack\LanguagePackLoader;
use AxolotlBucketLabs\CustomAlerts\pack\LoadedLanguagePack;
use AxolotlBucketLabs\CustomAlerts\packet\DisconnectPacketRewriter;
use AxolotlBucketLabs\CustomAlerts\packet\TextPacketRewriter;
use AxolotlBucketLabs\CustomAlerts\template\PlaceholderRegistry;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\ConfigLoadException;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function file_get_contents;
use function is_file;

final class CustomAlerts extends PluginBase{
	private const DEFAULT_LOCALE_FILE = "translations/vi_VN.ini";

	private PlaceholderRegistry $placeholders;
	private TranslationCatalog $catalog;
	private ConfigLoader $configLoader;
	private AlertService $alerts;
	private PacketListener $packetListener;
	private ResourcePackListener $resourcePackListener;
	private LanguagePackLoader $languagePacks;

	protected function onLoad() : void{
		$this->placeholders = new PlaceholderRegistry();
	}

	/**
	 * @throws \JsonException
	 */
	protected function onEnable() : void{
		$this->saveDefaultConfig();
		if(!is_file(Path::join($this->getDataFolder(), self::DEFAULT_LOCALE_FILE)) && !$this->saveResource(self::DEFAULT_LOCALE_FILE)){
			throw new RuntimeException("Required " . self::DEFAULT_LOCALE_FILE . " plugin resource is missing");
		}
		$catalogJson = file_get_contents($this->getResourcePath("translation-catalog.json"));
		if($catalogJson === false){
			throw new RuntimeException("Required translation-catalog.json plugin resource is missing");
		}
		$this->catalog = TranslationCatalog::fromJson($catalogJson);
		$this->configLoader = new ConfigLoader($this->catalog);

		try{
			$config = $this->loadAlertsConfig()->getConfig();
		}catch(ConfigLoadException|ConfigException $e){
			$this->getLogger()->error("Could not load config.yml: " . $e->getMessage());
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$this->alerts = new AlertService($this->placeholders, $this->getLogger(), $config);
		$this->packetListener = new PacketListener();
		$this->resourcePackListener = new ResourcePackListener();
		$this->languagePacks = new LanguagePackLoader(
			$this->catalog,
			new LanguagePackBuilder($this->getDataFolder()),
			Path::join($this->getDataFolder(), "translations"),
			$this->getLogger()
		);
		$this->applyConfig($config);

		$manager = $this->getServer()->getPluginManager();
		$manager->registerEvents(new ConnectionListener($this->alerts), $this);
		$manager->registerEvents(new ChatListener($this->alerts), $this);
		$manager->registerEvents(new DeathListener($this->alerts), $this);
		$manager->registerEvents($this->packetListener, $this);
		$manager->registerEvents($this->resourcePackListener, $this);
		$manager->registerEvents(new PluginDisableListener($this->placeholders), $this);

		$this->getServer()->getCommandMap()->register($this->getName(), new CustomAlertsCommand($this));
	}

	public function getPlaceholders() : PlaceholderRegistry{
		return $this->placeholders;
	}

	public function reloadAlerts() : ReloadResult{
		try{
			$loaded = $this->loadAlertsConfig();
		}catch(ConfigLoadException|ConfigException $e){
			$this->getLogger()->error("Could not load config.yml: " . $e->getMessage());
			return ReloadResult::failed($e->getMessage());
		}
		$config = $loaded->getConfig();
		return ReloadResult::loaded($config, $loaded->getWarnings(), $this->applyConfig($config));
	}

	/**
	 * @throws ConfigLoadException|ConfigException
	 */
	private function loadAlertsConfig() : ConfigLoadResult{
		$this->reloadConfig();
		$result = $this->configLoader->load($this->getConfig()->getAll());
		foreach($result->getWarnings() as $warning){
			$this->getLogger()->warning($warning);
		}
		return $result;
	}

	private function applyConfig(AlertsConfig $config) : ?LoadedLanguagePack{
		$this->alerts->setConfig($config);

		$overrides = $config->getPacketOverrides();
		$rewriters = [];
		if($overrides->getText() !== []){
			$rewriters[] = new TextPacketRewriter($overrides->getText(), $this->getLogger());
		}
		if($overrides->getDisconnect() !== []){
			$rewriters[] = new DisconnectPacketRewriter($overrides->getDisconnect());
		}
		$this->packetListener->setRewriters($rewriters);

		$settings = $config->getResourcePack();
		$languagePack = $this->languagePacks->load($settings);
		$this->resourcePackListener->setPack($languagePack?->getPack(), $settings->isRequired());
		return $languagePack;
	}
}
