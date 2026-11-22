<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\template;

use Closure;
use InvalidArgumentException;
use function array_key_exists;
use function array_unique;
use function array_values;
use function count;
use function preg_split;
use function sort;
use function str_starts_with;
use function substr;

final class Template{
	private const TOKEN_PATTERN = '/\{(%[0-9]+|[A-Za-z_][A-Za-z0-9_]*)}/';

	/** @var list<string> */
	private array $names = [];
	/** @var list<int> */
	private array $indices = [];

	/**
	 * @param list<string> $literals
	 * @param list<string> $tokens
	 */
	private function __construct(
		private readonly string $source,
		private readonly array  $literals,
		private readonly array $tokens
	){
		foreach($tokens as $token){
			if(str_starts_with($token, "%")){
				$this->indices[] = (int) substr($token, 1);
			}else{
				$this->names[] = $token;
			}
		}
		$this->names = array_values(array_unique($this->names));
		$this->indices = array_values(array_unique($this->indices));
		sort($this->indices);
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
		return new self($source, $literals, $tokens);
	}

	public function getSource() : string{
		return $this->source;
	}

	/**
	 * @return list<string>
	 */
	public function getNames() : array{
		return $this->names;
	}

	/**
	 * @return list<int>
	 */
	public function getIndices() : array{
		return $this->indices;
	}

	/**
	 * @param Closure(string) : ?string $resolver
	 */
	public function renderNamed(Closure $resolver) : string{
		return $this->render(static fn(string $token) : ?string => str_starts_with($token, "%") ? null : $resolver($token));
	}

	/**
	 * @param array<int, string> $parameters
	 */
	public function renderIndexed(array $parameters) : string{
		return $this->render(static fn(string $token) : ?string => str_starts_with($token, "%") ? ($parameters[(int) substr($token, 1)] ?? null) : null);
	}

	/**
	 * @param array<int, string> $parameters
	 */
	public function findMissingIndex(array $parameters) : ?int{
		foreach($this->indices as $index){
			if(!array_key_exists($index, $parameters)){
				return $index;
			}
		}
		return null;
	}

	/**
	 * @param Closure(string) : ?string $resolver
	 */
	private function render(Closure $resolver) : string{
		$result = $this->literals[0];
		for($i = 0, $count = count($this->tokens); $i < $count; ++$i){
			$token = $this->tokens[$i];
			$result .= ($resolver($token) ?? "{" . $token . "}") . $this->literals[$i + 1];
		}
		return $result;
	}
}
