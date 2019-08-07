<?php
	namespace PhpCliShell\Console\Command;

	use PhpCliShell\Core as C;

	use PhpCliShell\Application\Firewall;

	use PhpCliShell\Console\Factory\Configuration;

	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputDefinition;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputOption;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class FirewallApplicationDemo extends Command
	{
		protected function configure()
		{
			$this
				->setName('firewall:application:demo')
				->setDescription('Firewall application demo');
		}

		protected function execute(InputInterface $input, OutputInterface $output)
		{
			$formatter = $this->getHelper('formatter');

			define("APPLICATION_ROOT_DIR", PROJECT_ROOT_DIR.'/application/firewall');

			define("ROOT_DIR", WORKING_ROOT_DIR);
			define("APP_DIR", APPLICATION_ROOT_DIR);

			$configurations = array(
				APPLICATION_ROOT_DIR .'/configurations/demo.json'
			);

			$CONFIG = C\Config::getInstance();
			$CONFIG->loadConfigurations($configurations, false);
			$appConfig = $CONFIG->{Configuration\Network\Firewall::CONFIG_SECTION_ROOT_NAME};

			$pathConfig = $appConfig->configuration->paths;
			$localConfig = $appConfig->sites->datacenter_A->zones->LOCAL->toObject();

			$helpMessage1 = "In this mode, you can test firewall application but configuration is very light so".PHP_EOL;
			$helpMessage1 .= "there is not dual-site or complex network topology.".PHP_EOL.PHP_EOL;
			$helpMessage1 .= "On premise subnets are '".implode(', ', $localConfig->ipv4)."' and '".implode(', ', $localConfig->ipv6)."'.".PHP_EOL;
			$helpMessage1 .= "Exports are writed in '".C\Tools::filename($pathConfig->exports)."' directory".PHP_EOL;
			$helpMessage1 .= "Objects are writed in '".C\Tools::filename($pathConfig->objects)."' directory".PHP_EOL;
			$helpMessage1 .= "Configurations are writed in '".C\Tools::filename($pathConfig->configs)."' directory";

			$helpMessage2 = "Begin with command 'help'".PHP_EOL;
			$helpMessage2 .= "Copy commands below to your shell:".PHP_EOL.PHP_EOL;
			$helpMessage2 .= "site all".PHP_EOL;
			$helpMessage2 .= "create rule monosite".PHP_EOL;
			$helpMessage2 .= "status enable".PHP_EOL;
			$helpMessage2 .= "action permit".PHP_EOL;
			$helpMessage2 .= "description \"My first ACL\"".PHP_EOL;
			$helpMessage2 .= "tag icmp ping".PHP_EOL;
			$helpMessage2 .= "source subnet 0.0.0.0/0".PHP_EOL;
			$helpMessage2 .= "destination subnet 10.0.0.0/16".PHP_EOL;
			$helpMessage2 .= "protocol icmp".PHP_EOL;
			$helpMessage2 .= "show".PHP_EOL;
			$helpMessage2 .= "check".PHP_EOL;
			$helpMessage2 .= "exit".PHP_EOL;
			$helpMessage2 .= "show".PHP_EOL;
			$helpMessage2 .= "save myBackup".PHP_EOL;
			$helpMessage2 .= "export configuration juniper_junos-set";

			$output->writeln("");
			$output->writeln("<fg=black;bg=yellow;options=bold> .:| FIREWALL DEMO |:. </>");
			$output->writeln("<fg=black;bg=white;options=bold>".PHP_EOL.$helpMessage1."</>");	// PHP_EOL inside for workaround
			$output->writeln("<fg=white;bg=green;options=bold>".PHP_EOL.$helpMessage2."</>");	// PHP_EOL inside for workaround
			$output->writeln(PHP_EOL."<fg=yellow;options=bold>Enjoy !</>");
			$output->writeln("");

			$_SERVER['argc'] = 1;
			array_splice($_SERVER['argv'], 1);

			require_once(APPLICATION_ROOT_DIR . '/launcher/firewall.php');
			$lcFirewall = new Firewall\Launcher\Firewall();
			$shFirewall = new Firewall\Shell\Firewall($CONFIG);

			echo PHP_EOL;
			return 0;
		}
	}