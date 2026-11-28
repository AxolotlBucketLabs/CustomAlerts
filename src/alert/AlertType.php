<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use function in_array;

enum AlertType : string{
	case Join = "messages.join";
	case FirstJoin = "messages.first_join";
	case Quit = "messages.quit";
	case WorldChange = "messages.world_change";
	case KickBroadcast = "messages.kick";
	case Death = "messages.death";
	case DeathContact = "messages.death.contact";
	case DeathKill = "messages.death.kill";
	case DeathProjectile = "messages.death.projectile";
	case DeathSuffocation = "messages.death.suffocation";
	case DeathFall = "messages.death.fall";
	case DeathFire = "messages.death.fire";
	case DeathOnFire = "messages.death.on_fire";
	case DeathLava = "messages.death.lava";
	case DeathDrowning = "messages.death.drowning";
	case DeathExplosion = "messages.death.explosion";
	case DeathVoid = "messages.death.void";
	case DeathSuicide = "messages.death.suicide";
	case DeathMagic = "messages.death.magic";

	private const array COMMON_PLACEHOLDERS = ["PLAYER", "TIME", "TOTALPLAYERS", "MAXPLAYERS"];

	/**
	 * @return list<string>
	 */
	public function getPlaceholders() : array{
		return match($this){
			self::WorldChange => [...self::COMMON_PLACEHOLDERS, "ORIGIN", "TARGET"],
			self::DeathContact => [...self::COMMON_PLACEHOLDERS, "BLOCK"],
			self::DeathKill, self::DeathProjectile => [...self::COMMON_PLACEHOLDERS, "KILLER"],
			default => self::COMMON_PLACEHOLDERS
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
