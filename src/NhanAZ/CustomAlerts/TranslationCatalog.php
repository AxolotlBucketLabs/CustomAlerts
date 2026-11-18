<?php

declare(strict_types=1);

namespace NhanAZ\CustomAlerts;

use JsonException;
use function array_key_exists;
use function count;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;

final class TranslationCatalog{
	/** @var array<string, array{translation_side: string, placeholders: int[], english: string}> */
	private array $entries;

	/** @param array<string, array{translation_side: string, placeholders: int[], english: string}> $entries */
	private function __construct(array $entries){
		$this->entries = $entries;
	}

	/** @throws JsonException */
	public static function fromJson(string $json) : self{
		$data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
		if(!is_array($data) || !isset($data["entries"]) || !is_array($data["entries"])){
			throw new JsonException("Translation catalog has an invalid structure");
		}

		$entries = [];
		foreach(array_keys($data["entries"]) as $key){
			$entry = $data["entries"][$key];
			if(!is_string($key) || !is_array($entry) ||
				!isset($entry["translation_side"], $entry["placeholders"], $entry["english"]) ||
				!is_string($entry["translation_side"]) || !is_array($entry["placeholders"]) || !is_string($entry["english"])
			){
				throw new JsonException("Translation catalog contains an invalid entry");
			}
			foreach($entry["placeholders"] as $placeholder){
				if(!is_int($placeholder)){
					throw new JsonException("Translation catalog contains an invalid placeholder index");
				}
			}
			$entries[$key] = [
				"translation_side" => $entry["translation_side"],
				"placeholders" => $entry["placeholders"],
				"english" => $entry["english"]
			];
		}
		return new self($entries);
	}

	public function isClientKey(string $key) : bool{
		return isset($this->entries[$key]) && $this->entries[$key]["translation_side"] === "client";
	}

	/** @return int[]|null */
	public function getPlaceholders(string $key) : ?array{
		return $this->entries[$key]["placeholders"] ?? null;
	}

	public function count() : int{
		return count($this->entries);
	}

	public function hasKey(string $key) : bool{
		return array_key_exists($key, $this->entries);
	}
}
