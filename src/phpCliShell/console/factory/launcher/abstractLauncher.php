<?php
	namespace PhpCliShell\Console\Factory\Launcher;

	use PhpCliShell\Core as C;

	use PhpCliShell\Console\Factory\Configuration;

	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class AbstractLauncher
	{
		const TEMPLATE_PATHNAME = __DIR__ .'/templates';
		const TEMPLATE_FILENAME = null;

		const DEFAULT_LAUNCHER_PATHNAME = WORKING_ROOT_DIR;
		const LAUNCHER_PATHNAME = null;
		const LAUNCHER_FILENAME = null;

		const CONFIG_SECTION_ROOT_NAME = Configuration\AbstractConfiguration::CONFIG_SECTION_ROOT_NAME;

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
		protected $_launcherPathname;


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
			$this->_launcherPathname = null;
		}

		/**
		  * @return bool
		  */
		public function factory()
		{
			return false;
		}

		/**
		  * @param array $attributes
		  * @return bool
		  */
		protected function _writeLauncher(array $attributes)
		{
			$templatePathname = static::TEMPLATE_PATHNAME.'/'.static::TEMPLATE_FILENAME;
			$launcherPathname = $this->getLauncherPathname();

			try {
				$Core_Template = new C\Template($templatePathname, $launcherPathname, $attributes);
				return $Core_Template->rendering();
			}
			catch(E\Message $e) {
				$this->_output->writeln("<error>".$e->getMessage()."</error>");
				return false;
			}
		}

		/**
		  * @return string
		  */
		public function getLauncherPathname()
		{
			return $this->_launcherPathname;
		}

		/**
		  * @param null|string $key
		  * @return string
		  */
		public static function retrieveLauncherPathname($key = null)
		{
			if($key === null) {
				$launcherFilename = static::LAUNCHER_FILENAME;
			}
			else {
				$launcherFilename = sprintf(static::LAUNCHER_FILENAME, $key);
			}

			return static::LAUNCHER_PATHNAME.'/'.$launcherFilename;
		}
	}