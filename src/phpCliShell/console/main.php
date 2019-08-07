<?php
	$pharRootDir = Phar::running();

	if($pharRootDir === '') {
		$projectRootDir = dirname(__DIR__);
		$workingRootDir = realpath($projectRootDir.'/../../');
		$composerRootDir = $workingRootDir.'/vendor';
	}
	else {
		$projectRootDir = $pharRootDir.'/phpCliShell';
		$composerRootDir = $pharRootDir.'/vendor';
	}

	define("PROJECT_ROOT_DIR", $projectRootDir);
	require_once(PROJECT_ROOT_DIR.'/console/autoloader.php');
	PhpCliShell\Console\Autoloader::register();

	define("COMPOSER_ROOT_DIR", $composerRootDir);
	require_once(COMPOSER_ROOT_DIR.'/autoload.php');

	if(!isset($workingRootDir)) {
		$workingRootDir = PhpCliShell\Core\Tools::getWorkingPathname();
	}

	define("WORKING_ROOT_DIR", $workingRootDir);

	$Console_Application = new PhpCliShell\Console\Application();
	$Console_Application->run();