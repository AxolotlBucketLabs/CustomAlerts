<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\catalog;

use JsonException;
use function count;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function sort;

final readonly class TranslationCatalog{
	/**
	 * @param array<string, CatalogEntry> $entries
	 */
	private function __construct(
		private array $entries
	){}

	/**
	 * @throws JsonException
	 */
	public static function fromJson(string $json) : self{
		$data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
		if(!is_array($data) || !isset($data["entries"]) || !is_array($data["entries"])){
			throw new JsonException("Translation catalog has an invalid structure");
		}

		$entries = [];
		foreach($data["entries"] as $key => $entry){
			if(!is_array($entry) || !is_string($entry["translation_side"] ?? null) || !is_array($entry["placeholders"] ?? null) || !is_string($entry["english"] ?? null)){
				throw new JsonException("Translation catalog entry '$key' is invalid");
			}
			$side = TranslationSide::tryFrom($entry["translation_side"]);
			if($side === null){
				throw new JsonException("Translation catalog entry '$key' has an unknown translation side");
			}
			$placeholders = [];
			foreach($entry["placeholders"] as $placeholder){
				if(!is_int($placeholder)){
					throw new JsonException("Translation catalog entry '$key' has an invalid placeholder index");
				}
				$placeholders[] = $placeholder;
			}
			sort($placeholders);
			$key = (string) $key;
			$entries[$key] = new CatalogEntry($key, $side, $placeholders, $entry["english"]);
		}
		return new self($entries);
	}

	public function get(string $key) : ?CatalogEntry{
		return $this->entries[$key] ?? null;
	}

	public function has(string $key) : bool{
		return isset($this->entries[$key]);
	}

	public function isClientKey(string $key) : bool{
		return ($this->entries[$key] ?? null)?->getSide() === TranslationSide::Client;
	}

	public function count() : int{
		return count($this->entries);
	}
}
