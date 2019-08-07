<?php
	namespace PhpCliShell\Console\Factory\Configuration\Dcim;

	use PhpCliShell\Core as C;

	use PhpCliShell\Console\Factory\Configuration\AbstractConfiguration;

	use Symfony\Component\Console\Question\Question;
	use Symfony\Component\Console\Question\ConfirmationQuestion;

	class Patchmanager extends AbstractConfiguration
	{
		const CONFIG_PATHNAME = self::DEFAULT_CONFIG_PATHNAME;
		const CONFIG_FILENAME = 'dcim_patchmanager.%s.json';

		const CONFIG_SECTION_ROOT_NAME = 'DCIM_PATCHMANAGER';


		/**
		  * @return bool
		  */
		public function factory()
		{
			$this->_initialization();
			$questionHelper = $this->_command->getHelper('question');

			// servers > [server]
			// --------------------------------------------------
			$question = new Question('What is the key name of your PatchManager service? (Key must be unique. Only [A-Z0-9_-] characters are allowed) ', false, '#[0-9A-Z_\-]+#i');
			$serverKey = $questionHelper->ask($this->_input, $this->_output, $question);

			if($serverKey === false) {
				$this->_output->writeln('<error>PatchManager service key is not valid, only [A-Z0-9_-] characters are allowed</error>');
				return false;
			}

			$question = new Question('What is the PatchManager server address? (URL) ', false, '#[[:print:]]+#i');
			$serverLocation = $questionHelper->ask($this->_input, $this->_output, $question);

			if($serverLocation === false) {
				$this->_output->writeln('<error>PatchManager server address is not valid and require it</error>');
				return false;
			}

			$question = new Question('What is your PatchManager login? (if you want use environment login, leave empty) ', false, '#[[:print:]]+#i');
			$loginCredential = $questionHelper->ask($this->_input, $this->_output, $question);

			if($loginCredential === false)
			{
				$question = new Question('What PatchManager login environment variable name do you want use? ', false, '#[0-9A-Z_\-]+#i');
				$loginEnvVarName = $questionHelper->ask($this->_input, $this->_output, $question);

				if($loginEnvVarName === false) {
					$this->_output->writeln('<error>PatchManager login environment variable name is not valid, only [A-Z0-9_-] characters are allowed</error>');
					return false;
				}
			}
			else {
				$loginEnvVarName = false;
			}

			$question = new Question('What is your PatchManager password? (if you want use environment password, leave empty) ', false, '#[[:print:]]+#i');
			$passwordCredential = $questionHelper->ask($this->_input, $this->_output, $question);

			if($passwordCredential === false)
			{
				$question = new Question('What PatchManager password environment variable name do you want use? ', false, '#[0-9A-Z_\-]+#i');
				$passwordEnvVarName = $questionHelper->ask($this->_input, $this->_output, $question);

				if($passwordEnvVarName === false) {
					$this->_output->writeln('<error>PatchManager password environment variable name is not valid, only [A-Z0-9_-] characters are allowed</error>');
					return false;
				}
			}
			else {
				$passwordEnvVarName = false;
			}

			$objectCaching = false;
			// --------------------------------------------------

			// preferences > report > csvDelimiter
			// --------------------------------------------------
			$question = new Question('What is your CSV delimiter configuration in your PatchManager user profile? [,] ', ',', '#[[:print:]]+#i');
			$csvDelimiter = $questionHelper->ask($this->_input, $this->_output, $question);

			if($serverLocation === false) {
				$this->_output->writeln('<error>CSV delimiter is not valid and require it</error>');
				return false;
			}
			// --------------------------------------------------

			// userAttrs > default
			// --------------------------------------------------
			$question = new Question('Do you have serial number user attribute? (answer user attribute name) ', false, '#[[:print:]]+#i');
			$serialNumber = $questionHelper->ask($this->_input, $this->_output, $question);
			// --------------------------------------------------

			// Build configuration
			// --------------------------------------------------
			$userConfig = array(
				self::CONFIG_SECTION_ROOT_NAME => array(
					'servers' => array(
						$serverKey => array(
							'loginCredential' => $loginCredential,
							'loginEnvVarName' => $loginEnvVarName,
							'passwordCredential' => $passwordCredential,
							'passwordEnvVarName' => $passwordEnvVarName,
							'serverLocation' => $serverLocation,
							'objectCaching' => $objectCaching,
						)
					),
					'userAttrs' => array(
						'default' => array(
							'prefix' => '',
							'labels' => array(
								'serialNumber' => $serialNumber
							)
						)
					),
					'preferences' => array(
						'report' => array(
							'csvDelimiter' => $csvDelimiter
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