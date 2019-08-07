<?php
	namespace PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Core as C;

	trait OrchestratorTrait
	{
		/**
		  * @var \PhpCliShell\Addon\Ipam\Common\Orchestrator
		  */
		protected static $_instance;


		/**
		  * @return bool
		  */
		public static function hasInstance()
		{
			return (static::$_instance !== null);
		}

		/**
		  * @param \PhpCliShell\Core\Config $config
		  * @return \PhpCliShell\Addon\Ipam\Common\Orchestrator
		  */
		public static function getInstance(C\Config $config = null)
		{
			if(static::$_instance === null) {
				$class = static::class;
				static::$_instance = new $class($config);
			}

			return static::$_instance;
		}
	}