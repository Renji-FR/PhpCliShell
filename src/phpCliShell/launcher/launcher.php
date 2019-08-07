<?php
	namespace PhpCliShell\Launcher;

	require_once(PROJECT_ROOT_DIR . '/launcher/autoloader.php');

	class Launcher
	{
		public function __construct()
		{
			Autoloader::register();
		}
	}