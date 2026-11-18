<?php

declare(strict_types=1);

namespace NhanAZ\CustomAlerts;

use RuntimeException;
use function array_key_exists;
use function ltrim;
use function preg_replace;
use function preg_split;
use function strpos;
use function str_starts_with;
use function substr;
use function trim;

final class TranslationFileParser{
	/** @return array<string, string> */
	public static function parse(string $contents) : array{
		$contents = preg_replace('/^\xEF\xBB\xBF/', "", $contents, 1) ?? $contents;
		$lines = preg_split('/\r\n|\r|\n/', $contents);
		if($lines === false){
			throw new RuntimeException("Could not split translation file into lines");
		}

		$entries = [];
		foreach($lines as $lineNumber => $line){
			$trimmedLine = ltrim($line);
			if(trim($line) === "" || str_starts_with($trimmedLine, ";") || str_starts_with($trimmedLine, "#")){
				continue;
			}

			$separator = strpos($line, "=");
			if($separator === false || $separator === 0){
				throw new RuntimeException("Invalid translation entry on line " . ($lineNumber + 1));
			}
			$key = substr($line, 0, $separator);
			if(array_key_exists($key, $entries)){
				throw new RuntimeException("Duplicate translation key '$key' on line " . ($lineNumber + 1));
			}
			$entries[$key] = substr($line, $separator + 1);
		}

		return $entries;
	}
}
