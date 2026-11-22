<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use function in_array;

enum AlertType : string{
	case Join = "messages.join";
	case Quit = "messages.quit";
	case Chat = "messages.chat";
	case DeathBroadcast = "messages.death.broadcast";
	case DeathScreen = "messages.death.screen";
	case KickBroadcast = "messages.kick.broadcast";
	case KickReason = "messages.kick.reason";
	case KickScreen = "messages.kick.screen";
	case Login = "messages.login";
	case ServerFullReason = "messages.pre-login.server-full.reason";
	case ServerFullScreen = "messages.pre-login.server-full.screen";
	case WhitelistReason = "messages.pre-login.whitelist.reason";
	case WhitelistScreen = "messages.pre-login.whitelist.screen";
	case BannedReason = "messages.pre-login.banned.reason";
	case BannedScreen = "messages.pre-login.banned.screen";

	private const array PLAYER_PLACEHOLDERS = ["player", "display_name", "world", "x", "y", "z"];

	/**
	 * @return list<string>
	 */
	public function getPlaceholders() : array{
		return match($this){
			self::Join => self::PLAYER_PLACEHOLDERS,
			self::Quit, self::Login => [...self::PLAYER_PLACEHOLDERS, "reason"],
			self::Chat => [...self::PLAYER_PLACEHOLDERS, "message"],
			self::DeathBroadcast, self::DeathScreen => [...self::PLAYER_PLACEHOLDERS, "killer"],
			self::KickBroadcast, self::KickReason, self::KickScreen => [...self::PLAYER_PLACEHOLDERS, "reason", "screen"],
			self::ServerFullReason, self::ServerFullScreen, self::WhitelistReason, self::WhitelistScreen, self::BannedReason, self::BannedScreen => ["player"]
		};
	}

	public function isHideable() : bool{
		return match($this){
			self::Join, self::Quit, self::Chat, self::DeathBroadcast, self::KickBroadcast => true,
			default => false
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
