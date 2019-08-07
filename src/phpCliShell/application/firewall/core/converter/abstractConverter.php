<?php
	namespace PhpCliShell\Application\Firewall\Core\Converter;

	use PhpCliShell\Core as C;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core;

	use Symfony\Component\Console\Input\ArgvInput;
	use Symfony\Component\Console\Output\ConsoleOutput;
	use Symfony\Component\Console\Helper\QuestionHelper;
	use Symfony\Component\Console\Helper\FormatterHelper;

	abstract class AbstractConverter
	{
		/**
		  * @var \PhpCliShell\Cli\Shell\Main
		  */
		protected $_SHELL = null;

		/**
		  * @var \PhpCliShell\Application\Firewall\Core\Site
		  */
		protected $_site;

		/**
		  * @var \Symfony\Component\Console\Input\ArgvInput
		  */
		protected $_input = null;

		/**
		  * @var \Symfony\Component\Console\Output\ConsoleOutput
		  */
		protected $_output = null;

		/**
		  * @var \Symfony\Component\Console\Helper\QuestionHelper
		  */
		protected $_questionHelper = null;

		/**
		  * @var \Symfony\Component\Console\Helper\FormatterHelper
		  */
		protected $_formatterHelper = null;

		/**
		  * Debug mode
		  * @var bool
		  */
		protected $_debug = false;


		/**
		  * @param \PhpCliShell\Shell\Service\Main $SHELL
		  * @param \PhpCliShell\Core\Config $siteConfig
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Core\Site $site)
		{
			$this->_SHELL = $SHELL;
			$this->_site = $site;

			$this->_input = new ArgvInput();
			$this->_output = new ConsoleOutput();
			$this->_questionHelper = new QuestionHelper();
			$this->_formatterHelper = new FormatterHelper();
		}
	}