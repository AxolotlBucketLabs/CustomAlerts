<?php

declare(strict_types=1);

use AxolotlBucketLabs\CustomAlerts\alert\AlertContext;
use AxolotlBucketLabs\CustomAlerts\alert\AlertType;
use AxolotlBucketLabs\CustomAlerts\catalog\TranslationCatalog;
use AxolotlBucketLabs\CustomAlerts\catalog\TranslationSide;
use AxolotlBucketLabs\CustomAlerts\config\ConfigException;
use AxolotlBucketLabs\CustomAlerts\config\ConfigLoader;
use AxolotlBucketLabs\CustomAlerts\pack\BundledLocaleFiles;
use AxolotlBucketLabs\CustomAlerts\pack\LanguagePackBuilder;
use AxolotlBucketLabs\CustomAlerts\pack\TranslationFileParser;
use AxolotlBucketLabs\CustomAlerts\template\Template;

$root = dirname(__DIR__);
require_once $root . "/vendor/autoload.php";

$assert = static function(bool $condition, string $message) : void{
	if(!$condition){
		throw new RuntimeException($message);
	}
};
$sameList = static function(array $left, array $right) : bool{
	if(count($left) !== count($right)){
		return false;
	}
	foreach($right as $index => $value){
		if(($left[$index] ?? null) !== $value){
			return false;
		}
	}
	return true;
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

$catalogJson = file_get_contents($root . "/resources/translation-catalog.json");
$catalog = TranslationCatalog::fromJson($catalogJson === false ? "" : $catalogJson);
$assert($catalog->count() === 658, "translation catalog should describe 658 upstream keys");
$assert($catalog->isClientKey("death.attack.generic"), "client-side death key is missing from the catalog");
$assert(!$catalog->isClientKey("pocketmine.server.start"), "server-side keys must not be marked client-side");
$assert($catalog->get("pocketmine.server.start")?->getSide() === TranslationSide::Server, "server-side key has the wrong side");
$assert($catalog->get("death.attack.generic")?->getPlaceholders() === [0], "death key placeholder metadata is incorrect");

$fullLocaleContents = file_get_contents($root . "/resources/translations/vi_VN.ini");
$fullLocale = TranslationFileParser::parse($fullLocaleContents === false ? "" : $fullLocaleContents);
$assert(count($fullLocale) === 658, "full Vietnamese locale file should contain all 658 audited keys");
$assert(($fullLocale["death.attack.generic"] ?? null) === "{%0} đã chết", "full Vietnamese locale file is missing its death translation");
$assert(array_key_exists("accept_license ", $fullLocale), "Vietnamese locale must preserve the catalog key's trailing space");

$expectedLocales = ["bg_BG", "de_DE", "en_US", "es_ES", "fr_FR", "it_IT", "ja_JP", "ko_KR", "lv_LV", "nl_NL", "ru_RU", "tr_TR", "vi_VN", "zh_CN"];
$assert($sameList(array_keys(BundledLocaleFiles::FILES), $expectedLocales), "the bundled Bedrock locale list should contain all 14 supported locales");
$assert($sameList(array_keys(BundledLocaleFiles::NAMES), $expectedLocales), "every bundled locale should have a display name");
foreach($expectedLocales as $locale){
	$assert(array_key_exists($locale, BundledLocaleFiles::FILES), "$locale locale file is not registered");
	$assert(array_key_exists($locale, BundledLocaleFiles::NAMES), "$locale display name is missing");
}
$verifiedClientTranslations = [];
foreach(BundledLocaleFiles::FILES as $locale => $localeFile){
	$contents = file_get_contents($root . "/resources/" . $localeFile);
	if($contents === false){
		throw new RuntimeException("$locale locale file is missing");
	}
	$entries = TranslationFileParser::parse($contents);
	$assert($entries !== [], "$locale locale file should contain translations");
	$clientEntries = 0;
	foreach($entries as $key => $value){
		$catalogEntry = $catalog->get($key);
		if($catalogEntry === null){
			throw new RuntimeException("$locale locale contains uncatalogued key " . json_encode($key));
		}
		preg_match_all('/\\{%([0-9]+)}/', $value, $matches);
		$placeholders = array_values(array_unique(array_map(intval(...), $matches[1])));
		sort($placeholders);
		$assert($placeholders === $catalogEntry->getPlaceholders(), "$locale locale has invalid placeholders for " . json_encode($key));
		if($catalogEntry->getSide() === TranslationSide::Client){
			++$clientEntries;
			$verifiedClientTranslations[$locale][$key] = $value;
		}
		if($locale === "en_US"){
			$assert($value === $catalogEntry->getEnglish(), "English locale differs from the catalog for " . json_encode($key));
		}
	}
	$assert($clientEntries > 0, "$locale locale should have at least one verified client translation");
}

$named = Template::parse("<{player}> {message} {unknown}");
$assert($named->getNames() === ["player", "message", "unknown"], "template names were not collected");
$assert($named->renderNamed(static fn(string $name) : ?string => ["player" => "Steve", "message" => "hello"][$name] ?? null) === "<Steve> hello {unknown}", "named template substitution failed");
$indexed = Template::parse("{%1}: {%0}");
$assert($indexed->getIndices() === [0, 1], "template indices were not collected");
$assert($indexed->renderIndexed(["hello", "Steve"]) === "Steve: hello", "packet parameter substitution failed");
$assert(Template::parse("{%0}")->renderIndexed(["{%0}"]) === "{%0}", "packet substitution should not recurse into parameter contents");
$assert(Template::parse("{%2}")->findMissingIndex(["a"]) === 2, "missing packet parameter was not detected");
$assert($named->renderIndexed([]) === "<{player}> {message} {unknown}", "indexed rendering should leave named placeholders unchanged");

$assert(AlertType::isBuiltInPlaceholder("player") && AlertType::isBuiltInPlaceholder("killer"), "built-in placeholder names were not recognized");
$assert(!AlertType::isBuiltInPlaceholder("rank"), "unknown placeholder names should be available for registration");
$assert(AlertContext::forPlayerName(AlertType::BannedReason, "Steve")->getValues() === ["player" => "Steve"], "pre-login context should only expose the player name");

$loader = new ConfigLoader($catalog);
$default = $loader->load($config);
$assert($default->getWarnings() === [], "default config should load without warnings");
foreach(AlertType::cases() as $type){
	$assert($default->getConfig()->getAlert($type) instanceof Template, "default config should define " . $type->value);
}
$assert(!$default->getConfig()->getResourcePack()->isEnabled(), "default config should keep the resource pack disabled");
$assert($default->getConfig()->getKillerFallback() === "the server", "default killer fallback is incorrect");
$switchedOff = $loader->load(["custom-messages" => false] + $config);
$assert($switchedOff->getWarnings() === [] && $switchedOff->getConfig()->countAlerts() === 0, "custom-messages false should turn off every message");
$assert($switchedOff->getConfig()->getKillerFallback() === "environment", "custom-messages false should also ignore the killer fallback");
$assert(count($loader->load(["custom-messages" => "no"] + $config)->getWarnings()) === 1, "a non-boolean custom-messages should warn");

$custom = $loader->load([
	"config-version" => 2,
	"messages" => [
		"join" => "+ {display_name}",
		"quit" => false,
		"chat" => "{killer}: {message}",
		"kick" => ["reason" => false],
		"death" => ["screen" => "{%0}", "killer-fallback" => "môi trường"]
	],
	"packets" => [
		"text" => [
			"death.attack.generic" => "X {%0}",
			"death.attack.player" => "{%5}",
			"death.attack.mob" => "{player}",
			"pocketmine.server.start" => "nope",
			"custom.unknown.key" => "{%0} unchecked"
		],
		"disconnect" => ["Server closed" => "Đóng", "" => "empty"]
	],
	"resource-pack" => ["enabled" => true, "translations" => ["vi_VN" => ["menu.play" => "Chơi"], "bad" => []]]
]);
$customConfig = $custom->getConfig();
$assert($customConfig->getAlert(AlertType::Join) instanceof Template, "join template was not loaded");
$assert($customConfig->getAlert(AlertType::Quit) === false, "quit should be hidden");
$assert($customConfig->getAlert(AlertType::Chat) instanceof Template, "chat template with an unavailable placeholder should still load");
$assert($customConfig->getAlert(AlertType::KickReason) === null, "kick reason cannot be hidden");
$assert($customConfig->getAlert(AlertType::DeathScreen) === null, "event templates must reject packet placeholders");
$assert($customConfig->getKillerFallback() === "môi trường", "configured killer fallback was not loaded");
$assert($loader->load(["config-version" => 2, "messages" => ["death" => ["killer-fallback" => 5]]])->getConfig()->getKillerFallback() === "environment", "invalid killer fallback should use the default");

$assert(array_keys($customConfig->getPacketOverrides()->getText()) === ["death.attack.generic", "custom.unknown.key"], "packet text overrides were not validated");
$assert($customConfig->getPacketOverrides()->getDisconnect() === ["Server closed" => "Đóng"], "disconnect overrides were not validated");
$assert($customConfig->getResourcePack()->getTranslations() === ["vi_VN" => ["menu.play" => "Chơi"]], "resource pack translations were not validated");
$assert(count($custom->getWarnings()) === 9, "unexpected warning count: " . implode(" | ", $custom->getWarnings()));

$versionRejected = false;
try{
	$loader->load(["config-version" => 1]);
}catch(ConfigException){
	$versionRejected = true;
}
$assert($versionRejected, "config-version 1 should be rejected");

$temporaryDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "customalerts-test-" . bin2hex(random_bytes(6));
$assert(mkdir($temporaryDirectory), "could not create isolated temporary test directory");
try{
	$builder = new LanguagePackBuilder($temporaryDirectory);
	$translations = ["vi_VN" => ["enchantment.protect.all" => "Bảo vệ", "death.attack.generic" => "{%0} đã chết", "test.line" => "Dòng 1\nDòng 2"]];
	$packPath = $builder->build($translations);
	$archive = new ZipArchive();
	$assert($archive->open($packPath) === true, "generated language pack is not a valid ZIP archive");
	$packManifestDecoded = json_decode((string) $archive->getFromName("manifest.json"), true, flags: JSON_THROW_ON_ERROR);
	$localeList = json_decode((string) $archive->getFromName("texts/languages.json"), true, flags: JSON_THROW_ON_ERROR);
	$languageNames = json_decode((string) $archive->getFromName("texts/language_names.json"), true, flags: JSON_THROW_ON_ERROR);
	$languageFile = (string) $archive->getFromName("texts/vi_VN.lang");
	$archive->close();
	if(!is_array($packManifestDecoded)){
		throw new RuntimeException("generated pack manifest is not an object");
	}
	$packManifest = $packManifestDecoded;
	$assert(($packManifest["format_version"] ?? null) === 2, "generated pack manifest format is incorrect");
	$modules = $packManifest["modules"] ?? null;
	$firstModule = is_array($modules) ? ($modules[0] ?? null) : null;
	$assert(is_array($firstModule) && ($firstModule["type"] ?? null) === "resources", "generated pack is not a resource pack");
	$assert($localeList === ["vi_VN"], "generated pack locale list is incorrect");
	$assert($languageNames === [["vi_VN", "Tiếng Việt (Việt Nam)"]], "generated pack language display names are incorrect");
	$assert(str_contains($languageFile, "enchantment.protect.all=Bảo vệ"), "generated locale file is missing its translation");
	$assert(str_contains($languageFile, "death.attack.generic=%1\$s đã chết"), "generated locale file did not convert PocketMine placeholders to Bedrock syntax");
	$assert(str_contains($languageFile, "test.line=Dòng 1\\nDòng 2"), "generated locale file did not encode a newline");
	$header = $packManifest["header"] ?? null;
	$firstVersion = is_array($header) ? ($header["version"] ?? null) : null;
	if(!is_array($firstVersion) || !isset($firstVersion[2]) || !is_int($firstVersion[2])){
		throw new RuntimeException("generated pack manifest version is invalid");
	}

	$assert($builder->build($translations) === $packPath, "unchanged translations should reuse the generated pack");
	$changedPath = $builder->build(["vi_VN" => ["menu.play" => "Vào server"]]);
	$changedArchive = new ZipArchive();
	$assert($changedArchive->open($changedPath) === true, "updated language pack is not a valid ZIP archive");
	$changedManifestDecoded = json_decode((string) $changedArchive->getFromName("manifest.json"), true, flags: JSON_THROW_ON_ERROR);
	$changedArchive->close();
	if(!is_array($changedManifestDecoded)){
		throw new RuntimeException("updated pack manifest is not an object");
	}
	$changedManifest = $changedManifestDecoded;
	$changedHeader = $changedManifest["header"] ?? null;
	$changedVersion = is_array($changedHeader) ? ($changedHeader["version"] ?? null) : null;
	$assert(is_array($changedVersion) && ($changedVersion[2] ?? null) === $firstVersion[2] + 1, "changed translations should increment the pack version");
	$assert(!file_exists($packPath), "stale generated packs should be removed");
	$allLocalesPackPath = $builder->build($verifiedClientTranslations);
	$allLocalesArchive = new ZipArchive();
	$assert($allLocalesArchive->open($allLocalesPackPath) === true, "generated 14-locale pack is not a valid ZIP archive");
	$allLocales = json_decode((string) $allLocalesArchive->getFromName("texts/languages.json"), true, flags: JSON_THROW_ON_ERROR);
	$allLanguageNames = json_decode((string) $allLocalesArchive->getFromName("texts/language_names.json"), true, flags: JSON_THROW_ON_ERROR);
	$assert($allLocales === $expectedLocales, "generated pack does not declare all 14 verified locales");
	if(!is_array($allLanguageNames) || count($allLanguageNames) !== 14){
		throw new RuntimeException("generated pack is missing a display name for a locale");
	}
	foreach($expectedLocales as $index => $locale){
		$namePair = $allLanguageNames[$index] ?? null;
		if(!is_array($namePair) || ($namePair[0] ?? null) !== $locale || ($namePair[1] ?? null) !== BundledLocaleFiles::NAMES[$locale]){
			throw new RuntimeException("$locale display name is missing or out of order in the generated pack");
		}
		$localeContents = $allLocalesArchive->getFromName("texts/$locale.lang");
		if($localeContents === false){
			throw new RuntimeException("$locale translation file is missing from the generated pack");
		}
		$packedEntries = TranslationFileParser::parse($localeContents);
		$assert(count($packedEntries) === count($verifiedClientTranslations[$locale]), "$locale resource file has an unexpected translation count");
		foreach($verifiedClientTranslations[$locale] as $key => $_){
			$assert(array_key_exists($key, $packedEntries), "$locale resource file is missing " . json_encode($key));
		}
	}
	$allLocalesArchive->close();
}finally{
	$resolvedTemporaryDirectory = realpath($temporaryDirectory);
	$resolvedTempRoot = realpath(sys_get_temp_dir());
	if($resolvedTemporaryDirectory !== false && $resolvedTempRoot !== false &&
		str_starts_with($resolvedTemporaryDirectory, $resolvedTempRoot . DIRECTORY_SEPARATOR) &&
		str_contains(basename($resolvedTemporaryDirectory), "customalerts-test-")
	){
		$files = scandir($resolvedTemporaryDirectory);
		foreach($files === false ? [] : $files as $file){
			if($file !== "." && $file !== ".."){
				unlink($resolvedTemporaryDirectory . DIRECTORY_SEPARATOR . $file);
			}
		}
		rmdir($resolvedTemporaryDirectory);
	}
}

fwrite(STDOUT, "CustomAlerts checks passed.\n");
