<?php
	namespace PhpCliShell\Console\Factory\Configuration\Network;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Network as Net;

	use PhpCliShell\Addon\Ipam\Netbox;
	use PhpCliShell\Addon\Ipam\Phpipam;

	use PhpCliShell\Console\Factory\Configuration\AbstractConfiguration;

	use Symfony\Component\Console\Question\Question;
	use Symfony\Component\Console\Question\ChoiceQuestion;
	use Symfony\Component\Console\Question\ConfirmationQuestion;

	class Firewall extends AbstractConfiguration
	{
		const CONFIG_PATHNAME = self::DEFAULT_CONFIG_PATHNAME;
		const CONFIG_FILENAME = 'network_firewall.%s.json';

		/**
		  * @var string
		  */
		const CONFIG_SECTION_ROOT_NAME = 'NETWORK_FIREWALL';

		/**
		  * @var array
		  */
		const FACTORY_CONFIGURATION_IPAM = array(
			Netbox\Service::SERVICE_TYPE => Configuration\Ipam\Netbox::class,
			Phpipam\Service::SERVICE_TYPE => Configuration\Ipam\Phpipam::class,
		);


		/**
		  * @return bool
		  */
		public function factory()
		{
			$this->_initialization();
			$questionHelper = $this->_command->getHelper('question');

			// sites > [site]
			// --------------------------------------------------
			$this->_output->writeln('<fg=black;bg=white;options=bold>'.PHP_EOL.'FIREWALL SITES</>');

			$question = new Question('What is the firewall environment key? (Key must be unique. Only [A-Z0-9_-] characters are allowed) ', false, '#[0-9A-Z_\-]+#i');
			$envKey = $questionHelper->ask($this->_input, $this->_output, $question);

			if($envKey === false) {
				$this->_output->writeln('<error>Firewall environment key is not valid, only [A-Z0-9_-] characters are allowed</error>');
				return false;
			}

			$question = new Question('What is the firewall site key? (Key must be unique. Only [A-Z0-9_-] characters are allowed) ', false, '#[0-9A-Z_\-]+#i');
			$siteKey = $questionHelper->ask($this->_input, $this->_output, $question);

			if($siteKey === false) {
				$this->_output->writeln('<error>Firewall site key is not valid, only [A-Z0-9_-] characters are allowed</error>');
				return false;
			}

			$question = new Question('What is the firewall location name? (can be empty) ', '', '#[[:print:]]+#i');
			$location = $questionHelper->ask($this->_input, $this->_output, $question);

			$question = new Question('What is the firewall hostname? ', false, '#[[:print:]]+#i');
			$hostname = $questionHelper->ask($this->_input, $this->_output, $question);

			if($hostname === false) {
				$this->_output->writeln('<error>Firewall hostname is not valid and require it</error>');
				return false;
			}

			$question = new Question('What is the firewall address IP? (IP v4 or v6) ', false, '#[0-9A-F.:]+#i');
			$ip = $questionHelper->ask($this->_input, $this->_output, $question);

			if($ip === false || !Net\Tools::isIP($ip)) {
				$this->_output->writeln('<error>Firewall address IP is not valid and require it</error>');
				return false;
			}

			$question = new ChoiceQuestion('What is the firewall OS? ', ['juniper-junos', 'cisco-asa'], null);
			$os = $questionHelper->ask($this->_input, $this->_output, $question);

			if($os === false) {
				$this->_output->writeln('<error>Firewall OS is not valid and require it</error>');
				return false;
			}

			$question = new ChoiceQuestion('What is the firewall GUI access method? ', ['ssh', 'jnlp', 'http', 'https'], 0);
			$guiMethod = $questionHelper->ask($this->_input, $this->_output, $question);

			$question = new Question('What is the firewall GUI address? (can be empty and use IP address instead of GUI address) ', false, '#[[:print:]]+#i');
			$guiAddress = $questionHelper->ask($this->_input, $this->_output, $question);

			$question = new Question('What is the firewall WAN zone name? [WAN] ', 'WAN', '#[[:print:]]+#i');
			$wanZone = $questionHelper->ask($this->_input, $this->_output, $question);

			if($wanZone === false) {
				$this->_output->writeln('<error>Firewall WAN zone is not valid and require it</error>');
				return false;
			}

			$question = new Question('What is the firewall OnPremise zone name? [LOCAL] ', 'LOCAL', '#[[:print:]]+#i');
			$onPremiseZone = $questionHelper->ask($this->_input, $this->_output, $question);

			if($onPremiseZone === false) {
				$this->_output->writeln('<error>Firewall OnPremise zone is not valid and require it</error>');
				return false;
			}

			$question = new Question('What is the firewall OnPremise IPv4 subnet? (CIDR) ', false, '#[[:print:]]+#i');
			$onPremiseIPv4 = $questionHelper->ask($this->_input, $this->_output, $question);

			if($onPremiseIPv4 === false || !Net\Tools::isSubnetV4($onPremiseIPv4)) {
				$this->_output->writeln('<error>Firewall OnPremise IPv4 subnet is not valid and require it</error>');
				return false;
			}

			$question = new Question('What is the firewall OnPremise IPv6 subnet? (can be empty) ', false, '#[[:print:]]+#i');
			$onPremiseIPv6 = $questionHelper->ask($this->_input, $this->_output, $question);

			if($onPremiseIPv6 !== false && !Net\Tools::isSubnetV6($onPremiseIPv6)) {
				$this->_output->writeln('<error>Firewall OnPremise IPv6 subnet is not valid</error>');
				return false;
			}

			if($os === 'cisco-asa') {
				$question = new Question('What is the firewall global zone name? [global] ', 'global', '#[[:print:]]+#i');
				$globalZone = $questionHelper->ask($this->_input, $this->_output, $question);
			}
			else {
				$globalZone = false;
			}
			// --------------------------------------------------

			// services > ipam
			// --------------------------------------------------
			$this->_output->writeln('<fg=black;bg=white;options=bold>'.PHP_EOL.'IPAM SERVICES</>');

			$question = new ChoiceQuestion('Do you want enable IPAM features (autocompletion, searches, ...)? ', ['no' => 'Do not use IPAM', 'yes' => 'I want to use IPAM'], null);
			$useIpam = $questionHelper->ask($this->_input, $this->_output, $question);

			if($useIpam === false) {
				$this->_output->writeln('<error>Unable to get your answer, please open an issue</error>');
				return false;
			}
			else {
				$useIpam = self::HUMAN_BOOL[$useIpam];
			}

			if($useIpam !== false)
			{
				$ipamServices = array();
				$ipamConnectors = array_keys(self::FACTORY_CONFIGURATION_IPAM);

				do
				{
					$question = new ChoiceQuestion('Which IPAM connector do you want use? ', $ipamConnectors, null);
					$ipamApplication = $questionHelper->ask($this->_input, $this->_output, $question);

					$question = new Question('What is the configuration key name of your IPAM service? (Key must be unique. Only [A-Z0-9_-] characters are allowed) ', false, '#[0-9A-Z_\-]+#i');
					$ipamServerKey = $questionHelper->ask($this->_input, $this->_output, $question);

					if($ipamApplication !== false && $ipamServerKey !== false) {
						$ipamServices[$ipamApplication][] = $ipamServerKey;
					}
					else {
						$this->_output->writeln('<error>Unable to get your answer, please open an issue</error>');
						return false;
					}

					$question = new ChoiceQuestion('Do you have another IPAM service to enable? ', ['no' => 'Continue to next wizard step', 'yes' => 'I have another IPAM service'], null);
					$anotherIpam = $questionHelper->ask($this->_input, $this->_output, $question);

					if($anotherIpam === false) {
						$this->_output->writeln('<error>Unable to get your answer, please open an issue</error>');
						return false;
					}
					else {
						$anotherIpam = self::HUMAN_BOOL[$anotherIpam];
					}
				}
				while($anotherIpam);
			}
			else {
				$ipamServices = false;
			}
			// --------------------------------------------------

			// configuration
			// --------------------------------------------------
			$objectPath = 'backup/firewall/objects.json';
			$configPath = 'backup/firewall/configurations';
			$exportPath = 'tmp/firewall';
			$autosavePath = 'backup/firewall/autosave.json';
			$autosaveStatus = false;
			$junosUpdateMode = 'replace';
			// --------------------------------------------------

			// Build configuration
			// --------------------------------------------------
			$userConfig = array(
				self::CONFIG_SECTION_ROOT_NAME => array(
					'sites' => array(
						$siteKey => array(
							'location' => $location,
							'hostname' => $hostname,
							'ip' => $ip,
							'os' => $os,
							'gui' => $guiMethod,
							'scp' => false,
							'scp_loginCredential' => false,
							'scp_loginEnvVarName' => false,
							'scp_passwordCredential' => false,
							'scp_passwordEnvVarName' => false,
							'scp_remoteFile' => false,
							'ssh_remotePort' => false,
							'ssh_bastionHost' => false,
							'ssh_bastionPort' => false,
							'ssh_portForwarding' => false,
							'ssh_loginCredential' => false,
							'ssh_loginEnvVarName' => false,
							'ssh_passwordCredential' => false,
							'ssh_passwordEnvVarName' => false,
							'zones' => array(
								$wanZone => [
									'ipv4' => [ "0.0.0.0/0" ],
									'ipv6' => [ "::/0" ]
								],
								$onPremiseZone => [
									'ipv4' => [ $onPremiseIPv4 ],
									'ipv6' => [ ]
								],
								'__PRIVATE__' => [
									"ipv4" => [ "10.0.0.0/8", "172.16.0.0/12", "192.168.0.0/16" ],
									"ipv6" => [ ]
								]
							),
							'topology' => array(
								'onPremise' => [ $onPremiseZone ],
								'interSite' => [ ],
								'private' => [ '__PRIVATE__' ],
								'internet' => [ $wanZone ]
							),
							'metadata' => array(),
							'options' => array()
						)
					),
					'services' => array(
						'ipam' => $ipamServices,
					),
					'configuration' => array(
						'paths' => array(
							'objects' => $objectPath,
							'configs' => $configPath,
							'exports' => $exportPath,
							'autosave' => $autosavePath,
							'templates' => 'templates/firewall'
						),
						'autosave' => array(
							'status' => $autosaveStatus
						),
						'templates' => array(
							'juniper-junos_set' => array(
								'updateMode' => $junosUpdateMode
							)
						)
					)
				)
			);

			if($guiAddress !== false) {
				$userConfig[self::CONFIG_SECTION_ROOT_NAME]['sites'][$siteKey][$guiMethod] = $guiAddress;
			}

			if($onPremiseIPv6 !== false) {
				$userConfig[self::CONFIG_SECTION_ROOT_NAME]['sites'][$siteKey]['zones'][$onPremiseZone]['ipv6'][] = $onPremiseIPv6;
			}

			if($globalZone !== false) {
				$userConfig[self::CONFIG_SECTION_ROOT_NAME]['sites'][$siteKey]['options']['globalZone'] = $globalZone;
			}

			$config = $this->_prepareConfiguration($envKey);
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