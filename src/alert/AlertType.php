<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use function in_array;

enum AlertType : string{
	case Join = "messages.join";
	case Quit = "messages.quit";
	case DeathBroadcast = "messages.death.broadcast";
	case KickBroadcast = "messages.kick.broadcast";

	private const array PLAYER_PLACEHOLDERS = ["player", "display_name", "world", "x", "y", "z"];

	/**
	 * @return list<string>
	 */
	public function getPlaceholders() : array{
		return match($this){
			self::Join, self::Quit, self::KickBroadcast => self::PLAYER_PLACEHOLDERS,
			self::DeathBroadcast => [...self::PLAYER_PLACEHOLDERS, "killer"]
		};
	}

	public static function isBuiltInPlaceholder(string $name) : bool{
		foreach(self::cases() as $type){
			if(in_array($name, $type->getPlaceholders(), true)){
				return true;
			}
		}
		return false;
	}
}
