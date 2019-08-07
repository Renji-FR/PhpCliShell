<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\Json;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Component\Resolver;

	class AclRule extends AbstractRule
	{
		/**
		  * @var string
		  */
		const PREFIX = '';

		/**
		  * @var string
		  */
		const SUFFIX = '.acl';

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\AclRule
		  */
		protected $_aclRuleFwProgram = null;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config $ORCHESTRATOR
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Config $ORCHESTRATOR, ArrayObject $objects)
		{
			parent::__construct($SHELL, $ORCHESTRATOR, $objects);

			$this->_aclRuleFwProgram = Resolver::getManager(Manager\AclRule::class);
		}

		/**
		  * @param string $filename File to apply
		  * @param bool $checkValidity
		  * @return bool
		  */
		public function apply($filename, $checkValidity = true)
		{
			$configs = $this->_load($filename);

			if(count($configs) > 0) {
				$counterAclRules = $this->importRules($configs, true, null, null, $checkValidity);
				return ($counterAclRules !== false);
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
			$counterAclRules = $this->importRules($configs, $keepName, $prefix, $suffix, true);

			return array(
				Api\AclRule::class => $counterAclRules
			);
		}

		/**
		  * @param array $configItems Configuration items
		  * @param bool $keepName Keep name or allow to rewrite it
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @param bool $checkValidity
		  * @param bool $useContext
		  * @return int Number of ACL rules imported
		  */
		public function importRules(array $configItems, $keepName, $prefix, $suffix, $checkValidity, $useContext = false)
		{
			$counter = 0;

			if($useContext) {
				$context = $this->_ORCHESTRATOR->getContext(Manager\AclRule::MANAGER_TYPE);
				$configItems = $configItems[$context];
			}

			foreach($configItems as $type => $items)
			{
				// @todo temporaire/compatibilité
				// ------------------------------
				if($type === 'rule') {	// Version 2.x
					$type = Api\AclRule::API_TYPE;
				}
				else {
					$type = $this->_keyToType($type, $this->_aclRuleFwProgram::API_INDEXES);
				}
				// ------------------------------

				if(($ruleClass = $this->_aclRuleFwProgram->getClass($type)) !== false)
				{
					if(!$keepName) {
						$baseRuleName = $this->_aclRuleFwProgram->getNextName($type);
					}

					foreach($items as $index => &$rule)
					{
						if(!C\Tools::is('string&&!empty', $prefix)) $prefix = false;
						if(!C\Tools::is('string&&!empty', $suffix)) $suffix = false;

						if($keepName) {
							$name = $rule[$ruleClass::FIELD_NAME];
						}
						else {
							$name = $baseRuleName + $index;
						}

						$name = $prefix.$name.$suffix;
						$rule[$ruleClass::FIELD_NAME] = $name;

						foreach(array('source' => 'sources', 'destination' => 'destinations') as $attribute => $attributes)
						{
							if(array_key_exists($attributes, $rule))
							{
								foreach($rule[$attributes] as &$addressObject) {
									$addressObject = $this->_retrieveRuleAddress($ruleClass, $addressObject);
								}
								unset($addressObject);
							}
						}
					}
					unset($rule);

					$results = $this->_aclRuleFwProgram->restore($type, $items, $checkValidity);
					$counter += count($results);

					foreach($results as $Core_Api_Rule)
					{
						/**
						  * Afficher juste les messages et ne pas bloquer
						  * afin de permettre à l'utilisateur de corriger
						  */
						try {
							$Core_Api_Rule->checkOverlapAddress();
						}
						catch(E\Message $e) {
							$this->_SHELL->error("RULE '".$Core_Api_Rule->name."': ".$e->getMessage(), 'orange');
						}
					}
				}
			}

			return $counter;
		}

		/**
		  * @param array $configs Configurations to export
		  * @return false|array Configuration datas
		  */
		protected function _export(array $configs)
		{
			return array(
				Api\AclRule::API_INDEX => $configs[Api\AclRule::API_TYPE]
			);
		}
	}