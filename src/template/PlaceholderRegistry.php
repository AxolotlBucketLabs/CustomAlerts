<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\template;

use AxolotlBucketLabs\CustomAlerts\alert\AlertContext;
use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use Closure;
use InvalidArgumentException;
use pocketmine\plugin\Plugin;
use function preg_match;

final class PlaceholderRegistry{
	/** @var array<string, Closure(AlertContext) : ?string> */
	private array $providers = [];
	/** @var array<string, Plugin> */
	private array $owners = [];

	/**
	 * @param Closure(AlertContext) : ?string $provider
	 * @throws InvalidArgumentException
	 */
	public function register(Plugin $owner, string $name, Closure $provider) : void{
		if(!$owner->isEnabled()){
			throw new InvalidArgumentException("Plugin '{$owner->getName()}' must be enabled to register placeholders");
		}
		if(preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name) !== 1){
			throw new InvalidArgumentException("Invalid placeholder name '$name'");
		}
		if(AlertType::isBuiltInPlaceholder($name)){
			throw new InvalidArgumentException("Placeholder '$name' is built in and cannot be registered");
		}
		if(isset($this->providers[$name])){
			throw new InvalidArgumentException("Placeholder '$name' is already registered by '{$this->owners[$name]->getName()}'");
		}
		$this->providers[$name] = $provider;
		$this->owners[$name] = $owner;
	}

	public function unregister(string $name) : void{
		unset($this->providers[$name], $this->owners[$name]);
	}

	public function unregisterAll(Plugin $owner) : void{
		foreach($this->owners as $name => $registeredOwner){
			if($registeredOwner === $owner){
				$this->unregister($name);
			}
		}
	}

	public function isRegistered(string $name) : bool{
		return isset($this->providers[$name]);
	}

	public function resolve(string $name, AlertContext $context) : ?string{
		$provider = $this->providers[$name] ?? null;
		return $provider !== null ? $provider($context) : null;
	}
}
