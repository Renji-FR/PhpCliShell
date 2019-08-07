<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\Json;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Component\Resolver;

	/**
	  * Pour toutes les méthodes publiques d'importation:
	  * Throw Exception	: permet d'indiquer que les données sont corrompues
	  * Return false	: permet d'indique une erreur mais sans donnée corrompue
	  */
	class OneJson extends AbstractJson
	{
		/**
		  * @var string
		  */
		const PREFIX = '';

		/**
		  * @var string
		  */
		const SUFFIX = '';

		/**
		  * @var bool
		  */
		const ALLOW_LOAD = true;

		/**
		  * @var bool
		  */
		const ALLOW_SAVE = true;

		/**
		  * @var bool
		  */
		const ALLOW_IMPORT = true;

		/**
		  * @var bool
		  */
		const ALLOW_EXPORT = true;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\Site
		  */
		protected $_siteFwProgram = null;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\Address
		  */
		protected $_addressFwProgram = null;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\AclRule
		  */
		protected $_jsonAclRule = null;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\NatRule
		  */
		protected $_jsonNatRule = null;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config $ORCHESTRATOR
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Config $ORCHESTRATOR, ArrayObject $objects)
		{
			parent::__construct($SHELL, $ORCHESTRATOR, $objects);

			$this->_siteFwProgram = Resolver::getManager(Manager\Site::class);
			$this->_addressFwProgram = Resolver::getManager(Manager\Address::class);

			$this->_jsonAclRule = new AclRule($SHELL, $ORCHESTRATOR, $objects);
			$this->_jsonNatRule = new NatRule($SHELL, $ORCHESTRATOR, $objects);
		}

		/**
		  * @param string $filename File to load
		  * @return bool
		  */
		public function loadSites($filename)
		{
			$configs = $this->_load($filename);

			if(count($configs) > 0) {
				$counter = $this->importSites($configs, true);
				return ($counter >= 0);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $filename File to load
		  * @return bool
		  */
		public function loadAddresses($filename)
		{
			$configs = $this->_load($filename);

			if(count($configs) > 0) {
				$counter = $this->importAddresses($configs, true);
				return ($counter >= 0);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $filename File to apply
		  * @param bool $checkValidity
		  * @return bool
		  */
		public function apply($filename, $checkValidity = true)
		{
			$configs = $this->_load($filename);

			if(count($configs) > 0)
			{
				$counterAddresses = $this->importAddresses($configs, $checkValidity, true);
				$counterSites = $this->importSites($configs, $checkValidity, true);

				$counterAclRules = $this->_jsonAclRule->importRules($configs, true, null, null, $checkValidity, true);
				$counterNatRules = $this->_jsonNatRule->importRules($configs, true, null, null, $checkValidity, true);

				return ($counterAddresses !== false && $counterSites !== false && $counterAclRules !== false && $counterNatRules !== false);
			}
			else {
				return true;
			}
		}

		/**
		  * @param array $configs Configuration to import
		  * @param bool $keepName Keep name or allow to rewrite it
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @return false|array Number of objects imported
		  */
		protected function _import(array $configs, $keepName, $prefix, $suffix)
		{
			$counterSites = $this->importSites($configs, true);

			$counterAclRules = $this->_jsonAclRule->importRules($configs, $keepName, $prefix, $suffix, true);
			$counterNatRules = $this->_jsonNatRule->importRules($configs, $keepName, $prefix, $suffix, true);

			return array(
				Api\Site::class => $counterSites,
				Api\AclRule::class => $counterAclRules,
				Api\NatRule::class => $counterNatRules
			);
		}

		/**
		  * @param array $configItems Configuration items
		  * @param bool $checkValidity
		  * @param bool $useContext
		  * @return int Number of addresses imported
		  */
		public function importAddresses(array $configItems, $checkValidity, $useContext = false)
		{
			$counter = 0;

			if($useContext) {
				$context = $this->_ORCHESTRATOR->getContext(Manager\Address::MANAGER_TYPE);
				$configItems = $configItems[$context];
			}

			foreach($configItems as $type => $items)
			{
				// @todo temporaire/compatibilité
				// ------------------------------
				$type = $this->_keyToType($type, $this->_addressFwProgram::API_INDEXES);
				// ------------------------------

				if($this->_addressFwProgram->isType($type)) {
					$results = $this->_addressFwProgram->restore($type, $items, $checkValidity);
					$counter += count($results);
				}
			}

			return $counter;
		}

		/**
		  * @param array $configItems Configuration items
		  * @param bool $checkValidity
		  * @param bool $useContext
		  * @return int Number of sites imported
		  */
		public function importSites(array $configItems, $checkValidity, $useContext = false)
		{
			$counter = 0;

			if($useContext) {
				$context = $this->_ORCHESTRATOR->getContext(Manager\Site::MANAGER_TYPE);
				$configItems = $configItems[$context];
			}

			foreach($configItems as $type => $items)
			{
				// @todo temporaire/compatibilité
				// ------------------------------
				$type = $this->_keyToType($type, $this->_siteFwProgram::API_INDEXES);
				// ------------------------------

				if($this->_siteFwProgram->isType($type)) {
					$results = $this->_siteFwProgram->restore($type, $items, $checkValidity);
					$counter += count($results);
				}
			}

			return $counter;
		}
	}