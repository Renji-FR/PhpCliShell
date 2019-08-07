<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Helper;

	use ArrayObject;

	use PhpCliShell\Core as C;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config;

	abstract class AbstractHelper
	{
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
	}