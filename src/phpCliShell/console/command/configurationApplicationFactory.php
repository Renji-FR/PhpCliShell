<?php
	namespace PhpCliShell\Console\Command;

	use PhpCliShell\Console\Factory\Configuration;

	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputDefinition;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputOption;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;
	use Symfony\Component\Console\Question\ChoiceQuestion;

	class ConfigurationApplicationFactory extends Command
	{
		/**
		  * @var array
		  */
		const APPLICATIONS = array(
				'network' => array(
					'firewall' => Configuration\Network\Firewall::class,
				)
		);


		protected function configure()
		{
			$this
				->setName('configuration:application:factory')
				->setDescription('Build application configuration')
				->addArgument(
					'applicationType',
					InputArgument::OPTIONAL,
					'The application type to create launcher'
				)
				->addArgument(
					'applicationName',
					InputArgument::OPTIONAL,
					'The application name to create launcher'
				);
		}

		protected function execute(InputInterface $input, OutputInterface $output)
		{
			$questionHelper = $this->getHelper('question');
			$formatterHelper = $this->getHelper('formatter');

			$applications = self::APPLICATIONS;
			$applicationType = $input->getArgument('applicationType');

			if($applicationType === null)
			{
				$applicationTypes = array_keys($applications);

				$question = new ChoiceQuestion('What is the application type? ', $applicationTypes, null);
				$applicationType = $questionHelper->ask($input, $output, $question);

				if($applicationType !== false)
				{
					$applicationType = mb_strtolower($applicationType);

					if(array_key_exists($applicationType, $applications)) {
						$applications = $applications[$applicationType];
					}
					else {
						$this->_output->writeln('<error>Application type not found and require it</error>');
						return 1;
					}
				}
				else {
					$this->_output->writeln('<error>Application type is not valid and require it</error>');
					return 1;
				}
			}
			else
			{
				$applicationType = mb_strtolower($applicationType);

				if(array_key_exists($applicationType, $applications)) {
					$applications = $applications[$applicationType];
				}
				else
				{
					$errorMessages = array("Error!", "Application type '".$applicationType."' does not exist");
					$formattedBlock = $formatterHelper->formatBlock($errorMessages, 'error');
					$output->writeln($formattedBlock);

					$output->writeln("<comment>Application types available:</comment>");

					foreach($applications as $applicationType => $applicationNames) {
						$output->writeln("<comment>- ".$applicationType."</comment>");
					}

					return 1;
				}
			}

			$applicationName = $input->getArgument('applicationName');

			if($applicationName === null)
			{
				$applicationNames = array_keys($applications);

				$question = new ChoiceQuestion('What is the application name? ', $applicationNames, null);
				$applicationName = $questionHelper->ask($input, $output, $question);

				if($applicationName !== false)
				{
					$applicationName = mb_strtolower($applicationName);

					if(array_key_exists($applicationName, $applications)) {
						$applicationClass = $applications[$applicationName];
					}
					else {
						$this->_output->writeln('<error>Application name not found and require it</error>');
						return 1;
					}
				}
				else {
					$this->_output->writeln('<error>Application name is not valid and require it</error>');
					return 1;
				}
			}
			else
			{
				$applicationName = mb_strtolower($applicationName);

				if(array_key_exists($applicationName, $applications)) {
					$applicationClass = $applications[$applicationName];
				}
				else
				{
					$errorMessages = array("Error!", "Application name '".$applicationName."' does not exist");
					$formattedBlock = $formatterHelper->formatBlock($errorMessages, 'error');
					$output->writeln($formattedBlock);

					$output->writeln("<comment>Application names available:</comment>");

					foreach($applications as $applicationName => $applicationClass) {
						$output->writeln("<comment>- ".$applicationName."</comment>");
					}

					return 1;
				}
			}

			$Factory_Configuration = new $applicationClass($this, $input, $output);
			$status = $Factory_Configuration->factory();

			if($status) {
				$exitMessage = "Do not forget to create luncher '".$applicationType."' '".$applicationName."' ";
				$exitMessage .= "with console and command launcher:application:factory";
				$output->writeln("<comment>".$exitMessage."</comment>");
				return 0;
			}
			else {
				return 1;
			}
		}
	}