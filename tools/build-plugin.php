<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$buildDirectory = $root . DIRECTORY_SEPARATOR . "build";
if(!is_dir($buildDirectory) && !mkdir($buildDirectory, 0777, true) && !is_dir($buildDirectory)){
	throw new RuntimeException("Could not create build directory");
}
$outputPath = $buildDirectory . DIRECTORY_SEPARATOR . "CustomAlerts.phar";
if(file_exists($outputPath) && !unlink($outputPath)){
	throw new RuntimeException("Could not replace existing plugin archive");
}

$archive = new \Phar($outputPath);
$archive->startBuffering();
foreach(["plugin.yml", "README.md", "LICENSE"] as $path){
	$archive->addFile($root . DIRECTORY_SEPARATOR . $path, $path);
}
foreach(["src", "resources", "docs"] as $directory){
	$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . DIRECTORY_SEPARATOR . $directory));
	foreach($iterator as $file){
		if(!$file->isFile()){
			continue;
		}
		$relative = str_replace("\\", "/", substr($file->getPathname(), strlen($root) + 1));
		$archive->addFile($file->getPathname(), $relative);
	}
}
$archive->setStub("<?php __HALT_COMPILER();");
$archive->setSignatureAlgorithm(\Phar::SHA256);
$archive->stopBuffering();
fwrite(STDOUT, "Built $outputPath\n");
