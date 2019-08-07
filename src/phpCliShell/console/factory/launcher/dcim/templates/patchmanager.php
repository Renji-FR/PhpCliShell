<?php
	echo '<?php';
	echo PHP_EOL;
?>
	define("WORKING_ROOT_DIR", __DIR__);
	define("PROJECT_ROOT_DIR", '<?php echo $this->projectRootDir; ?>');
	define("APPLICATION_ROOT_DIR", PROJECT_ROOT_DIR.'/application/Patchmanager');

	define("ROOT_DIR", WORKING_ROOT_DIR);
	define("APP_DIR", APPLICATION_ROOT_DIR);

	if(!isset($configurations))
	{
		$configurations = array(
			'<?php echo $this->configPathname; ?>'
		);
	}

	require_once(APPLICATION_ROOT_DIR . '/launcher/dcim.php');
	$Launcher = new PhpCliShell\Application\Patchmanager\Launcher\Dcim();

	$SHELL = new PhpCliShell\Application\Patchmanager\Shell\Dcim($configurations, '<?php echo $this->serverKey; ?>');

	echo PHP_EOL;
	exit();