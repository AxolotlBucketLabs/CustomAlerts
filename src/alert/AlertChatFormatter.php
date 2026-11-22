<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use pocketmine\lang\Translatable;
use pocketmine\player\chat\ChatFormatter;
use pocketmine\player\Player;

final readonly class AlertChatFormatter implements ChatFormatter{
	public function __construct(
		private AlertService  $alerts,
		private Player        $player,
		private ChatFormatter $fallback
	){}

	public function format(string $username, string $message) : Translatable|string{
		return $this->alerts->render(AlertContext::forPlayer(AlertType::Chat, $this->player, ["message" => $message])) ?? $this->fallback->format($username, $message);
	}
}
