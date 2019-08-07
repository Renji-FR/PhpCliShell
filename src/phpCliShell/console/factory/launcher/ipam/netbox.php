<?php
	namespace PhpCliShell\Console\Factory\Launcher\Ipam;

	use PhpCliShell\Core as C;

	use PhpCliShell\Console\Factory\Configuration;

	use PhpCliShell\Console\Factory\Launcher\AbstractLauncher;

	use Symfony\Component\Console\Question\Question;
	use Symfony\Component\Console\Question\ConfirmationQuestion;

	class Netbox extends AbstractLauncher
	{
		const TEMPLATE_PATHNAME = __DIR__ .'/templates';
		const TEMPLATE_FILENAME = 'netbox.php';

		const LAUNCHER_PATHNAME = self::DEFAULT_LAUNCHER_PATHNAME;
		const LAUNCHER_FILENAME = 'ipam_netbox.%s.php';

		const CONFIG_SECTION_ROOT_NAME = Configuration\Ipam\Netbox::CONFIG_SECTION_ROOT_NAME;


		/**
		  * @return bool
		  */
		public function factory()
		{
			$this->_initialization();
			$questionHelper = $this->_command->getHelper('question');

			// server key
			// --------------------------------------------------
			$question = new Question("What is the key name in configuration of your NetBox service? (Key must be unique. Only [A-Z0-9_-] characters are allowed) ", false, '#[0-9A-Z_\-]+#i');
			$serverKey = $questionHelper->ask($this->_input, $this->_output, $question);

			if($serverKey === false) {
				$this->_output->writeln("<error>NetBox service key is not valid, only [A-Z0-9_-] characters are allowed</error>");
				return false;
			}
			// --------------------------------------------------

			// Build launcher
			// --------------------------------------------------
			$configPathname = Configuration\Ipam\Netbox::retrieveConfigurationPathname($serverKey);

			if(file_exists($configPathname))
			{
				$json = file_get_contents($configPathname);

				if($json !== false)
				{
					$config = json_decode($json, true);

					if($config !== null)
					{
						if(isset($config[self::CONFIG_SECTION_ROOT_NAME]['servers'][$serverKey]))
						{
							$launcherPathname = $this->retrieveLauncherPathname($serverKey);
							$this->_launcherPathname = $launcherPathname;

							if(file_exists($launcherPathname))
							{
								$question = new ConfirmationQuestion('Launcher already exists, are you sure you want overwrite it? (Yes or No) [No] ', false, '/^(y|o)/i');
								$overwriteLauncher = $questionHelper->ask($this->_input, $this->_output, $question);

								if($overwriteLauncher === false) {
									return false;
								}
							}

							$templateVars = array(
								'projectRootDir' => PROJECT_ROOT_DIR,
								'configPathname' => $configPathname,
								'serverKey' => $serverKey
							);

							$status = $this->_writeLauncher($templateVars);

							if($status) {
								$this->_output->writeln("<info>Launcher '".$launcherPathname."' is available!</info>");
								return true;
							}
							else {
								$this->_output->writeln("<error>Unable to create launcher, please open an issue</error>");
							}
						}
						else {
							$this->_output->writeln("<error>Service key '".$serverKey."' does not exist in configuration '".$configPathname."'</error>");
							$this->_output->writeln("<error>You can create it with this console and command configuration:addon:factory</error>");
						}
					}
					else {
						$this->_output->writeln('<error>Configuration "'.$configPathname.'" is not a valid JSON</error>');
					}
				}
				else {
					$this->_output->writeln('<error>Unable to get configuration "'.$configPathname.'"</error>');
				}
			}
			else {
				$this->_output->writeln("<error>Configuration '".$configPathname."' does not exist</error>");
				$this->_output->writeln("<error>You can create it with this console and command configuration:addon:factory</error>");
			}
			// --------------------------------------------------

			return false;
		}
	}