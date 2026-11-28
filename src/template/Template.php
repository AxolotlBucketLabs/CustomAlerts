<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\template;

use Closure;
use InvalidArgumentException;
use function array_unique;
use function array_values;
use function preg_split;

final class Template{
	private const TOKEN_PATTERN = '/\{([A-Za-z_][A-Za-z0-9_]*)}/';

	/** @var list<string> */
	private readonly array $names;

	/**
	 * @param list<string> $literals
	 * @param list<string> $tokens
	 */
	private function __construct(
		private readonly array $literals,
		private readonly array $tokens
	){
		$this->names = array_values(array_unique($tokens));
	}

	public static function parse(string $source) : self{
		$parts = preg_split(self::TOKEN_PATTERN, $source, -1, PREG_SPLIT_DELIM_CAPTURE);
		if($parts === false){
			throw new InvalidArgumentException("Could not parse template");
		}
		$literals = [];
		$tokens = [];
		foreach($parts as $position => $part){
			if($position % 2 === 0){
				$literals[] = $part;
			}else{
				$tokens[] = $part;
			}
		}
		return new self($literals, $tokens);
	}

	/**
	 * @return list<string>
	 */
	public function getNames() : array{
		return $this->names;
	}

	/**
	 * @param Closure(string) : ?string $resolver
	 */
	public function renderNamed(Closure $resolver) : string{
		$result = $this->literals[0];
		foreach($this->tokens as $index => $token){
			$result .= ($resolver($token) ?? ("{" . $token . "}")) . $this->literals[$index + 1];
		}
		return $result;
	}
}
