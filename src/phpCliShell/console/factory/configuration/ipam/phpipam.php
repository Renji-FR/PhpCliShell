<?php
	namespace PhpCliShell\Console\Factory\Configuration\Ipam;

	use PhpCliShell\Core as C;

	use PhpCliShell\Console\Factory\Configuration\AbstractConfiguration;

	use Symfony\Component\Console\Question\Question;
	use Symfony\Component\Console\Question\ChoiceQuestion;
	use Symfony\Component\Console\Question\ConfirmationQuestion;

	class Phpipam extends AbstractConfiguration
	{
		const CONFIG_PATHNAME = self::DEFAULT_CONFIG_PATHNAME;
		const CONFIG_FILENAME = 'ipam_phpipam.%s.json';

		const CONFIG_SECTION_ROOT_NAME = 'IPAM_PHPIPAM';


		/**
		  * @return bool
		  */
		public function factory()
		{
			$this->_initialization();
			$questionHelper = $this->_command->getHelper('question');

			// servers > [server] & contexts > [context]
			// --------------------------------------------------
			$question = new Question('What is the key name of your phpIPAM service? (Key must be unique. Only [A-Z0-9_-] characters are allowed) ', false, '#[0-9A-Z_\-]+#i');
			$serverKey = $questionHelper->ask($this->_input, $this->_output, $question);

			if($serverKey === false) {
				$this->_output->writeln('<error>phpIPAM service key is not valid, only [A-Z0-9_-] characters are allowed</error>');
				return false;
			}

			$question = new Question('What is the phpIPAM server address? (URL) ', false, '#[[:print:]]+#i');
			$serverLocation = $questionHelper->ask($this->_input, $this->_output, $question);

			if($serverLocation === false) {
				$this->_output->writeln('<error>phpIPAM server address is not valid and require it</error>');
				return false;
			}

			$question = new Question('What is the phpIPAM API application ID? (Administration > API) ', false, '#[[:print:]]+#i');
			$applicationId = $questionHelper->ask($this->_input, $this->_output, $question);

			if($applicationId === false) {
				$this->_output->writeln('<error>phpIPAM application ID is not valid and require it</error>');
				return false;
			}

			$question = new ChoiceQuestion('What is your phpIPAM version? ', ['1.3' => 'phpIPAM = 1.3.x', '1.4' => 'phpIPAM >= 1.4'], null);
			$phpipamVersion = $questionHelper->ask($this->_input, $this->_output, $question);

			if($phpipamVersion === false) {
				$this->_output->writeln('<error>Unable to known your phpIPAM version, please open an issue</error>');
				return false;
			}

			if($phpipamVersion === '1.4')
			{
				$question = new ChoiceQuestion('Do you want use a token to authenticate you? ', ['no' => 'Do not use token', 'yes' => 'Use token'], null);
				$useToken = $questionHelper->ask($this->_input, $this->_output, $question);

				if($useToken === false) {
					$this->_output->writeln('<error>Unable to get your answer, please open an issue</error>');
					return false;
				}
				else {
					$useToken = self::HUMAN_BOOL[$useToken];
				}

				if($useToken)
				{
					$question = new Question('What is your phpIPAM token? (if you want use environment token, leave empty) ', false, '#[[:print:]]+#i');
					$tokenCredential = $questionHelper->ask($this->_input, $this->_output, $question);

					if($tokenCredential === false)
					{
						$question = new Question('What phpIPAM token environment variable name do you want use? ', false, '#[0-9A-Z_\-]+#i');
						$tokenEnvVarName = $questionHelper->ask($this->_input, $this->_output, $question);

						if($tokenEnvVarName === false) {
							$this->_output->writeln('<error>phpIPAM token environment variable name is not valid, only [A-Z0-9_-] characters are allowed</error>');
							return false;
						}
					}
					else {
						$tokenEnvVarName = false;
					}
				}
			}
			else {
				$useToken = false;
			}

			if(!$useToken)
			{
				$tokenCredential = false;
				$tokenEnvVarName = false;

				$question = new Question('What is your phpIPAM login? (if you want use environment login, leave empty) ', false, '#[[:print:]]+#i');
				$loginCredential = $questionHelper->ask($this->_input, $this->_output, $question);

				if($loginCredential === false)
				{
					$question = new Question('What phpIPAM login environment variable name do you want use? ', false, '#[0-9A-Z_\-]+#i');
					$loginEnvVarName = $questionHelper->ask($this->_input, $this->_output, $question);

					if($loginEnvVarName === false) {
						$this->_output->writeln('<error>phpIPAM login environment variable name is not valid, only [A-Z0-9_-] characters are allowed</error>');
						return false;
					}
				}
				else {
					$loginEnvVarName = false;
				}

				$question = new Question('What is your phpIPAM password? (if you want use environment password, leave empty) ', false, '#[[:print:]]+#i');
				$passwordCredential = $questionHelper->ask($this->_input, $this->_output, $question);

				if($passwordCredential === false)
				{
					$question = new Question('What phpIPAM password environment variable name do you want use? ', false, '#[0-9A-Z_\-]+#i');
					$passwordEnvVarName = $questionHelper->ask($this->_input, $this->_output, $question);

					if($passwordEnvVarName === false) {
						$this->_output->writeln('<error>phpIPAM password environment variable name is not valid, only [A-Z0-9_-] characters are allowed</error>');
						return false;
					}
				}
				else {
					$passwordEnvVarName = false;
				}
			}
			else {
				$loginCredential = $loginEnvVarName = false;
				$passwordCredential = $passwordEnvVarName = false;
			}

			$customControllersChoices = array('no' => 'I don\'t want install custom controllers', 'yes' => 'I will install custom controllers');
			$question = new ChoiceQuestion('Do you want use custom controllers for best performance? (highly recommended !!) ', $customControllersChoices, null);
			$useCustomControllers = $questionHelper->ask($this->_input, $this->_output, $question);

			if($useCustomControllers === false) {
				$this->_output->writeln('<error>Unable to get your answer, please open an issue</error>');
				return false;
			}
			else {
				$useCustomControllers = self::HUMAN_BOOL[$useCustomControllers];
			}

			if($useCustomControllers)
			{
				$appConnector = 'custom_v'.$phpipamVersion;

				$question = new ChoiceQuestion('Do you want enable objects cache system? ', ['no' => 'Disable cache system', 'yes' => 'Enable cache system'], null);
				$useCache = $questionHelper->ask($this->_input, $this->_output, $question);

				if($useCache === false) {
					$this->_output->writeln('<error>Unable to get your answer, please open an issue</error>');
					return false;
				}
				else {
					$objectCaching = self::HUMAN_BOOL[$useCache];
				}
			}	
			else
			{
				$appConnector = 'default_v'.$phpipamVersion;

				/**
				  * With default controllers, force cache system to be enabled
				  * Without cache system, performances are poor !
				  */
				$objectCaching = true;
			}
			// --------------------------------------------------

			// Build configuration
			// --------------------------------------------------
			$userConfig = array(
				self::CONFIG_SECTION_ROOT_NAME => array(
					'servers' => array(
						$serverKey => array(
							'tokenCredential' => $tokenCredential,
							'tokenEnvVarName' => $tokenEnvVarName,
							'loginCredential' => $loginCredential,
							'loginEnvVarName' => $loginEnvVarName,
							'passwordCredential' => $passwordCredential,
							'passwordEnvVarName' => $passwordEnvVarName,
							'serverLocation' => $serverLocation,
							'appConnector' => $appConnector,
							'objectCaching' => $objectCaching,
						)
					),
					'contexts' => array(
						$serverKey => $applicationId
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