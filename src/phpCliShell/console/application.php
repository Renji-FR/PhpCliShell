<?php
	namespace PhpCliShell\Console;

	use ErrorException;

	use PhpCliShell\Console\Command;

	use Symfony\Component\Console\Application as SF_Application;

	class Application extends SF_Application
	{
		/**
		  * Box replace value when building PHAR
		  *
		  * @var string
		  */
		const APP_VERSION = '@git_version@';


		public function __construct()
		{
			set_error_handler(array(static::class, 'errorHandler'));

			if(self::APP_VERSION !== '@'.'git_version'.'@') {
				$version = self::APP_VERSION;
			}
			else {
				$version = 'dev';
			}

			parent::__construct('PHP-CLI SHELL', $version);

			$this->add(new Command\ConfigurationAddonFactory());
			$this->add(new Command\ConfigurationApplicationFactory());
			$this->add(new Command\LauncherApplicationFactory());
			$this->add(new Command\FirewallApplicationDemo());
		}

		public static function errorHandler($errno, $errstr, $errfile, $errline)
		{
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		}
	}