<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\alert;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;

final class AlertRenderEvent extends Event implements Cancellable{
	use CancellableTrait;

	public function __construct(
		private readonly AlertContext $context,
		private string                $message
	){}

	public function getContext() : AlertContext{
		return $this->context;
	}

	public function getType() : AlertType{
		return $this->context->getType();
	}

	public function getPlayer() : ?Player{
		return $this->context->getPlayer();
	}

	public function getMessage() : string{
		return $this->message;
	}

	public function setMessage(string $message) : void{
		$this->message = $message;
	}
}
