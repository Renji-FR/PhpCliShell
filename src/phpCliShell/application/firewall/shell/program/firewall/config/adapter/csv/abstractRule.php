<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\Csv;

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
	abstract class AbstractRule extends AbstractCsv
	{
		/**
		  * @var bool
		  */
		const ALLOW_LOAD = false;

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
		  * @var string
		  */
		const TAG_SEPARATOR = ';';

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
		  * @param array $aclRules ACL rules configuration
		  * @param bool $keepName Keep name or allow to rewrite it
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @param bool $checkValidity
		  * @return int Number of rules imported
		  */
		abstract public function importRules(array $aclRules, $keepName, $prefix, $suffix, $checkValidity);

		/**
		  * @param array $configs Configurations to export
		  * @return array Export datas
		  */
		abstract public function exportRules(array $configs);
	}