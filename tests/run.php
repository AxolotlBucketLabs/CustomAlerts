<?php

declare(strict_types=1);

use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use AxolotlBucketLabs\CustomAlerts\config\ConfigException;
use AxolotlBucketLabs\CustomAlerts\config\ConfigLoader;
use AxolotlBucketLabs\CustomAlerts\template\Template;

$root = dirname(__DIR__);
require_once $root . "/vendor/autoload.php";

$assert = static function(bool $condition, string $message) : void{
	if(!$condition){
		throw new RuntimeException($message);
	}
};

$manifest = yaml_parse_file($root . "/plugin.yml");
$config = yaml_parse_file($root . "/resources/config.yml");
if(!is_array($manifest)){
	throw new RuntimeException("plugin.yml did not parse");
}
$mainClass = $manifest["main"] ?? null;
$namespacePrefix = $manifest["src-namespace-prefix"] ?? null;
if(!is_string($mainClass) || !is_string($namespacePrefix)){
	throw new RuntimeException("plugin.yml is missing its main class or namespace prefix");
}
if(!is_array($config) || ($config["config-version"] ?? null) !== ConfigLoader::VERSION){
	throw new RuntimeException("config.yml did not parse or has an unsupported version");
}
$mainRelative = substr($mainClass, strlen($namespacePrefix) + 1);
$assert(is_file($root . "/src/" . str_replace("\\", "/", $mainRelative) . ".php"), "plugin main class path does not match plugin.yml");

$named = Template::parse("<{player}> {message} {unknown}");
$assert($named->getNames() === ["player", "message", "unknown"], "template names were not collected");
$assert($named->renderNamed(static fn(string $name) : ?string => ["player" => "Steve", "message" => "hello"][$name] ?? null) === "<Steve> hello {unknown}", "named template substitution failed");

$assert(AlertType::isBuiltInPlaceholder("player") && AlertType::isBuiltInPlaceholder("killer"), "built-in placeholder names were not recognized");
$assert(!AlertType::isBuiltInPlaceholder("rank"), "unknown placeholder names should remain distinct from built-in placeholders");

$loader = new ConfigLoader();
$default = $loader->load($config);
$assert($default->getWarnings() === [], "default config should load without warnings");
foreach(AlertType::cases() as $type){
	$assert($default->getConfig()->getAlert($type) instanceof Template, "default config should define " . $type->value);
}
$assert($default->getConfig()->getKillerFallback() === "the server", "default killer fallback is incorrect");

$custom = $loader->load([
	"config-version" => 2,
	"messages" => [
		"join" => "+ {display_name}",
		"quit" => false,
		"death" => [
			"broadcast" => "{player} died to {killer}",
			"killer-fallback" => "nature"
		]
	]
]);
$customConfig = $custom->getConfig();
$assert($customConfig->getAlert(AlertType::Join) instanceof Template, "join template was not loaded");
$assert($customConfig->getAlert(AlertType::Quit) === false, "quit broadcast should be hidden");
$assert($customConfig->getKillerFallback() === "nature", "configured killer fallback was not loaded");

$versionRejected = false;
try{
	$loader->load(["config-version" => 1]);
}catch(ConfigException){
	$versionRejected = true;
}
$assert($versionRejected, "config-version 1 should be rejected");

fwrite(STDOUT, "CustomAlerts checks passed.\n");
