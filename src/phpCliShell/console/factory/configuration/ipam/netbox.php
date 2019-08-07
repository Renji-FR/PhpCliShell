<?php
	namespace PhpCliShell\Console\Factory\Configuration\Ipam;

	use PhpCliShell\Core as C;

	use PhpCliShell\Console\Factory\Configuration\AbstractConfiguration;

	use Symfony\Component\Console\Question\Question;
	use Symfony\Component\Console\Question\ConfirmationQuestion;

	class Netbox extends AbstractConfiguration
	{
		const CONFIG_PATHNAME = self::DEFAULT_CONFIG_PATHNAME;
		const CONFIG_FILENAME = 'ipam_netbox.%s.json';

		const CONFIG_SECTION_ROOT_NAME = 'IPAM_NETBOX';


		/**
		  * @return bool
		  */
		public function factory()
		{
			$this->_initialization();
			$questionHelper = $this->_command->getHelper('question');

			// servers > [server] & contexts > [context]
			// --------------------------------------------------
			$question = new Question('What is the key name of your NetBox service? (Key must be unique. Only [A-Z0-9_-] characters are allowed) ', false, '#[0-9A-Z_\-]+#i');
			$serverKey = $questionHelper->ask($this->_input, $this->_output, $question);

			if($serverKey === false) {
				$this->_output->writeln('<error>NetBox service key is not valid, only [A-Z0-9_-] characters are allowed</error>');
				return false;
			}

			$question = new Question('What is the NetBox server address? (URL) ', false, '#[[:print:]]+#i');
			$serverLocation = $questionHelper->ask($this->_input, $this->_output, $question);

			if($serverLocation === false) {
				$this->_output->writeln('<error>NetBox server address is not valid and require it</error>');
				return false;
			}

			$question = new ChoiceQuestion('What is your NetBox version? ', ['2.6' => 'NetBox = 2.6.x'], null);
			$netboxVersion = $questionHelper->ask($this->_input, $this->_output, $question);

			if($netboxVersion === false) {
				$this->_output->writeln('<error>Unable to known your NetBox version, please open an issue</error>');
				return false;
			}

			$question = new Question('What is your NetBox token? (if you want use environment token, leave empty) ', false, '#[[:print:]]+#i');
			$tokenCredential = $questionHelper->ask($this->_input, $this->_output, $question);

			if($tokenCredential === false)
			{
				$question = new Question('What NetBox token environment variable name do you want use? ', false, '#[0-9A-Z_\-]+#i');
				$tokenEnvVarName = $questionHelper->ask($this->_input, $this->_output, $question);

				if($tokenEnvVarName === false) {
					$this->_output->writeln('<error>NetBox token environment variable name is not valid, only [A-Z0-9_-] characters are allowed</error>');
					return false;
				}
			}
			else {
				$tokenEnvVarName = false;
			}

			$appConnector = 'default_v'.$netboxVersion;
			$objectCaching = true;
			// --------------------------------------------------

			// Build configuration
			// --------------------------------------------------
			$userConfig = array(
				self::CONFIG_SECTION_ROOT_NAME => array(
					'servers' => array(
						$serverKey => array(
							'tokenCredential' => $tokenCredential,
							'tokenEnvVarName' => $tokenEnvVarName,
							'serverLocation' => $serverLocation,
							'appConnector' => $appConnector,
							'objectCaching' => $objectCaching,
						)
					)
				)
			);

			$config = $this->_prepareConfiguration($serverKey);
			$config = array_replace_recursive($config, $userConfig);

			$status = $this->_writeConfiguration($config);

			if($status) {
				$configPathname = $this->getConfigurationPathname();
				$this->_output->writeln("<info>Configuration '".$configPathname."' is available!</info>");
				return true;
			}
			else {
				return false;
			}
			// --------------------------------------------------
		}
	}