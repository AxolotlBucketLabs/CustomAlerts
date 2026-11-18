<?php

declare(strict_types=1);

if($argc !== 3){
	fwrite(STDERR, "Usage: php tools/generate-translation-catalog.php <eng.ini> <output.json>\n");
	exit(2);
}

$sourcePath = $argv[1];
$outputPath = $argv[2];
$source = file_get_contents($sourcePath);
if($source === false){
	fwrite(STDERR, "Could not read translation source: $sourcePath\n");
	exit(1);
}

$entries = [];
foreach(preg_split('/\r\n|\r|\n/', $source) as $line){
	$line = trim($line);
	if($line === "" || $line[0] === ";"){
		continue;
	}
	$separator = strpos($line, "=");
	if($separator === false){
		continue;
	}
	$key = substr($line, 0, $separator);
	$english = substr($line, $separator + 1);
	if($key === ""){
		continue;
	}
	preg_match_all('/\{%([0-9]+)\}/', $english, $matches);
	$placeholders = array_values(array_unique(array_map('intval', $matches[1])));
	sort($placeholders);
	$serverTranslated = str_starts_with($key, "pocketmine.");
	$entries[$key] = [
		"translation_side" => $serverTranslated ? "server" : "client",
		"routes" => $serverTranslated ? ["event-adapter-if-supported", "otherwise-pretranslated-or-console-setup"] : ["outgoing-text-packet-if-present", "client-resource-pack"],
		"placeholders" => $placeholders,
		"english" => $english
	];
}
ksort($entries);

$serverCount = count(array_filter($entries, static fn(array $entry) : bool => $entry["translation_side"] === "server"));
$catalog = [
	"upstream" => "axolotl-pm/PocketMine-MP",
	"commit" => "77f832da28e5b52996207ca5a8d8b54fd827b77e",
	"source" => "resources/translations/eng.ini",
	"counts" => ["total" => count($entries), "server_translated" => $serverCount, "client_translated" => count($entries) - $serverCount],
	"entries" => $entries
];
$json = json_encode($catalog, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
if(file_put_contents($outputPath, $json) === false){
	fwrite(STDERR, "Could not write catalog: $outputPath\n");
	exit(1);
}
fwrite(STDOUT, "Wrote " . count($entries) . " translations ($serverCount server-side, " . (count($entries) - $serverCount) . " client-side).\n");
