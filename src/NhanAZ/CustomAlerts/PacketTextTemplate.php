<?php

declare(strict_types=1);

namespace NhanAZ\CustomAlerts;

use function array_key_exists;
use function preg_replace_callback;

final class PacketTextTemplate{
	/**
	 * Returns null if the packet does not contain a parameter referenced by the configured template.
	 *
	 * @param string[] $parameters
	 */
	public static function render(string $template, array $parameters, ?int &$missingParameter = null) : ?string{
		$missingParameter = null;
		$result = preg_replace_callback('/\{([0-9]+)\}/', static function(array $match) use ($parameters, &$missingParameter) : string{
			$index = (int) $match[1];
			if(!array_key_exists($index, $parameters)){
				$missingParameter ??= $index;
				return $match[0];
			}
			return $parameters[$index];
		}, $template);

		return $missingParameter === null && $result !== null ? $result : null;
	}
}
