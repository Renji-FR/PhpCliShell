<?php
	namespace PhpCliShell\Application\Firewall\Core;

	use PhpCliShell\Core as C;

	class Config extends C\Config
	{
		/**
		  * @var \PhpCliShell\Core\Config
		  */
		private static $_instance = null;


		/**
		  * @param null|string|string[] $filename
		  * @return \PhpCliShell\Core\Config
		  */
		public static function getInstance($filename = null)
		{
			if(self::$_instance === null) {
				$instance = parent::getInstance($filename);
				self::$_instance = $instance->NETWORK_FIREWALL;
			}

			return self::$_instance;
		}
	}