<?php
	define("ROOT_DIR", realpath(__DIR__ . '/../../../'));
	define("APP_DIR", ROOT_DIR . '/application/firewall');

	$configurations = array(
		ROOT_DIR . '/configurations/config.json',
		ROOT_DIR . '/configurations/config.user.json',
		APP_DIR . '/configurations/firewall.json',
		APP_DIR . '/configurations/firewall.user.json',
		APP_DIR . '/configurations/firewall.prdx.json',
		APP_DIR . '/configurations/firewall.prdx.user.json',
		__DIR__ . '/configurations/firewall.test.user.json',
	);

	require_once(APP_DIR . '/launcher/firewall.php');
	$Launcher = new \App\Firewall\Launcher_Firewall();

	$SHELL = new \App\Firewall\Shell_Firewall($configurations, array('CORP', 'SEC'), false);

	$commands = array(
		array('show', null),
		array('show hosts', null),
		array('show subnets', null),
		array('show networks', null),
		array('show rules', null),
	);

	foreach($commands as $command)
	{
		list($cmd, $args) = $command;

		if($args === null) {
			$args = array();
		}

		$SHELL->EOL();

		$SHELL->print($cmd, 'black', 'white');
		$Cli_Results = $SHELL->test($cmd, $args);

		if($Cli_Results->isTrue()) {
			$SHELL->print('STATUS: OK', 'black', 'white');
		}
		else {
			$SHELL->error('STATUS: KO', 'black', 'red');
		}
	}