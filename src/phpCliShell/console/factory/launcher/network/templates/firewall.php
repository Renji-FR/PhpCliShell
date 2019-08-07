<?php
	echo '<?php';
	echo PHP_EOL;
?>
	define("WORKING_ROOT_DIR", __DIR__);
	define("PROJECT_ROOT_DIR", '<?php echo $this->projectRootDir; ?>');
	define("APPLICATION_ROOT_DIR", PROJECT_ROOT_DIR.'/application/firewall');
	define("COMPOSER_ROOT_DIR", '<?php echo $this->composerRootDir; ?>');

	define("ROOT_DIR", WORKING_ROOT_DIR);
	define("APP_DIR", APPLICATION_ROOT_DIR);

	if(!isset($configurations))
	{
		$configurations = array(
<?php
	foreach($this->configPathnames as $configPathname) {
		echo "\t\t\t'".$configPathname."',".PHP_EOL;
	}
?>
		);
	}

	require_once(APPLICATION_ROOT_DIR . '/launcher/firewall.php');
	$Launcher = new PhpCliShell\Application\Firewall\Launcher\Firewall();

	$SHELL = new PhpCliShell\Application\Firewall\Shell\Firewall($configurations);

	echo PHP_EOL;
	exit();