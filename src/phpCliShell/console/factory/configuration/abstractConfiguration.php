<?php
	namespace PhpCliShell\Console\Factory\Configuration;

	use PhpCliShell\Core as C;

	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;
	use Symfony\Component\Console\Question\Question;
	use Symfony\Component\Console\Question\ConfirmationQuestion;

	class AbstractConfiguration
	{
		/**
		  * @var array
		  */
		const HUMAN_BOOL = array(
			'no' => false,
			'yes' => true
		);

		/**
		  * @var string
		  */
		const DEFAULT_CONFIG_PATHNAME = WORKING_ROOT_DIR.'/configurations';

		/**
		  * @var string
		  */
		const CONFIG_PATHNAME = null;

		/**
		  * @var string
		  */
		const CONFIG_FILENAME = null;

		/**
		  * @var string
		  */
		const CONFIG_SECTION_ROOT_NAME = null;

		/**
		  * @var \Symfony\Component\Console\Command\Command
		  */
		protected $_command;

		/**
		  * @var \Symfony\Component\Console\Input\InputInterface
		  */
		protected $_input;

		/**
		  * @var \Symfony\Component\Console\Output\OutputInterface
		  */
		protected $_output;

		/**
		  * @var string
		  */
		protected $_configurationPathname;


		/**
		  * @param \Symfony\Component\Console\Command\Command $command
		  * @param \Symfony\Component\Console\Input\InputInterface $input
		  * @param \Symfony\Component\Console\Output\OutputInterface $output
		  * @return $this
		  */
		public function __construct(Command $command, InputInterface $input, OutputInterface $output)
		{
			$this->_command = $command;
			$this->_input = $input;
			$this->_output = $output;
		}

		/**
		  * @return void
		  */
		protected function _initialization()
		{
			$this->_configurationPathname = null;
		}

		/**
		  * @return bool
		  */
		public function factory()
		{
			return false;
		}

		/**
		  * @return array
		  */
		protected function _getDefaultConfiguration()
		{
			return array(
				'DEFAULT' => array(
					'sys' => array(
						'browserCmd' => 'xdg-open',
						'secureShellCmd' => 'ssh',
						'javawsCmd' => 'javaws',
					)
				)
			);
		}

		/**
		  * @param null|string $key
		  * @return false|array Configuration
		  */
		protected function _prepareConfiguration($key = null)
		{
			$config = $this->_getDefaultConfiguration();
			$questionHelper = $this->_command->getHelper('question');

			$configFullname = $this->retrieveConfigurationPathname($key);
			$this->_configurationPathname = $configFullname;
			$configPathname = dirname($configFullname);

			if(!file_exists($configPathname))
			{
				C\Tools::pathname($configPathname, true, true);

				if(!file_exists($configPathname)) {
					$this->_output->writeln("<error>Can not create configuration directory '".$configPathname."'</error>");
				}
				else {
					return $config;
				}
			}
			elseif(file_exists($configFullname))
			{
				$question = new ConfirmationQuestion('Configuration already exists, do you want merge both? (Yes or No) [No] ', false, '/^(y|o)/i');
				$mergeConfig = $questionHelper->ask($this->_input, $this->_output, $question);

				if($mergeConfig)
				{
					if(!is_readable($configFullname)) {
						$this->_output->writeln('<error>Configuration "'.$configFullname.'" is not readable</error>');
					}
					elseif(!is_writable($configFullname)) {
						$this->_output->writeln('<error>Configuration "'.$configFullname.'" is not writable</error>');
					}
					else
					{
						$json = file_get_contents($configFullname);

						if($json !== false)
						{
							$currentConfig = json_decode($json, true);

							if($currentConfig !== null) {
								return array_replace_recursive($config, $currentConfig);
							}
							else {
								$this->_output->writeln("<error>Configuration '".$configFullname."' is not a valid JSON</error>");
							}
						}
						else {
							$this->_output->writeln("<error>Unable to get configuration '".$configFullname."'</error>");
						}
					}
				}
				else
				{
					$question = new ConfirmationQuestion('Are you sure you want overwrite it? (Yes or No) [No] ', false, '/^(y|o)/i');
					$overwriteConfig = $questionHelper->ask($this->_input, $this->_output, $question);

					if($overwriteConfig !== false) {
						return $config;
					}
				}
			}
			elseif(!is_writable($configPathname)) {
				$this->_output->writeln("<error>Configuration directory '".$configPathname."' is not writable</error>");
			}
			else {
				return $config;
			}

			return false;
		}

		/**
		  * @param array $configuration
		  * @return bool
		  */
		protected function _writeConfiguration(array $configuration)
		{
			$json = json_encode($configuration, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

			if($json === false) {
				$this->_output->writeln("<error>Unable to create JSON configuration, please open an issue</error>");
				return false;
			}

			$configPathname = $this->getConfigurationPathname();

			$status = file_put_contents($configPathname, $json);

			if($status === false) {
				$this->_output->writeln("<error>Unable to write JSON configuration in '".$configPathname."'</error>");
				return false;
			}

			return true;
		}

		/**
		  * @return null|string
		  */
		public function getConfigurationPathname()
		{
			return $this->_configurationPathname;
		}

		/**
		  * @param null|string $key
		  * @return string
		  */
		public static function retrieveConfigurationPathname($key = null)
		{
			if($key === null) {
				$configFilename = static::CONFIG_FILENAME;
			}
			else {
				$configFilename = sprintf(static::CONFIG_FILENAME, $key);
			}

			return static::CONFIG_PATHNAME.'/'.$configFilename;
		}
	}