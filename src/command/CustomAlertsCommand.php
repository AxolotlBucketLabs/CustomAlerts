<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\command;

use AxolotlBucketLabs\CustomAlerts\CustomAlerts;
use AxolotlBucketLabs\CustomAlerts\ReloadResult;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use function array_slice;
use function count;
use function strtolower;

final class CustomAlertsCommand extends Command implements PluginOwned{
	public const string PERMISSION_RELOAD = "customalerts.command.reload";
	private const int MAX_LISTED_WARNINGS = 10;

	public function __construct(
		private readonly CustomAlerts $plugin
	){
		parent::__construct("customalerts", "Reload CustomAlerts configuration", "/customalerts reload", ["ca"]);
		$this->setPermission(self::PERMISSION_RELOAD);
	}

	public function getOwningPlugin() : Plugin{
		return $this->plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if(count($args) !== 1 || strtolower($args[0]) !== "reload"){
			throw new InvalidCommandSyntaxException();
		}

		$result = $this->plugin->reloadAlerts();
		if(!$result->isSuccessful()){
			$sender->sendMessage("§8[§cCustomAlerts§8] §7Could not load the configuration. The previous configuration is still in use.");
			$sender->sendMessage("§c" . $result->getError());
			return;
		}

		$sender->sendMessage("§8[§aCustomAlerts§8] §7Configuration reloaded.");
		$this->sendSummary($sender, $result);
		$this->sendWarnings($sender, $result->getWarnings());
	}

	private function sendSummary(CommandSender $sender, ReloadResult $result) : void{
		$config = $result->getConfig();
		if($config !== null){
			$sender->sendMessage("§7Configured broadcasts: §f" . $config->countAlerts());
		}
	}

	/**
	 * @param list<string> $warnings
	 */
	private function sendWarnings(CommandSender $sender, array $warnings) : void{
		if($warnings === []){
			return;
		}
		$sender->sendMessage("§e" . count($warnings) . " warning(s):");
		foreach(array_slice($warnings, 0, self::MAX_LISTED_WARNINGS) as $warning){
			$sender->sendMessage("§8- §7" . $warning);
		}
		$remaining = count($warnings) - self::MAX_LISTED_WARNINGS;
		if($remaining > 0){
			$sender->sendMessage("§7... and §f" . $remaining . " §7more, see the console.");
		}
	}
}
