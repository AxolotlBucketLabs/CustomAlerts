<?php

declare(strict_types=1);

namespace NhanAZ\CustomAlerts;

final class TemplateRenderer{
	/**
	 * @param array<string, string> $values
	 */
	public static function render(string $template, array $values) : string{
		$replacements = [];
		foreach(array_keys($values) as $name){
			$value = $values[$name];
			$replacements["{{$name}}"] = $value;
		}
		return strtr($template, $replacements);
	}
}
