<?php
	namespace PhpCliShell\Addon\Dcim;

	use PhpCliShell\Core as C;

	class Orchestrator extends C\Addon\Orchestrator
	{
		const SERVICE_NAME = 'DCIM';

		/**
		  * @var \PhpCliShell\Addon\Dcim\Orchestrator
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
		  * @return \PhpCliShell\Addon\Dcim\Orchestrator
		  */
		public static function getInstance(C\Config $config = null)
		{
			if(static::$_instance === null) {
				$class = static::class;
				static::$_instance = new $class($config);
			}

			return static::$_instance;
		}

		/**
		  * @param string $id
		  * @return \PhpCliShell\Addon\Dcim\Service
		  */
		protected function _newService($id)
		{
			return new Service($id, $this->_config);
		}
	}