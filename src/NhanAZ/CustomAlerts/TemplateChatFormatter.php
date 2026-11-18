<?php

declare(strict_types=1);

namespace NhanAZ\CustomAlerts;

use pocketmine\player\Player;
use pocketmine\player\chat\ChatFormatter;

final class TemplateChatFormatter implements ChatFormatter{
	public function __construct(
		private string $template,
		private Player $player
	){}

	public function format(string $username, string $message) : string{
		$position = $this->player->getPosition();
		return TemplateRenderer::render($this->template, [
			"player" => $username,
			"display_name" => $this->player->getDisplayName(),
			"message" => $message,
			"world" => $this->player->getWorld()->getFolderName(),
			"x" => (string) $position->getX(),
			"y" => (string) $position->getY(),
			"z" => (string) $position->getZ()
		]);
	}
}
