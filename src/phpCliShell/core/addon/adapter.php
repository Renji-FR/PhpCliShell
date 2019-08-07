<?php
	namespace PhpCliShell\Core\Addon;

	use PhpCliShell\Core as C;

	abstract class Adapter
	{
		/**
		  * @var string
		  */
		const LABEL = 'unknown';

		/**
		  * @var string
		  */
		const METHOD = 'unknown';

		/**
		  * @var \PhpCliShell\Core\Addon\Service
		  */
		protected $_service;

		/**
		  * Addon configuration
		  * @var \PhpCliShell\Core\Config
		  */
		protected $_config;

		/**
		  * @var bool
		  */
		protected $_debug = false;


		/**
		  * @param \PhpCliShell\Core\Addon\Service $service
		  * @param \PhpCliShell\Core\Config $config
		  * @param bool $debug
		  * @return \PhpCliShell\Core\Addon\Adapter
		  */
		public function __construct(Service $service, C\Config $config, $debug = false)
		{
			$this->debug($debug);

			$this->_service = $service;
			$this->_config = $config;
		}

		/**
		 * @return string Service ID
		 */
		public function getServiceId()
		{
			return $this->_service->id;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'service': {
					return $this->_service;
				}
				case 'config': {
					return $this->_config;
				}
				default: {
					throw new Exception("This attribute '".$name."' does not exist", E_USER_ERROR);
				}
			}
		}
	}