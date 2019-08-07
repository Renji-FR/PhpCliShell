<?php
	echo '<?php';
	echo PHP_EOL;
?>
	define("WORKING_ROOT_DIR", __DIR__);
	define("PROJECT_ROOT_DIR", '<?php echo $this->projectRootDir; ?>');
	define("APPLICATION_ROOT_DIR", PROJECT_ROOT_DIR.'/application/Phpipam');

	define("ROOT_DIR", WORKING_ROOT_DIR);
	define("APP_DIR", APPLICATION_ROOT_DIR);

	if(!isset($configurations))
	{
		$configurations = array(
			'<?php echo $this->configPathname; ?>'
		);
	}

	require_once(APPLICATION_ROOT_DIR . '/launcher/ipam.php');
	$Launcher = new PhpCliShell\Application\Phpipam\Launcher\Ipam();

	$SHELL = new PhpCliShell\Application\Phpipam\Shell\Ipam($configurations, '<?php echo $this->serverKey; ?>');

	echo PHP_EOL;
	exit();