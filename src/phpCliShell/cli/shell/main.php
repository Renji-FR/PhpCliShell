<?php
	namespace PhpCliShell\Cli\Shell;

	use ErrorException;

	use PhpCliShell\Core as C;

	abstract class Main
	{
		/**
		  * Use same version as Composer configuration
		  *
		  * @var string
		  */
		const PHP_MIN_VERSION = '7.1.0';

		/**
		  * @var \PhpCliShell\Core\Config
		  */
		protected $_CONFIG;

		/**
		  * @var bool|int
		  */
		protected $_debug = false;

		/**
		  * @var bool|int
		  */
		protected $_addonDebug = false;

		/**
		  * @var bool|int
		  */
		protected $_applicationDebug = false;


		/**
		  * @param string|array|\PhpCliShell\Core\Config $configuration
		  * @return $this
		  */
		public function __construct($configuration)
		{
			set_error_handler(array(static::class, 'errorHandler'));

			if(!C\Tools::isPharRunning() && version_compare(PHP_VERSION, self::PHP_MIN_VERSION) === -1) {
				throw new Exception("Version PHP inférieure à ".self::PHP_MIN_VERSION.", PHP ".self::PHP_MIN_VERSION." min requis", E_USER_ERROR);
			}

			$this->_initDebug();

			if($configuration instanceof C\Config) {
				$this->_CONFIG = $configuration;
			}
			else {
				$this->_CONFIG = C\Config::getInstance();
				$this->_CONFIG->loadConfigurations($configuration, false);
			}
		}

		protected function _initDebug()
		{
			$debug = getenv('PHPCLI_DEBUG');
			$this->_setDebug($this->_debug, $debug);

			$debug = getenv('PHPCLI_ADDON_DEBUG');
			$this->_setDebug($this->_addonDebug, $debug);

			$debug = getenv('PHPCLI_APPLICATION_DEBUG');
			$this->_setDebug($this->_applicationDebug, $debug);
		}

		/**
		  * @return \PhpCliShell\Core\Config
		  */
		public function getConfig()
		{
			return $this->_CONFIG;
		}

		/**
		  * @return false|\PhpCliShell\Core\Config
		  */
		public function getAddonConfig()
		{
			return false;
		}

		/**
		  * @return false|\PhpCliShell\Core\Config
		  */
		public function getApplicationConfig()
		{
			return false;
		}

		protected function _setDebug(&$attribute, $debug)
		{
			switch(mb_strtolower($debug))
			{
				case 'on':
				case 'yes':
				case 'true': {
					$attribute = true;
					break;
				}
				default:
				{
					if(C\Tools::is('int&&>0', $debug)) {
						$attribute = (int) $debug;
					}
				}
			}
		}

		protected function _throwException(Exception $exception)
		{
			C\Tools::e(PHP_EOL.PHP_EOL."Exception --> ".$exception->getMessage()." [".$exception->getFile()."] {".$exception->getLine()."}", 'red');
		}

		public static function errorHandler($errno, $errstr, $errfile, $errline)
		{
			/**
			  * Ne pas désactiver le buffer ici sinon il y aura une différence entre les ErrorException et les Exception
			  * Lors d'une Error et donc d'une ErrorException, le buffer, s'il est activé, serait désactivé
			  * mais lors d'une Exception "normale" le buffer resterait activé
			  */
			/*$bufferLevel = ob_get_level();

			for($i=0; $i<$bufferLevel; $i++) {
				ob_end_flush();
			}*/

			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'config':
				case 'configuration': {
					return $this->getConfig();
				}
				case 'addonConfig':
				case 'addonConfiguration': {
					return $this->getAddonConfig();
				}
				case 'applicationConfig':
				case 'applicationConfiguration': {
					return $this->getApplicationConfig();
				}
				case 'debug': {
					return $this->_debug;
				}
				case 'addonDebug': {
					return $this->_addonDebug;
				}
				case 'applicationDebug': {
					return $this->_applicationDebug;
				}
				default: {
					throw new Exception('Attribute "'.$name.'" does not exist', E_USER_ERROR);
				}
			}
		}
	}