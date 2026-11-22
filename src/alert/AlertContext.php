<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use pocketmine\player\Player;

final readonly class AlertContext{
	/**
	 * @param array<string, string> $values
	 */
	public function __construct(
		private AlertType $type,
		private string    $playerName,
		private ?Player   $player,
		private array     $values
	){}

	/**
	 * @param array<string, string> $values
	 */
	public static function forPlayer(AlertType $type, Player $player, array $values = []) : self{
		$position = $player->getPosition();
		return new self($type, $player->getName(), $player, $values + [
			"player" => $player->getName(),
			"display_name" => $player->getDisplayName(),
			"world" => $player->getWorld()->getFolderName(),
			"x" => (string) $position->getFloorX(),
			"y" => (string) $position->getFloorY(),
			"z" => (string) $position->getFloorZ()
		]);
	}

	public static function forPlayerName(AlertType $type, string $playerName) : self{
		return new self($type, $playerName, null, ["player" => $playerName]);
	}

	public function getType() : AlertType{
		return $this->type;
	}

	public function getPlayerName() : string{
		return $this->playerName;
	}

	public function getPlayer() : ?Player{
		return $this->player;
	}

	public function getValue(string $name) : ?string{
		return $this->values[$name] ?? null;
	}

	/**
	 * @return array<string, string>
	 */
	public function getValues() : array{
		return $this->values;
	}
}
