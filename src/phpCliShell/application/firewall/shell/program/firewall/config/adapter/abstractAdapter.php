<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter;

	use ArrayObject;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Component\Resolver;

	/**
	  * Pour toutes les méthodes publiques d'importation:
	  * Throw Exception	: permet d'indiquer que les données sont corrompues
	  * Return false	: permet d'indique une erreur mais sans donnée corrompue
	  */
	abstract class AbstractAdapter implements InterfaceAdapter
	{
		/**
		  * @var string
		  */
		const FORMAT = null;

		/**
		  * @var string
		  */
		const PREFIX = '';

		/**
		  * @var string
		  */
		const SUFFIX = '';

		/**
		  * @var string
		  */
		const EXTENSION = null;

		/**
		  * @var bool
		  */
		const ALLOW_LOAD = false;

		/**
		  * @var bool
		  */
		const ALLOW_SAVE = false;

		/**
		  * @var bool
		  */
		const ALLOW_IMPORT = false;

		/**
		  * @var bool
		  */
		const ALLOW_EXPORT = false;

		/**
		  * @var \PhpCliShell\Cli\Terminal\Main
		  */
		protected $_TERMINAL;

		/**
		  * @var \PhpCliShell\Cli\Shell\Main
		  */
		protected $_SHELL;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config
		  */
		protected $_ORCHESTRATOR;

		/**
		  * @var \ArrayObject
		  */
		protected $_objects;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config $ORCHESTRATOR
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Config $ORCHESTRATOR, ArrayObject $objects)
		{
			$this->_SHELL = $SHELL;
			$this->_TERMINAL = $SHELL->terminal;
			$this->_ORCHESTRATOR = $ORCHESTRATOR;

			$this->_objects = $objects;
		}

		/**
		  * @param string $action
		  * @return bool
		  */
		public function can($action)
		{
			switch(mb_strtolower($action))
			{
				case 'load': {
					return static::ALLOW_LOAD;
				}
				case 'save': {
					return static::ALLOW_SAVE;
				}
				case 'import': {
					return static::ALLOW_IMPORT;
				}
				case 'export': {
					return static::ALLOW_EXPORT;
				}
				default: {
					throw new Exception("Action '".$action."' is unknown", E_USER_ERROR);
				}
			}
		}

		/**
		  * @param string $pathname
		  * @param string $basename
		  * @return string
		  */
		public function formatFilename($pathname, $basename)
		{
			return $pathname . DIRECTORY_SEPARATOR . static::PREFIX . $basename . static::SUFFIX . static::EXTENSION;
		}

		/**
		  * Throw Exception pour indiquer que les données sont corrompues
		  * Return false pour indique une erreur mais sans donnée corrompue
		  *
		  * @param string $filename File to load
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @return false|array Number of objects loaded
		  */
		public function load($filename, $prefix = null, $suffix = null)
		{
			$configs = $this->_load($filename);

			if(count($configs) > 0) {
				return $this->_import($configs, true, $prefix, $suffix);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $filename File to save
		  * @param array $configs Configurations to save
		  * @return false|array Number of objects saved
		  */
		public function save($filename, array $configs)
		{
			$configs = $this->_export($configs);

			if($configs !== false)
			{
				if(count($configs) > 0) {
					return $this->_save($configs, $filename);
				}
				else {
					return true;
				}
			}
			else {
				return false;
			}
		}

		/**
		  * Throw Exception pour indiquer que les données sont corrompues
		  * Return false pour indique une erreur mais sans donnée corrompue
		  *
		  * @param string $filename File to import
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @return false|array Number of objects imported
		  */
		public function import($filename, $prefix = null, $suffix = null)
		{
			$configs = $this->_load($filename);

			if(count($configs) > 0) {
				return $this->_import($configs, true, $prefix, $suffix);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $filename File to export
		  * @param array $configs Configurations to export
		  * @return false|array Number of objects exported
		  */
		public function export($filename, array $configs)
		{
			$configs = $this->_export($configs);

			if($configs !== false)
			{
				if(count($configs) > 0) {
					return $this->_save($configs, $filename);
				}
				else {
					return true;
				}
			}
			else {
				return false;
			}
		}

		/**
		  * @param array $configs Configuration to import
		  * @param bool $keepName Keep name or allow to rewrite it
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @return false|array Number of objects imported
		  */
		abstract protected function _import(array $configs, $keepName, $prefix, $suffix);

		/**
		  * @param array $configs Configurations to export
		  * @return false|array Configuration datas
		  */
		abstract protected function _export(array $configs);

		/**
		  * @param string $filename Configuration filename
		  * @return array Configuration datas
		  */
		abstract protected function _load($filename);

		/**
		  * @param array $configs Configurations to save
		  * @param string $filename Configuration filename
		  * @return bool Save status
		  */
		abstract protected function _save(array $configs, $filename);
	}