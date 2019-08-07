<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\Csv;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config;

	/**
	  * Pour toutes les méthodes publiques d'importation:
	  * Throw Exception	: permet d'indiquer que les données sont corrompues
	  * Return false	: permet d'indique une erreur mais sans donnée corrompue
	  */
	class OneCsv extends AbstractCsv
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
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\AclRule
		  */
		protected $_csvAclRule = null;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\NatRule
		  */
		protected $_csvNatRule = null;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config $ORCHESTRATOR
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Config $ORCHESTRATOR, ArrayObject $objects)
		{
			parent::__construct($SHELL, $ORCHESTRATOR, $objects);

			$this->_csvAclRule = new AclRule($SHELL, $ORCHESTRATOR, $objects);
			$this->_csvNatRule = new NatRule($SHELL, $ORCHESTRATOR, $objects);
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
			throw new Exception("Unable to use CSV adapter: CSV file can store only one object type", E_USER_ERROR);
		}

		/**
		  * @param array $configs Configurations to export
		  * @return false|array Configuration datas
		  */
		protected function _export(array $configs)
		{
			throw new Exception("Unable to use CSV adapter: CSV file can store only one object type", E_USER_ERROR);
		}
	}