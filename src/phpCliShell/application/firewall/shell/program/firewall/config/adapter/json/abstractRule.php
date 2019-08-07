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

	/**
	  * Pour toutes les méthodes publiques d'importation:
	  * Throw Exception	: permet d'indiquer que les données sont corrompues
	  * Return false	: permet d'indique une erreur mais sans donnée corrompue
	  */
	abstract class AbstractRule extends AbstractJson
	{
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
		const ALLOW_IMPORT = true;

		/**
		  * @var bool
		  */
		const ALLOW_EXPORT = true;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\Address
		  */
		protected $_addressFwProgram = null;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config $ORCHESTRATOR
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Config $ORCHESTRATOR, ArrayObject $objects)
		{
			parent::__construct($SHELL, $ORCHESTRATOR, $objects);

			$this->_addressFwProgram = Resolver::getManager(Manager\Address::class);
		}

		/**
		  * @param array $configItems Configuration items
		  * @param bool $keepName Keep name or allow to rewrite it
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @param bool $checkValidity
		  * @param bool $useContext
		  * @return int Number of NAT rules imported
		  */
		abstract public function importRules(array $configItems, $keepName, $prefix, $suffix, $checkValidity, $useContext = false);

		/**
		  * @param string $ruleClass
		  * @param string $addressObject
		  * @return \PhpCliShell\Application\Firewall\Core\Api\Address
		  */
		protected function _retrieveRuleAddress($ruleClass, $addressObject)
		{
			$addressParts = explode($ruleClass::SEPARATOR_TYPE, $addressObject, 2);

			if(count($addressParts) === 2)
			{
				// @todo temporaire/compatibilité
				// ------------------------------
				$addressType = $this->_keyToType($addressParts[0], $this->_addressFwProgram::API_INDEXES);
				// ------------------------------

				if($this->_addressFwProgram->isType($addressType))
				{
					$Core_Api_Address = $this->_addressFwProgram->getObject($addressType, $addressParts[1]);

					if($Core_Api_Address !== false) {
						return $Core_Api_Address;
					}
					else {
						throw new E\Message("L'adresse '".$addressObject."' n'existe pas", E_USER_ERROR);
					}
				}
				else {
					throw new E\Message("L'adresse '".$addressObject."' n'est pas valide (type)", E_USER_ERROR);
				}
			}
			else {
				throw new E\Message("L'adresse '".$addressObject."' n'est pas valide (format)", E_USER_ERROR);
			}
		}
	}