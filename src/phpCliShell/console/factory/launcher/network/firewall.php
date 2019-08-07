<?php
	namespace PhpCliShell\Console\Factory\Launcher\Network;

	use PhpCliShell\Core as C;

	use PhpCliShell\Console\Factory\Configuration;

	use PhpCliShell\Console\Factory\Launcher\AbstractLauncher;

	use Symfony\Component\Console\Question\Question;
	use Symfony\Component\Console\Question\ChoiceQuestion;
	use Symfony\Component\Console\Question\ConfirmationQuestion;

	class Firewall extends AbstractLauncher
	{
		const TEMPLATE_PATHNAME = __DIR__ .'/templates';
		const TEMPLATE_FILENAME = 'firewall.php';

		const LAUNCHER_PATHNAME = self::DEFAULT_LAUNCHER_PATHNAME;
		const LAUNCHER_FILENAME = 'network_firewall.%s.php';

		/**
		  * @var string
		  */
		const CONFIG_SECTION_ROOT_NAME = Configuration\Network\Firewall::CONFIG_SECTION_ROOT_NAME;

		/**
		  * @var array
		  */
		const FACTORY_CONFIGURATION_IPAM = Configuration\Network\Firewall::FACTORY_CONFIGURATION_IPAM;


		/**
		  * @return bool
		  */
		public function factory()
		{
			$this->_initialization();
			$questionHelper = $this->_command->getHelper('question');

			// env and site keys
			// --------------------------------------------------
			$question = new Question('What is the firewall environment key? (Key must be unique. Only [A-Z0-9_-] characters are allowed) ', false, '#[0-9A-Z_\-]+#i');
			$envKey = $questionHelper->ask($this->_input, $this->_output, $question);

			if($envKey === false) {
				$this->_output->writeln('<error>Firewall environment key is not valid, only [A-Z0-9_-] characters are allowed</error>');
				return false;
			}

			$question = new Question("What is the firewall site key in configuration? (Key must be unique. Only [A-Z0-9_-] characters are allowed) ", false, '#[0-9A-Z_\-]+#i');
			$siteKey = $questionHelper->ask($this->_input, $this->_output, $question);

			if($siteKey === false) {
				$this->_output->writeln("<error>Firewall site key is not valid, only [A-Z0-9_-] characters are allowed</error>");
				return false;
			}
			// --------------------------------------------------

			// Build launcher
			// --------------------------------------------------
			$configPathname = Configuration\Network\Firewall::retrieveConfigurationPathname($envKey);

			if(file_exists($configPathname))
			{
				$json = file_get_contents($configPathname);

				if($json !== false)
				{
					$config = json_decode($json, true);

					if($config !== null)
					{
						if(isset($config[self::CONFIG_SECTION_ROOT_NAME]['sites'][$siteKey]))
						{
							$launcherPathname = $this->retrieveLauncherPathname($envKey);
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
								'composerRootDir' => COMPOSER_ROOT_DIR,
								'configPathnames' => array($configPathname)
							);

							if(isset($config[self::CONFIG_SECTION_ROOT_NAME]['services']['ipam']))
							{
								$ipamServices = $config[self::CONFIG_SECTION_ROOT_NAME]['services']['ipam'];

								if($ipamServices !== false)
								{
									foreach($ipamServices as $ipamApplication => $ipamServerKeys)
									{
										if(array_key_exists($ipamApplication, self::FACTORY_CONFIGURATION_IPAM))
										{
											$ipamConfigFactory = self::FACTORY_CONFIGURATION_IPAM[$ipamApplication];

											foreach($ipamServerKeys as $ipamServerKey)
											{
												$ipamConfigPathname = $ipamConfigFactory::retrieveConfigurationPathname($ipamServerKey);

												if(file_exists($ipamConfigPathname))
												{
													$json = file_get_contents($configPathname);

													if($json !== false)
													{
														$config = json_decode($json, true);

														if($config !== null)
														{
															if(isset($config[$ipamConfigPathname::CONFIG_SECTION_ROOT_NAME]['servers'][$ipamServerKey])) {
																$templateVars['configPathnames'][] = $ipamConfigPathname;
															}
															else {
																$this->_output->writeln("<error>IPAM service key '".$ipamServerKey."' does not exist in configuration '".$ipamConfigPathname."'</error>");
																$this->_output->writeln("<error>You can create it with this console and command configuration:addon:factory</error>");
															}
														}
														else {
															$this->_output->writeln('<error>IPAM configuration "'.$ipamConfigPathname.'" is not a valid JSON</error>');
														}
													}
													else {
														$this->_output->writeln('<error>Unable to get IPAM configuration "'.$ipamConfigPathname.'"</error>');
													}
												}
												else {
													$this->_output->writeln("<error>IPAM configuration '".$ipamConfigPathname."' does not exist</error>");
													$this->_output->writeln("<error>You can create it with this console and command configuration:addon:factory</error>");
												}
											}
										}
										else {
											$this->_output->writeln("<error>IPAM connector name '".$ipamApplication."' is not available</error>");
											$this->_output->writeln("<error>Please use another connector or open an issue</error>");
										}
									}
								}
							}

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
							$this->_output->writeln("<error>Site key '".$siteKey."' does not exist in configuration '".$configPathname."'</error>");
							$this->_output->writeln("<error>You can create it with this console and command configuration:application:factory</error>");
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
				$this->_output->writeln("<error>You can create it with this console</error>");
			}
			// --------------------------------------------------

			return false;
		}
	}