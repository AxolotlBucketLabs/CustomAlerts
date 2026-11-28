<?php

declare(strict_types=1);

use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
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
if(!is_array($manifest) || !is_array($config)){
	throw new RuntimeException("plugin.yml or config.yml did not parse");
}
$mainClass = $manifest["main"] ?? null;
$namespacePrefix = $manifest["src-namespace-prefix"] ?? null;
if(!is_string($mainClass) || !is_string($namespacePrefix)){
	throw new RuntimeException("plugin.yml is missing its main class or namespace prefix");
}
$assert(isset($config["messages.join"]) && !isset($config["messages"]["join"]), "message keys should be flat");
$mainRelative = substr($mainClass, strlen($namespacePrefix) + 1);
$assert(is_file($root . "/src/" . str_replace("\\", "/", $mainRelative) . ".php"), "plugin main class path does not match plugin.yml");

$named = Template::parse("<{PLAYER}> {TIME} {UNKNOWN}");
$assert($named->getNames() === ["PLAYER", "TIME", "UNKNOWN"], "template names were not collected");
$assert($named->renderNamed(static fn(string $name) : ?string => ["PLAYER" => "Steve", "TIME" => "12:00:00"][$name] ?? null) === "<Steve> 12:00:00 {UNKNOWN}", "named template substitution failed");

$assert(AlertType::isBuiltInPlaceholder("PLAYER") && AlertType::isBuiltInPlaceholder("KILLER"), "built-in placeholder names were not recognized");
$assert(!AlertType::isBuiltInPlaceholder("RANK"), "unknown placeholder names should remain distinct from built-in placeholders");

$loader = new ConfigLoader();
$default = $loader->load($config);
$assert($default->getWarnings() === [], "default config should load without warnings");
foreach(AlertType::cases() as $type){
	$assert($default->getConfig()->getAlert($type) instanceof Template, "default config should define " . $type->value);
}

$custom = $loader->load([
	"messages.join" => "+ {PLAYER}",
	"messages.death.kill" => "{PLAYER} died to {KILLER}"
]);
$customConfig = $custom->getConfig();
$assert($customConfig->getAlert(AlertType::Join) instanceof Template, "flat join template was not loaded");
$assert($customConfig->getAlert(AlertType::DeathKill) instanceof Template, "flat death template was not loaded");
$assert($customConfig->getAlert(AlertType::Quit) === null, "missing message should leave the server default in place");

fwrite(STDOUT, "CustomAlerts checks passed.\n");
