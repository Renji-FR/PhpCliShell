<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Helper;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Network;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core;
	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Core\Converter;

	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config;

	use Symfony\Component\Console\Input\ArgvInput;
	use Symfony\Component\Console\Output\ConsoleOutput;
	use Symfony\Component\Console\Helper\QuestionHelper;
	use Symfony\Component\Console\Helper\FormatterHelper;

	use Symfony\Component\Console\Question\Question;
	use Symfony\Component\Console\Question\ChoiceQuestion;
	use Symfony\Component\Console\Question\ConfirmationQuestion;

	/**
	  * Import datas from custom format
	  * Do not use this helper to import from JSON or CSV file
	  * This helper is only for no builtin format, see examples
	  *
	  * Examples:
	  * - Cisco ASA
	  * - Juniper JunOS
	  */
	class Import extends AbstractHelper
	{
		/**
		  * @var array
		  */
		const IMPORT_EXTENSION_CLASS = array(
			'cisco_asa' => Converter\Cisco\Asa::class,
			'juniper_junos' => Converter\Juniper\Junos::class,
			'juniper_junos-set' => Converter\Juniper\JunosSet::class,
		);

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
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config $ORCHESTRATOR
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Config $ORCHESTRATOR, ArrayObject $objects)
		{
			parent::__construct($SHELL, $ORCHESTRATOR, $objects);

			$this->_input = new ArgvInput();
			$this->_output = new ConsoleOutput();
			$this->_questionHelper = new QuestionHelper();
			$this->_formatterHelper = new FormatterHelper();
		}

		/**
		  * @param null|string $prefix
		  * @param null|string $suffix
		  * @return bool
		  */
		public function import($prefix = null, $suffix = null)
		{
			$SiteManager = $this->_SHELL->program->getProgram('site');
			$sites = $SiteManager->getAvailableSites();
			$hostnames = $sites->getFirewallHostnames();

			$question = new ChoiceQuestion('From which firewall do you want import configuration? ', $hostnames, null);
			$siteName = $this->_questionHelper->ask($this->_input, $this->_output, $question);

			if(isset($sites->{$siteName}))
			{
				$site = $sites->{$siteName};

				if($SiteManager->isSiteEnabled($siteName))
				{
					$firewallStore = $this->_SHELL->program->getProgram('store');

					if(($firewall = $firewallStore->getFirewall($siteName)) !== false)
					{
						if(isset($site->os))
						{
							switch(mb_strtolower($site->os))
							{
								case 'juniper-junos': {
									$converterClass = self::IMPORT_EXTENSION_CLASS['juniper_junos-set'];
								}
							}

							if(isset($converterClass))
							{
								$converter = new $converterClass($this->_SHELL, $site);
								$status = $this->_load($firewall, $converter);

								if($status) {
									$this->_save($converter, $prefix, $suffix);
								}
							}
							else {
								$this->_SHELL->error("L'OS du firewall '".$firewall->name."' n'est pas supportée", 'orange');
							}
						}
						else {
							$this->_SHELL->error("L'attribut OS du site '".$siteName."' n'est pas déclaré", 'orange');
						}
					}
					else {
						$this->_SHELL->error("Le firewall du site '".$siteName."' n'est pas disponible", 'orange');
					}
				}
				else {
					$this->_SHELL->error("Veuillez activer le '".$siteName."' avant l'importation", 'orange');
				}
			}
			else {
				$this->_SHELL->error("Le configuration du site '".$siteName."' n'existe pas", 'orange');
			}

			return true;
		}

		protected function _load(Core\Firewall $firewall, Converter\AbstractConverter $converter)
		{
			$question = new ConfirmationQuestion('Do you want wizard retrieve configuration from remote firewall? [yes|NO] ', false);

			if($this->_questionHelper->ask($this->_input, $this->_output, $question))
			{
				$question = new ChoiceQuestion('Which method do you want use to retrieve configuration? ', ['scp' => 'Secure copy (SSH)'], null);
				$method = $this->_questionHelper->ask($this->_input, $this->_output, $question);

				switch($method)
				{
					case 'scp':
					{
						$remoteSSH = false;

						$bastionCallback = function(Network\Ssh $bastionSSH)
						{
							if($bastionSSH->isConnected && $bastionSSH->isAuthenticated) {
								$this->_SHELL->print('SSH tunnel is established! (PID: '.$bastionSSH->processPid.')', 'green');
							}
						};

						try {
							$remoteSSH = $firewall->ssh($bastionCallback);
						}
						catch(E\Message $e) {
							$this->_SHELL->throw($e);
						}
						catch(\Exception $e) {
							$this->_SHELL->error("An error occured during SSH connection:".PHP_EOL.$e->getMessage(), 'orange');
						}

						if($remoteSSH !== false)
						{
							if($remoteSSH->isConnected && $remoteSSH->isAuthenticated)
							{
								$this->_SHELL->print('SSH session is established! (PID: '.$remoteSSH->processPid.')', 'green');

								try {
									$rulesCounters = $converter->loadFromRemote($remoteSSH);
								}
								catch(E\Message $exception) {
									$this->_SHELL->throw($exception);
								}
								/**
								  * Toujours exécuté et toujours en premier
								  * Exécuté même lorsqu'il n'y a pas d'exception
								  */
								finally {
									$remoteSSH->disconnect();
								}
							}
							else {
								$remoteSSH->disconnect();
								$this->_SHELL->error("Impossible d'établir la session SSH au site '".$firewall->siteName."'", 'orange');
							}
						}

						break;
					}
					default: {
						$this->_SHELL->error("La méthode '".$method."' n'est pas supportée", 'orange');
					}
				}
			}
			else
			{
				try {
					$rulesCounters = $converter->loadFromFiles();
				}
				catch(E\Message $exception) {
					$this->_SHELL->throw($exception);
				}
			}

			if(!isset($exception) && isset($rulesCounters))
			{
				if($rulesCounters !== false)
				{
					$this->_SHELL->EOL();

					foreach($rulesCounters as $classApi => $rulesCounter)
					{
						switch($rulesCounter)
						{
							case 0:
							case 1: {
								$this->_SHELL->print($rulesCounter." ".$classApi::API_LABEL." a été trouvé(e)", 'green');
								break;
							}
							default: {
								$this->_SHELL->print($rulesCounter." ".$classApi::API_LABEL." ont été trouvé(e)s", 'green');
							}
						}
					}

					return true;
				}
				else {
					$this->_SHELL->error("Import process aborted", 'orange');
				}
			}

			return false;
		}

		protected function _save(Converter\AbstractConverter $converter, $prefix, $suffix)
		{
			$this->_SHELL->EOL(3);

			$objectsCounters = $converter->saveToLocal($prefix, $suffix);

			$this->_SHELL->EOL();

			foreach($objectsCounters as $classApi => $objectsCounter)
			{
				switch($objectsCounter)
				{
					case 0:
					case 1: {
						$this->_SHELL->print($objectsCounter." ".$classApi::API_LABEL." a été importé(e)", 'green');
						break;
					}
					default: {
						$this->_SHELL->print($objectsCounter." ".$classApi::API_LABEL." ont été importé(e)s", 'green');
					}
				}
			}
		}
	}