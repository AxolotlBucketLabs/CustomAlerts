<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use pocketmine\player\Player;

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
		$position = $player->getPosition();
		return new self($type, $values + [
			"player" => $player->getName(),
			"display_name" => $player->getDisplayName(),
			"world" => $player->getWorld()->getFolderName(),
			"x" => (string) $position->getFloorX(),
			"y" => (string) $position->getFloorY(),
			"z" => (string) $position->getFloorZ()
		]);
	}

	public function getType() : AlertType{
		return $this->type;
	}

	public function getValue(string $name) : ?string{
		return $this->values[$name] ?? null;
	}
}
