<?php

declare(strict_types=1);

use NhanAZ\CustomAlerts\LanguagePackBuilder;
use NhanAZ\CustomAlerts\PacketTextTemplate;
use NhanAZ\CustomAlerts\TemplateRenderer;
use NhanAZ\CustomAlerts\TranslationCatalog;
use NhanAZ\CustomAlerts\TranslationFileParser;
$root = dirname(__DIR__);
foreach([
	"src/NhanAZ/CustomAlerts/TemplateRenderer.php",
	"src/NhanAZ/CustomAlerts/PacketTextTemplate.php",
	"src/NhanAZ/CustomAlerts/TranslationCatalog.php",
	"src/NhanAZ/CustomAlerts/TranslationFileParser.php",
	"src/NhanAZ/CustomAlerts/LanguagePackBuilder.php"
] as $file){
	require_once $root . DIRECTORY_SEPARATOR . $file;
}

$assert = static function(bool $condition, string $message) : void{
	if(!$condition){
		throw new RuntimeException($message);
	}
};

$manifest = yaml_parse_file($root . DIRECTORY_SEPARATOR . "plugin.yml");
$config = yaml_parse_file($root . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "config.yml");
$assert(is_array($manifest) && isset($manifest["main"], $manifest["api"]), "plugin.yml did not parse");
$assert(is_array($config) && ($config["config-version"] ?? null) === 1, "config.yml did not parse");
$assert(is_file($root . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $manifest["main"]) . ".php"), "plugin main class path does not match plugin.yml");

$catalogJson = file_get_contents($root . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "translation-catalog.json");
$catalog = TranslationCatalog::fromJson($catalogJson === false ? "" : $catalogJson);
$assert($catalog->count() === 658, "translation catalog should describe 658 upstream keys");
$assert($catalog->isClientKey("death.attack.generic"), "client-side death key is missing from the catalog");
$assert(!$catalog->isClientKey("pocketmine.server.start"), "server-side keys must not be marked client-side");
$assert($catalog->getPlaceholders("death.attack.generic") === [0], "death key placeholder metadata is incorrect");
$fullLocaleContents = file_get_contents($root . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "translations" . DIRECTORY_SEPARATOR . "vi_VN.ini");
$fullLocale = TranslationFileParser::parse($fullLocaleContents === false ? "" : $fullLocaleContents);
$assert(count($fullLocale) === 658, "full Vietnamese locale file should contain all 658 audited keys");
$assert(($fullLocale["death.attack.generic"] ?? null) === "{%0} đã chết", "full Vietnamese locale file is missing its death translation");

$assert(TemplateRenderer::render("<{player}> {message}", ["player" => "Steve", "message" => "hello"]) === "<Steve> hello", "named template substitution failed");
$assert(PacketTextTemplate::render("{1}: {0}", ["hello", "Steve"]) === "Steve: hello", "packet parameter substitution failed");
$assert(PacketTextTemplate::render("{0}", ["{0}"]) === "{0}", "packet substitution should not recurse into parameter contents");
$missingParameter = null;
$assert(PacketTextTemplate::render("{2}", ["a"], $missingParameter) === null && $missingParameter === 2, "packet template with a missing parameter should be rejected");

$temporaryDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "customalerts-test-" . bin2hex(random_bytes(6));
$assert(mkdir($temporaryDirectory), "could not create isolated temporary test directory");
try{
	$translations = ["vi_VN" => ["enchantment.protect.all" => "Bảo vệ", "death.attack.generic" => "{%0} đã chết", "test.line" => "Dòng 1\nDòng 2"]];
	$packPath = LanguagePackBuilder::build($temporaryDirectory, $translations);
	$archive = new \ZipArchive();
	$assert($archive->open($packPath) === true, "generated language pack is not a valid ZIP archive");
	$packManifest = json_decode((string) $archive->getFromName("manifest.json"), true, flags: JSON_THROW_ON_ERROR);
	$localeList = json_decode((string) $archive->getFromName("texts/languages.json"), true, flags: JSON_THROW_ON_ERROR);
	$languageFile = (string) $archive->getFromName("texts/vi_VN.lang");
	$archive->close();
	$assert(($packManifest["format_version"] ?? null) === 2, "generated pack manifest format is incorrect");
	$assert(($packManifest["modules"][0]["type"] ?? null) === "resources", "generated pack is not a resource pack");
	$assert($localeList === ["vi_VN"], "generated pack locale list is incorrect");
	$assert(str_contains($languageFile, "enchantment.protect.all=Bảo vệ"), "generated locale file is missing its translation");
	$assert(str_contains($languageFile, "death.attack.generic=%1\$s đã chết"), "generated locale file did not convert PocketMine placeholders to Bedrock syntax");
	$assert(str_contains($languageFile, "test.line=Dòng 1\\nDòng 2"), "generated locale file did not encode a newline");
	$firstManifestVersion = $packManifest["header"]["version"];

	$samePath = LanguagePackBuilder::build($temporaryDirectory, $translations);
	$assert($samePath === $packPath, "unchanged translations should reuse the generated pack");
	$changedPath = LanguagePackBuilder::build($temporaryDirectory, ["vi_VN" => ["menu.play" => "Vào server"]]);
	$changedArchive = new \ZipArchive();
	$assert($changedArchive->open($changedPath) === true, "updated language pack is not a valid ZIP archive");
	$changedManifest = json_decode((string) $changedArchive->getFromName("manifest.json"), true, flags: JSON_THROW_ON_ERROR);
	$changedArchive->close();
	$assert($changedManifest["header"]["version"][2] === $firstManifestVersion[2] + 1, "changed translations should increment the pack version");
}finally{
	$resolvedTemporaryDirectory = realpath($temporaryDirectory);
	$resolvedTempRoot = realpath(sys_get_temp_dir());
	if($resolvedTemporaryDirectory !== false && $resolvedTempRoot !== false &&
		str_starts_with($resolvedTemporaryDirectory, $resolvedTempRoot . DIRECTORY_SEPARATOR) &&
		str_contains(basename($resolvedTemporaryDirectory), "customalerts-test-")
	){
		$files = scandir($resolvedTemporaryDirectory);
		if($files === false){
			$files = [];
		}
		foreach($files as $file){
			if($file !== "." && $file !== ".."){
				unlink($resolvedTemporaryDirectory . DIRECTORY_SEPARATOR . $file);
			}
		}
		rmdir($resolvedTemporaryDirectory);
	}
}

fwrite(STDOUT, "CustomAlerts checks passed.\n");
