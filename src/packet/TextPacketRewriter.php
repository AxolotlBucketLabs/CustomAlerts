<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\packet;

use AxolotlBucketLabs\CustomAlerts\template\Template;
use Logger;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\TextPacket;

final class TextPacketRewriter implements PacketRewriter{
	/** @var array<string, true> */
	private array $reportedMissingParameters = [];

	/**
	 * @param array<string, Template> $overrides
	 */
	public function __construct(
		private readonly array $overrides,
		private readonly Logger $logger
	){}

	public function rewrite(ClientboundPacket $packet) : ?ClientboundPacket{
		if(!$packet instanceof TextPacket || !$packet->needsTranslation){
			return null;
		}
		$template = $this->overrides[$packet->message] ?? null;
		if($template === null){
			return null;
		}

		$missing = $template->findMissingIndex($packet->parameters);
		if($missing !== null){
			$this->reportMissingParameter($packet->message, $missing);
			return null;
		}
		$message = $template->renderIndexed($packet->parameters);

		$replacement = match($packet->type){
			TextPacket::TYPE_TRANSLATION => TextPacket::raw($message),
			TextPacket::TYPE_POPUP => TextPacket::popup($message),
			TextPacket::TYPE_JUKEBOX_POPUP => TextPacket::jukeboxPopup($message),
			default => null
		};
		if($replacement === null){
			return null;
		}
		$replacement->xboxUserId = $packet->xboxUserId;
		$replacement->platformChatId = $packet->platformChatId;
		return $replacement;
	}

	private function reportMissingParameter(string $key, int $index) : void{
		if(isset($this->reportedMissingParameters["$key:$index"])){
			return;
		}
		$this->reportedMissingParameters["$key:$index"] = true;
		$this->logger->warning("Skipped packet override for '$key': packet parameter {%$index} was not supplied.");
	}
}
