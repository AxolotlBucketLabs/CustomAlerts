<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use pocketmine\player\Player;
use function count;
use function date;

final readonly class AlertContext{
	/**
	 * @param array<string, string> $values
	 */
	private function __construct(
		private AlertType $type,
		private array $values
	){}

	/**
	 * @param array<string, string> $values
	 */
	public static function forPlayer(AlertType $type, Player $player, array $values = []) : self{
		$server = $player->getServer();
		return new self($type, $values + [
			"PLAYER" => $player->getName(),
			"TIME" => date("H:i:s"),
			"TOTALPLAYERS" => (string) count($server->getOnlinePlayers()),
			"MAXPLAYERS" => (string) $server->getMaxPlayers()
		]);
	}

	public function getType() : AlertType{
		return $this->type;
	}

	public function getValue(string $name) : ?string{
		return $this->values[$name] ?? null;
	}
}
