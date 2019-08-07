<?php
	namespace PhpCliShell\Console\Command;

	use PhpCliShell\Addon\Ipam\Netbox;
	use PhpCliShell\Addon\Ipam\Phpipam;
	use PhpCliShell\Addon\Dcim\Patchmanager;

	use PhpCliShell\Console\Factory\Configuration;

	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputDefinition;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputOption;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;
	use Symfony\Component\Console\Question\ChoiceQuestion;

	class ConfigurationAddonFactory extends Command
	{
		/**
		  * @var array
		  */
		const ADDONS = array(
				'dcim' => array(
					Patchmanager\Service::SERVICE_TYPE => Configuration\Dcim\Patchmanager::class,
				),
				'ipam' => array(
					Netbox\Service::SERVICE_TYPE => Configuration\Ipam\Netbox::class,
					Phpipam\Service::SERVICE_TYPE => Configuration\Ipam\Phpipam::class,
				)
		);


		protected function configure()
		{
			$this
				->setName('configuration:addon:factory')
				->setDescription('Build addon configuration')
				->addArgument(
					'addonType',
					InputArgument::OPTIONAL,
					'The addon type to create launcher'
				)
				->addArgument(
					'addonName',
					InputArgument::OPTIONAL,
					'The addon name to create launcher'
				);
		}

		protected function execute(InputInterface $input, OutputInterface $output)
		{
			$questionHelper = $this->getHelper('question');
			$formatterHelper = $this->getHelper('formatter');

			$addons = self::ADDONS;
			$addonType = $input->getArgument('addonType');

			if($addonType === null)
			{
				$addonTypes = array_keys($addons);

				$question = new ChoiceQuestion('What is the addon type? ', $addonTypes, null);
				$addonType = $questionHelper->ask($input, $output, $question);

				if($addonType !== false)
				{
					$addonType = mb_strtolower($addonType);

					if(array_key_exists($addonType, $addons)) {
						$addons = $addons[$addonType];
					}
					else {
						$this->_output->writeln('<error>Addon type not found and require it</error>');
						return 1;
					}
				}
				else {
					$this->_output->writeln('<error>Addon type is not valid and require it</error>');
					return 1;
				}
			}
			else
			{
				$addonType = mb_strtolower($addonType);

				if(array_key_exists($addonType, $addons)) {
					$addons = $addons[$addonType];
				}
				else
				{
					$errorMessages = array("Error!", "Addon type '".$addonType."' does not exist");
					$formattedBlock = $formatterHelper->formatBlock($errorMessages, 'error');
					$output->writeln($formattedBlock);

					$output->writeln("<comment>Addon types available:</comment>");

					foreach($addons as $addonType => $addonNames) {
						$output->writeln("<comment>- ".$addonType."</comment>");
					}

					return 1;
				}
			}

			$addonName = $input->getArgument('addonName');

			if($addonName === null)
			{
				$addonNames = array_keys($addons);

				$question = new ChoiceQuestion('What is the addon name? ', $addonNames, null);
				$addonName = $questionHelper->ask($input, $output, $question);

				if($addonName !== false)
				{
					$addonName = mb_strtolower($addonName);

					if(array_key_exists($addonName, $addons)) {
						$addonClass = $addons[$addonName];
					}
					else {
						$this->_output->writeln('<error>Addon name not found and require it</error>');
						return 1;
					}
				}
				else {
					$this->_output->writeln('<error>Addon name is not valid and require it</error>');
					return 1;
				}
			}
			else
			{
				$addonName = mb_strtolower($addonName);

				if(array_key_exists($addonName, $addons)) {
					$addonClass = $addons[$addonName];
				}
				else
				{
					$errorMessages = array("Error!", "Addon name '".$addonName."' does not exist");
					$formattedBlock = $formatterHelper->formatBlock($errorMessages, 'error');
					$output->writeln($formattedBlock);

					$output->writeln("<comment>Addon names available:</comment>");

					foreach($addons as $addonName => $addonClass) {
						$output->writeln("<comment>- ".$addonName."</comment>");
					}

					return 1;
				}
			}

			$Factory_Configuration = new $addonClass($this, $input, $output);
			$status = $Factory_Configuration->factory();

			if($status) {
				$exitMessage = "Do not forget to create luncher '".$addonType."' '".$addonName."' ";
				$exitMessage .= "with console and command launcher:application:factory";
				$output->writeln("<comment>".$exitMessage."</comment>");
				return 0;
			}
			else {
				return 1;
			}
		}
	}