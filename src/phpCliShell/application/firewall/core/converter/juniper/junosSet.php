<?php
	namespace PhpCliShell\Application\Firewall\Core\Converter\Juniper;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core;
	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Core\Template;
	use PhpCliShell\Application\Firewall\Core\Exception;
	use PhpCliShell\Application\Firewall\Core\Converter\Appliance;

	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Component\Resolver;

	use Symfony\Component\Console\Question\Question;
	use Symfony\Component\Console\Question\ChoiceQuestion;
	use Symfony\Component\Console\Question\ConfirmationQuestion;

	class JunosSet extends Appliance
	{
		/**
		  * @var array
		  */
		const COMMANDS = array(
			'applications' => 'show configuration applications | display xml | no-more',
			'addressBook' => 'show configuration security zones | display xml | no-more',
			'policies' => 'show configuration security policies | display xml | no-more',
			'translations' => 'show configuration security nat | display xml | no-more',
		);

		/**
		  * @var array
		  */
		const APPLICATIONS = array(
			'junos-ftp' => 'tcp/21',
			'junos-tftp' => 'udp/69',
			'junos-rtsp' => 'tcp/554',
			'junos-netbios-session' => 'tcp/139',
			'junos-smb-session' => 'tcp/445',
			'junos-ssh' => 'tcp/22',
			'junos-telnet' => 'tcp/23',
			'junos-smtp' => 'tcp/25',
			'junos-tacacs' => 'tcp/49',
			'junos-tacacs-ds' => 'tcp/65',
			'junos-dhcp-client' => 'udp/68',
			'junos-dhcp-server' => 'udp/67',
			'junos-bootpc' => 'udp/68',
			'junos-bootps' => 'udp/67',
			'junos-finger' => 'tcp/79',
			'junos-http' => 'tcp/80',
			'junos-https' => 'tcp/443',
			'junos-pop3' => 'tcp/110',
			'junos-ident' => 'tcp/113',
			'junos-nntp' => 'tcp/119',
			'junos-ntp' => 'udp/123',
			'junos-imap' => 'tcp/143',
			'junos-imaps' => 'tcp/993',
			'junos-bgp' => 'tcp/179',
			'junos-ldap' => 'tcp/389',
			'junos-snpp' => 'tcp/444',
			'junos-biff' => 'udp/512',
			'junos-who' => 'udp/513',
			'junos-syslog' => 'udp/514',
			'junos-printer' => 'tcp/515',
			'junos-rip' => 'udp/520',
			'junos-radius' => 'udp/1812',
			'junos-radacct' => 'udp/1813',
			'junos-nfsd-tcp' => 'tcp/2049',
			'junos-nfsd-udp' => 'udp/2049',
			'junos-cvspserver' => 'tcp/2401',
			'junos-ldp-tcp' => 'tcp/646',
			'junos-ldp-udp' => 'udp/646',
			'junos-xnm-ssl' => 'tcp/3220',
			'junos-xnm-clear-text' => 'tcp/3221',
			'junos-ike' => 'udp/500',
			'junos-aol' => 'tcp/5190-5193',
			'junos-chargen' => 'udp/19',
			'junos-dhcp-relay' => 'udp/67',
			'junos-discard' => 'udp/9',
			'junos-dns-udp' => 'udp/53',
			'junos-dns-tcp' => 'tcp/53',
			'junos-echo' => 'udp/7',
			'junos-gopher' => 'tcp/70',
			'junos-gnutella' => 'udp/6346-6347',
			'junos-gre' => '47',
			'junos-gprs-gtp-c-tcp' => 'tcp/2123',
			'junos-gprs-gtp-c-udp' => 'udp/2123',
			'junos-gprs-gtp-c' => array(
				'tcp/2123',
				'udp/2123',
			),
			'junos-gprs-gtp-u-tcp' => 'tcp/2152',
			'junos-gprs-gtp-u-udp' => 'udp/2152',
			'junos-gprs-gtp-u' => array(
				'tcp/2152',
				'udp/2152',
			),
			'junos-gprs-gtp-v0-tcp' => 'tcp/3386',
			'junos-gprs-gtp-v0-udp' => 'udp/3386',
			'junos-gprs-gtp-v0' => array(
				'tcp/3386',
				'udp/3386',
			),
			'junos-gprs-sctp' => '132',
			'junos-http-ext' => 'tcp/7001',
			'junos-icmp-all' => 'icmp',
			'junos-icmp-ping' => 'icmp/8',
			'junos-internet-locator-service' => 'tcp/389',
			'junos-ike-nat' => 'udp/4500',
			'junos-irc' => 'tcp/6660-6669',
			'junos-l2tp' => 'udp/1701',
			'junos-lpr' => 'tcp/515',
			'junos-mail' => 'tcp/25',
			'junos-mgcp-ua' => 'udp/2427',
			'junos-mgcp-ca' => 'udp/2727',
			'junos-msn' => 'tcp/1863',
			'junos-ms-rpc-tcp' => 'tcp/135',
			'junos-ms-rpc-udp' => 'udp/135',
			'junos-ms-sql' => 'tcp/1433',
			'junos-nbname' => 'udp/137',
			'junos-nbds' => 'udp/138',
			'junos-nfs' => 'udp/111',
			'junos-ns-global' => 'tcp/15397',
			'junos-ns-global-pro' => 'tcp/15397',
			'junos-nsm' => 'udp/69',
			'junos-ospf' => '89',
			'junos-pc-anywhere' => 'udp/5632',
			'junos-ping' => 'icmp',									   
			'junos-pingv6' => '58',
			'junos-icmp6-dst-unreach-addr' => '58/1:3',
			'junos-icmp6-dst-unreach-admin' => '58/1:1',
			'junos-icmp6-dst-unreach-beyond' => '58/1:2',
			'junos-icmp6-dst-unreach-port' => '58/1:4',
			'junos-icmp6-dst-unreach-route' => '58/1:0',
			'junos-icmp6-echo-reply' => '58/129',
			'junos-icmp6-echo-request' => '58/128',
			'junos-icmp6-packet-too-big' => '58/2:0',
			'junos-icmp6-param-prob-header' => '58/4:0',
			'junos-icmp6-param-prob-nexthdr' => '58/4:1',									   
			'junos-icmp6-param-prob-option' => '58/4:2',
			'junos-icmp6-time-exceed-reassembly' => '58/3:1',
			'junos-icmp6-time-exceed-transit' => '58/3:0',
			'junos-icmp6-all' => '58',
			'junos-pptp' => 'tcp/1723',
			'junos-realaudio' => 'tcp/554',
			'junos-sccp' => 'tcp/2000',
			'junos-sctp-any' => '132',
			'junos-rsh' => 'tcp/514',
			'junos-sql-monitor' => 'udp/1434',
			'junos-sqlnet-v1' => 'tcp/1525',
			'junos-sqlnet-v2' => 'tcp/1521',
			'junos-sun-rpc-tcp' => 'tcp/111',
			'junos-sun-rpc-udp' => 'udp/111',
			'junos-tcp-any' => 'tcp',
			'junos-udp-any' => 'udp',
			'junos-uucp' => 'udp/540',
			'junos-vdo-live' => 'udp/7000-7010',
			'junos-vnc' => 'tcp/5800',
			'junos-wais' => 'tcp/210',
			'junos-whois' => 'tcp/43',
			'junos-winframe' => 'tcp/1494',
			'junos-x-windows' => 'tcp/6000-6063',
			'junos-wxcontrol' => 'tcp/3578',
			'junos-snmp-agentx' => 'tcp/705',
			'junos-r2cp' => 'udp/28672',
			'junos-sip' => array(
				'udp/5060',
				'tcp/5060',
			),
			'junos-smb' => array(
				'tcp/139',
				'tcp/445',
			),
			'junos-talk' => array(
				'udp/517',
				'tcp/517',
			),
			'junos-ntalk' => array(
				'udp/518',
				'tcp/518',
			),
			'junos-ymsg' => array(
				'tcp/5000-5010',
				'tcp/5050',
				'udp/5000-5010',
				'udp/5050',
			),
			'junos-stun' => array(
				'udp/3478-3479',
				'tcp/3478-3479',
			),
			'junos-h323' => array(
				'tcp/1720',
				'udp/1719',
				'tcp/1503',
				'tcp/389',
				'tcp/522',
				'tcp/1731',
			),
			'junos-routing-inbound' => array(
				'tcp/179',
				'udp/520',
				'tcp/646',
				'udp/646',
			),
			'junos-cifs' =>  array(
				'tcp/139',
				'tcp/445',
			),
			'junos-gprs-gtp' =>  array(
				'tcp/2123',
				'udp/2123',
				'tcp/2152',
				'udp/2152',
				'tcp/3386',
				'udp/3386',
			),
			'junos-mgcp' =>  array(
				'udp/2427',
				'udp/2727',
			),
			'junos-ms-rpc' => array(
				'tcp/135',
				'udp/135',
			),
			'junos-sun-rpc' => array(
				'tcp/111',
				'udp/111',
			),
		);

		/**
		  * https://www.iana.org/assignments/icmp-parameters/icmp-parameters.xhtml
		  *
		  * @var array
		  */
		const APP_ICMPv4_TYPES = array(
			0 => 'echo-reply',
			3 => 'unreachable',
			4 => 'source-quench',
			5 => 'redirect',
			8 => 'echo-request',
			9 => 'router-advertisement',
			10 => 'router-solicit',
			11 => 'time-exceeded',
			12 => 'parameter-problem',
			13 => 'timestamp',
			14 => 'timestamp-reply',
			15 => 'info-request',
			16 => 'info-reply',
			17 => 'mask-request',
			18 => 'mask-reply',
		);

		/**
		  * https://www.iana.org/assignments/icmp-parameters/icmp-parameters.xhtml
		  *
		  * @var array
		  */
		const APP_ICMPv4_CODES = array(
			3 => array(
				0 => 'network-unreachable',
				1 => 'host-unreachable',
				2 => 'protocol-unreachable',
				3 => 'port-unreachable',
				4 => 'fragmentation-needed',
				5 => 'source-route-failed',
				6 => 'destination-network-unknown',
				7 => 'destination-host-unknown',
				8 => 'source-host-isolated',
				9 => 'destination-network-prohibited',
				10 => 'destination-host-prohibited',
				11 => 'network-unreachable-for-tos',
				12 => 'host-unreachable-for-tos',
				13 => 'communication-prohibited-by-filtering',
				14 => 'host-precedence-violation',
				15 => 'precedence-cutoff-in-effect',
			),
			5 => array(
				0 => 'redirect-for-network',
				1 => 'redirect-for-host',
				2 => 'redirect-for-tos-and-net',
				3 => 'redirect-for-tos-and-host',
			),
			11 => array(
				0 => 'ttl-eq-zero-during-transit',
				1 => 'ttl-eq-zero-during-reassembly',
			),
			12 => array(
				0 => 'ip-header-bad',
				1 => 'required-option-missing',
			),
		);

		/**
		  * https://www.iana.org/assignments/icmpv6-parameters/icmpv6-parameters.xhtml
		  *
		  * @var array
		  */
		const APP_ICMPv6_TYPES = array(
			1 => 'destination-unreachable',
			2 => 'packet-too-big',
			3 => 'time-exceeded ',
			4 => 'parameter-problem',
			128 => 'echo',
			129 => 'echo-reply',
			130 => 'membership-query',
			131 => 'membership-report',
			132 => 'membership-termination',
			133 => 'router-solicit',
			134 => 'router-advertisement',
			135 => 'neighbor-solicit ',
			136 => 'neighbor-advertisement',
			137 => 'redirect',
			138 => 'router-renumbering',
			139 => 'node-information-request',
			140 => 'node-information-reply',
		);

		/**
		  * https://www.iana.org/assignments/icmpv6-parameters/icmpv6-parameters.xhtml
		  *
		  * @var array
		  */
		const APP_ICMPv6_CODES = array(
			1 => array(
				0 => 'no-route-to-destination',
				1 => 'administratively-prohibited',
				3 => 'address-unreachable',
				4 => 'port-unreachable',
			),
			3 => array(
				0 => 'ttl-eq-zero-during-transit',
				1 => 'ttl-eq-zero-during-reassembly',
			),
			4 => array(
				0 => 'ip6-header-bad',
				1 => 'unrecognized-next-header',
				2 => 'unrecognized-option',
			),
		);

		/**
		  * @var array
		  */
		const APP_ICMP_TYPES = array(
			4 => self::APP_ICMPv4_TYPES,
			6 => self::APP_ICMPv6_TYPES,
		);

		/**
		  * @var array
		  */
		const APP_ICMP_CODES = array(
			4 => self::APP_ICMPv4_CODES,
			6 => self::APP_ICMPv6_CODES,
		);

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\Address
		  */
		protected $_addressManager = null;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\AclRule
		  */
		protected $_aclRuleManager = null;

		/**
		  * @var array
		  */
		protected $_addresses = array();

		/**
		  * @var array
		  */
		protected $_protocols = array();

		/**
		  * @var array
		  */
		protected $_aclRules = array();

		/**
		  * @var array
		  */
		protected $_natRules = array();


		/**
		  * @param \PhpCliShell\Shell\Service\Main $SHELL
		  * @param \PhpCliShell\Core\Config $siteConfig
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Core\Site $site)
		{
			parent::__construct($SHELL, $site);

			$this->_addressManager = Resolver::getManager(Manager\Address::class);
			$this->_aclRuleManager = Resolver::getManager(Manager\AclRule::class);
			$this->_natRuleManager = Resolver::getManager(Manager\NatRule::class);
		}

		/**
		  * @return void
		  */
		protected function _reset()
		{
			$this->_addresses = array();
			$this->_protocols = array();
			$this->_aclRules = array();
			$this->_natRules = array();
		}

		public function loadFromRemote(C\Network\Ssh $remoteSSH)
		{
			$this->_reset();

			if($remoteSSH->isRunning)
			{
				if($remoteSSH->isConnected)
				{
					if($remoteSSH->isAuthenticated)
					{
						$command = self::COMMANDS['applications'];
						$this->_SHELL->print("Execute: ".$command, 'green');
						$status = $remoteSSH->exec($command, $stdout, $stderr);

						if($status && $stderr === '') {
							$applications = $remoteSSH->stdoutCleaner($stdout, $command);
						}
						else {
							throw new E\Message("Unable to retrieve applications configuration from remote firewall", E_USER_WARNING);
						}

						$command = self::COMMANDS['addressBook'];
						$this->_SHELL->print("Execute: ".$command, 'green');
						$status = $remoteSSH->exec($command, $stdout, $stderr);

						if($status && $stderr === '') {
							$addressBook = $remoteSSH->stdoutCleaner($stdout, $command);
						}
						else {
							throw new E\Message("Unable to retrieve addressBook configuration from remote firewall", E_USER_WARNING);
						}

						$command = self::COMMANDS['policies'];
						$this->_SHELL->print("Execute: ".$command, 'green');
						$status = $remoteSSH->exec($command, $stdout, $stderr);

						if($status && $stderr === '') {
							$policies = $remoteSSH->stdoutCleaner($stdout, $command);
						}
						else {
							throw new E\Message("Unable to retrieve policies (ACL) configuration from remote firewall", E_USER_WARNING);
						}

						/*$command = self::COMMANDS['translations'];
						$this->_SHELL->print("Execute: ".$command, 'green');
						$status = $remoteSSH->exec($command, $stdout, $stderr);

						if($status && $stderr === '') {
							$translations = $remoteSSH->stdoutCleaner($stdout, $command);
						}
						else {
							throw new E\Message("Unable to retrieve translations (NAT) configuration from remote firewall", E_USER_WARNING);
						}*/
						$translations = null;

						/**
						  * Permet d'importer deux sites dans la même instance
						  * avec des objets de même nom mais d'adressages différents
						  */
						$siteName = $this->_site->getName();

						return $this->_load($siteName, $applications, $addressBook, $policies, $translations);
					}
					else {
						throw new E\Message("SSH session is not authenticated", E_USER_WARNING);
					}
				}
				else {
					throw new E\Message("SSH session is not connected", E_USER_WARNING);
				}
			}
			else {
				throw new E\Message("SSH session is not running", E_USER_WARNING);
			}
		}

		public function loadFromFiles()
		{
			$helperFilesystem = function ($answer) {
				return $this->_SHELL->program->shellAutoC_filesystem(null, $answer);
			};

			$cliTerminalQuestion = new Cli\Terminal\Question();
			
			$question = "Where is your file with '".self::COMMANDS['applications']."' command result? (pathname)";
			$applicationsFile = $cliTerminalQuestion->assistQuestion($question, $helperFilesystem);

			if(C\Tools::is('string&&!empty', $applicationsFile))
			{
				if(file_exists($applicationsFile) && is_readable($applicationsFile))
				{
					$applications = file_get_contents($applicationsFile);

					if($applications === false) {
						throw new E\Message("Unable to get applications file contents", E_USER_WARNING);
					}
				}
				else {
					throw new E\Message("Applications file '".$applicationsFile."' does not exist or is not readable", E_USER_WARNING);
				}
			}
			else {
				return false;
			}

			$question = "Where is your file with '".self::COMMANDS['addressBook']."' command result? (pathname)";
			$addressBookFile = $cliTerminalQuestion->assistQuestion($question, $helperFilesystem);

			if(C\Tools::is('string&&!empty', $addressBookFile))
			{
				if(file_exists($addressBookFile) && is_readable($addressBookFile))
				{
					$addressBook = file_get_contents($addressBookFile);

					if($addressBook === false) {
						throw new E\Message("Unable to get addressBook file contents", E_USER_WARNING);
					}
				}
				else {
					throw new E\Message("AddressBook file '".$addressBookFile."' does not exist or is not readable", E_USER_WARNING);
				}
			}
			else {
				return false;
			}

			$question = "Where is your file with '".self::COMMANDS['policies']."' command result? (pathname, leave empty to not import)";
			$policiesFile = $cliTerminalQuestion->assistQuestion($question, $helperFilesystem);

			if($policiesFile !== false)
			{
				if($policiesFile !== '')
				{
					if(file_exists($policiesFile) && is_readable($policiesFile))
					{
						$policies = file_get_contents($policiesFile);

						if($policies === false) {
							throw new E\Message("Unable to get policies file contents", E_USER_WARNING);
						}
					}
					else {
						throw new E\Message("Policies file '".$policiesFile."' does not exist or is not readable", E_USER_WARNING);
					}
				}
				else {
					$policies = null;
				}
			}
			else {
				return false;
			}

			/*$question = "Where is your file with '".self::COMMANDS['translations']."' command result? (pathname, leave empty to not import)";
			$translationsFile = $cliTerminalQuestion->assistQuestion($question, $helperFilesystem);

			if($translationsFile !== false)
			{
				if($translationsFile !== '')
				{
					if(file_exists($translationsFile) && is_readable($translationsFile))
					{
						$translations = file_get_contents($translationsFile);

						if($translations === false) {
							throw new E\Message("Unable to get translations file contents", E_USER_WARNING);
						}
					}
					else {
						throw new E\Message("Translations file '".$translationsFile."' does not exist or is not readable", E_USER_WARNING);
					}
				}
				else {
					$translations = null;
				}
			}
			else {
				return false;
			}*/
			$translations = null;

			/**
			  * Permet d'importer deux sites dans la même instance
			  * avec des objets de même nom mais d'adressages différents
			  */
			$siteName = $this->_site->getName();

			return $this->_load($siteName, $applications, $addressBook, $policies, $translations);
		}

		protected function _load($siteName, $applications, $addressBook, $policies, $translations)
		{
			$applications = $this->_prepareApplications($applications);
			$addressBook = $this->_prepareAddressBook($addressBook);
			$policies = $this->_preparePolicies($policies);
			$translations = $this->_prepareTranslations($translations);

			$siteAddressBooks = array();
			$siteAddressBooks['objects'][$siteName] = $addressBook['objects'];
			$siteAddressBooks['groups'][$siteName] = $addressBook['groups'];

			$this->_protocols = $this->_convertToProtocols($applications);
			$this->_addresses = $this->_convertToAddresses($siteAddressBooks);
			$this->_aclRules = $this->_convertToAclRules($siteName, $siteAddressBooks, $policies);
			$this->_natRules = $this->_convertToNatRules($siteName, $siteAddressBooks, $translations);

			return array(
				Api\AclRule::class => count($this->_aclRules),
				Api\NatRule::class => count($this->_natRules),
			);
		}

		/**
		  * @param null|string $xmlConfig XML
		  * @return array Applications
		  */
		protected function _prepareApplications($xmlConfig)
		{
			$applications = array();

			if($xmlConfig !== null)
			{
				$XML = new C\Xml($xmlConfig);
				$datas = $XML->toArray();

				$apps = $datas['rpc-reply'][0]['configuration'][0]['applications'][0]['application'];

				foreach($apps as $application)
				{
					$name = $application['name'][0]['value'];
					$name = $this->_applicationNameCleaner($name);

					if(!array_key_exists('term', $application)) {
						$applications['term'] = array($application);
					}

					foreach($applications['term'] as $term)
					{
						$protocol = $term['protocol'][0]['value'];

						switch($protocol)
						{
							case 'tcp':
							case 'udp':
							{
								if(isset($term['source-port'])) {
									throw new E\Message("Unable to convert TCP/UDP application '".$name."' with source port, it is not implemented", E_USER_WARNING);
								}

								if(isset($term['destination-port'][0]['value']))
								{
									$destPort = $term['destination-port'][0]['value'];
									$destPort = str_replace('-', Api\Protocol::PROTO_RANGE_SEPARATOR, $destPort);

									$protocol .= Api\Protocol::PROTO_SEPARATOR.$destPort;
								}
							}
							case 'icmp':
							case 'icmp4':
							case 'icmp6':
							{
								$version = (substr($protocol, -1, 1) === '6') ? (6) : (4);
								$v = ($version === 6) ? ('6') : ('');
								$icmpTypeField = 'icmp'.$v.'-type';
								$icmpCodeField = 'icmp'.$v.'-code';

								if(isset($term[$icmpTypeField][0]['value']))
								{
									$type = $term[$icmpTypeField][0]['value'];

									if(!C\Tools::is('int', $type))
									{
										$type = array_search($type, self::APP_ICMP_TYPES[$version], true);

										if($type === false) {
											throw new E\Message("Unable to convert ICMP application '".$name."' type '".$term[$icmpTypeField][0]['value']."'", E_USER_WARNING);
										}
									}

									$protocol .= Api\Protocol::PROTO_SEPARATOR.$type;

									if(isset($term[$icmpCodeField][0]['value']))
									{
										$code = $term[$icmpCodeField][0]['value'];

										if(!C\Tools::is('int', $code))
										{
											if(array_key_exists($type, self::APP_ICMP_CODES[$version]))
											{
												$code = array_search($code, self::APP_ICMP_CODES[$version][$type], true);

												if($code === false) {
													throw new E\Message("Unable to convert ICMP application '".$name."' code '".$term[$icmpCodeField][0]['value']."'", E_USER_WARNING);
												}
											}
											else {
												throw new E\Message("Unable to convert ICMP application '".$name."' type '".$type."' code '".$term[$icmpCodeField][0]['value']."'", E_USER_WARNING);
											}
										}

										$protocol .= Api\Protocol::PROTO_OPTIONS_SEPARATOR.$code;
									}
								}
								elseif(isset($term[$icmpCodeField])) {
									throw new E\Message("Unable to convert ICMP application '".$name."' with only code, require type", E_USER_WARNING);
								}
							}
						}

						$applications[$name][] = $protocol;
					}
				}
			}

			return $applications;
		}

		/**
		  * @param null|string $xmlConfig XML
		  * @return array AddressBook
		  */
		protected function _prepareAddressBook($xmlConfig)
		{
			$addressBooks = array(
				'objects' => array(),
				'groups' => array(),
			);

			if($xmlConfig !== null)
			{
				$XML = new C\Xml($xmlConfig);
				$datas = $XML->toArray();

				$zones = $datas['rpc-reply'][0]['configuration'][0]['security'][0]['zones'];

				foreach($zones as $zone)
				{
					foreach($zone['security-zone'] as $securityZone)
					{
						$zoneName = $securityZone['name'][0]['value'];

						// workaround for default any objects
						$addressBooks['objects'][$zoneName]['any'] = array('0.0.0.0/0', '0::/0');
						$addressBooks['objects'][$zoneName]['any-ipv4'] = array('0.0.0.0/0');
						$addressBooks['objects'][$zoneName]['any-ipv6'] = array('0::/0');

						$base = $securityZone['address-book'][0];

						foreach($base['address'] as $address) {
							$name = $this->_addressNameCleaner($address['name'][0]['value']);
							$addressBooks['objects'][$zoneName][$name][] = $address['ip-prefix'][0]['value'];
						}

						if(array_key_exists('address-set', $base))
						{
							foreach($base['address-set'] as $addressSet)
							{
								foreach($addressSet['address'] as $address) {
									$name = $this->_addressNameCleaner($address['name'][0]['value']);
									$addressBooks['groups'][$zoneName][$addressSet['name'][0]['value']][] = $name;
								}
							}
						}
					}
				}
			}

			return $addressBooks;
		}

		/**
		  * @param null|string $xmlConfig XML
		  * @return array Policies
		  */
		protected function _preparePolicies($xmlConfig)
		{
			$policies = array();

			if($xmlConfig !== null)
			{
				$XML = new C\Xml($xmlConfig);
				$datas = $XML->toArray();

				$rules = $datas['rpc-reply'][0]['configuration'][0]['security'][0]['policies'];

				foreach($rules as $zones)
				{
					foreach($zones['policy'] as $zone)
					{
						$sourceZoneName = $zone['from-zone-name'][0]['value'];
						$destinationZoneName = $zone['to-zone-name'][0]['value'];

						foreach($zone['policy'] as $policy)
						{
							$rule = array();
							$rule['__sourceZoneName__'] = $sourceZoneName;
							$rule['__destinationZoneName__'] = $destinationZoneName;

							// <policy inactive="inactive">
							$rule['status'] = (!isset($policy['__attrs__']['inactive']));
							$rule['action'] = array_key_exists('permit', $policy['then'][0]);
							$rule['name'] = (array_key_exists('name', $policy)) ? ($policy['name'][0]['value']) : ('');
							$rule['description'] = (array_key_exists('description', $policy)) ? ($policy['description'][0]['value']) : ('');

							$rule['tags'] = array();

							if(array_key_exists('log', $policy['then'][0])) {
								$rule['tags'][] = 'logging';
							}

							if(array_key_exists('count', $policy['then'][0])) {
								$rule['tags'][] = 'counter';
							}

							foreach(array('source-address' => 'sources', 'destination-address' => 'destinations') as $attribute => $attributes)
							{
								foreach($policy['match'][0][$attribute] as $add) {
									$rule[$attributes][] = $this->_addressNameCleaner($add['value']);
								}

								$rule[$attributes] = array_unique($rule[$attributes]);
							}

							foreach($policy['match'][0]['application'] as $app) {
								$rule['protocols'][] = $this->_applicationNameCleaner($app['value']);
							}

							$policies[] = $rule;
						}
					}
				}
			}

			return $policies;
		}

		/**
		  * @param null|string $xmlConfig XML
		  * @return array Translations
		  */
		protected function _prepareTranslations($xmlConfig)
		{
			$translations = array();

			if($xmlConfig !== null)
			{
				$XML = new C\Xml($xmlConfig);
				$datas = $XML->toArray();
			}

			return $translations;
		}

		protected function _convertToProtocols(array $applications)
		{
			return $applications;
		}

		protected function _convertToAddresses(array $addressBooks)
		{
			$addresses = array();
			$addressesTmp = array();

			foreach($addressBooks['objects'] as $site => &$siteAddressBook)
			{
				foreach($siteAddressBook as $zone => &$addressBook)
				{
					foreach($addressBook as $name => $_addresses)
					{
						if(count($_addresses) > 2)	{
							throw new Exception("Address '".$name."' has more than 2 IPs: ".implode(', ', $_addresses), E_USER_ERROR);
						}

						$id = $site."___".$zone."___".$name;

						usort($_addresses, function ($a, $b) use ($_addresses)
						{
							$aIsIpv4 = (strpos($a, '.') !== false);
							$bIsIpv4 = (strpos($b, '.') !== false);
							$aIsIpv6 = (strpos($a, ':') !== false);
							$bIsIpv6 = (strpos($b, ':') !== false);

							if(($aIsIpv4 && $bIsIpv4) || ($aIsIpv6 && $bIsIpv6)) {
								return 0;
							}
							elseif($aIsIpv4) {
								return -1;
							}
							elseif($aIsIpv6) {
								return 1;
							}
							else {
								//var_dump($aIsIpv4, $aIsIpv6, $bIsIpv4, $bIsIpv6);
								throw new Exception("Unable to retreive the IP version for: ".implode(', ', $_addresses), E_USER_ERROR);
							}
						});

						if(array_key_exists($name, $addressesTmp))
						{
							if(count(array_diff($addressesTmp[$name], $_addresses)) > 0) {
								$name = $id;
							}
						}
						else {
							$addressesTmp[$name] = $_addresses;
						}

						$type = null;

						foreach($_addresses as &$address)
						{
							if(preg_match('#(^([^/\-]+)$)|(/32|/128)#i', $address)) {
								$addType = Api\Host::API_TYPE;
								$address = preg_replace('#(/(32|128))#i', '', $address);
							}
							elseif(strpos($address, '/') !== false) {
								$addType = Api\Subnet::API_TYPE;
							}
							elseif(strpos($address, '-') !== false) {
								$addType = Api\Network::API_TYPE;
							}
							else {
								throw new Exception("Unable to find address type for '".$address."'", E_USER_ERROR);
							}

							if($type === null) {
								$type = $addType;
							}
							elseif($type !== $addType) {
								throw new Exception("Address types mismatches for: ".implode(', ', $_addresses), E_USER_ERROR);
							}
						}

						$addresses[$id] = array('id' => $id, 'type' => $type, 'name' => $name, 'addresses' => $_addresses);

						// Améliorer en sauvegardant que l'ID
						/*if(!array_key_exists($name, $addressesTmp)) {
							$addressesTmp[$name] = $id;
						}*/
					}
				}
			}

			return $addresses;
		}

		protected function _convertToAclRules($siteName, array $addressBooks, array $policies)
		{
			$rules = array();

			foreach($policies as $policy)
			{
				foreach(array('source' => 'sources', 'destination' => 'destinations') as $attribute => $attributes)
				{
					${$attributes} = array();
					$zoneName = $policy['__'.$attribute.'ZoneName__'];

					foreach($policy[$attributes] as $address)
					{
						$id = $siteName.'___'.$zoneName.'___'.$address;

						if(array_key_exists($id, $this->_addresses)) {
							${$attributes}[] = $this->_addresses[$id];
						}
						elseif(isset($addressBooks['groups'][$siteName][$zoneName][$address]))
						{
							foreach($addressBooks['groups'][$siteName][$zoneName][$address] as $address)
							{
								$id = $siteName.'___'.$zoneName.'___'.$address;

								if(array_key_exists($id, $this->_addresses)) {
									${$attributes}[] = $this->_addresses[$id];
								}
								else {
									throw new Exception("Unable to find address '".$id."'", E_USER_ERROR);
								}
							}
						}
						else {
							throw new Exception("Unable to find address '".$id."'", E_USER_ERROR);
						}
					}

					$policy[$attributes] = ${$attributes};
				}

				$protocols = array();

				foreach($policy['protocols'] as $protocol)
				{
					if(array_key_exists($protocol, $this->_protocols)) {
						$applications = $this->_protocols[$protocol];
					}
					elseif(array_key_exists($protocol, self::APPLICATIONS)) {
						$applications = (array) self::APPLICATIONS[$protocol];
					}
					else {
						throw new Exception("Unable to find application '".$protocol."'", E_USER_ERROR);
					}

					$protocols = array_merge($protocols, $applications);
				}

				$policy['type'] = Api\AclRule::API_TYPE;
				$policy['protocols'] = $protocols;
				$rules[] = $policy;
			}

			return $rules;
		}

		protected function _convertToNatRules($siteName, array $addressBooks, array $translations)
		{
			$rules = array();
			return $rules;
		}

		protected function _applicationNameCleaner($name)
		{
			$namePrefix = preg_quote(Template\Juniper\JunosSet::APP_NAME_PREFIX, '#');
			return preg_replace('#^('.$namePrefix.')#i', '', $name);
		}

		protected function _addressNameCleaner($name)
		{
			$namePrefix = preg_quote(Template\Juniper\JunosSet::ADD_NAME_PREFIX, '#');
			$IPvSeparator = preg_quote(Template\Juniper\JunosSet::ADDRESS_IPv_SEPARATOR, '#');
			return preg_replace('#^('.$namePrefix.')(.+?)('.$IPvSeparator.'[46])$#i', '$2', $name);
		}

		public function saveToLocal($prefix = null, $suffix = null)
		{
			$addressCounter = array(
				Api\Host::API_TYPE => 0,
				Api\Subnet::API_TYPE => 0,
				Api\Network::API_TYPE => 0,
			);

			$ruleCounter = array(
				Api\AclRule::API_TYPE => 0,
				Api\NatRule::API_TYPE => 0,
			);

			foreach($this->_aclRules as $rule)
			{
				foreach(array('sources', 'destinations') as $attributes)
				{
					foreach($rule[$attributes] as &$ruleAttr)
					{
						/**
						  * /!\ Important pour l'algorythme
						  * - addresses ne doit comporter au maximum que 2 IPs
						  * - addresses doit posséder en 1er l'IPv4 et en 2ème l'IPv6
						  */
						if(count($ruleAttr['addresses']) > 0)
						{
							$addresses = array();

							/**
							  * On récupère les objets Address API correspondants à chaque adresse
							  * En local plusieurs objets peuvent correspondrent à la même adresse
							  */
							foreach($ruleAttr['addresses'] as $address)
							{
								$addressesApi = $this->_addressManager->getObjects($ruleAttr['type'], $address, false, true, 1);

								if(count($addressesApi) > 0) {
									$addresses[] = $addressesApi;
								}
							}

							/**
							  * On ne garde que les objets communs à toutes les adresses
							  */
							switch(count($addresses))
							{
								case 1: {
									$addresses = $addresses[0];
									break;
								}
								case 2: {
									$addresses = array_intersect($addresses[0], $addresses[1]);
									break;
								}
							}

							if(count($addresses) === 0)
							{
								/**
								  * Création depuis IPAM donc possibilité que l'objet Addresse API soit dualstack
								  */
								$addressApi = $this->_addressManager->autoCreateObject($ruleAttr['type'], $ruleAttr['addresses'][0], false, false);

								if(!$addressApi instanceof Api\Address)
								{
									$addressApi = $this->_addressManager->insert($ruleAttr['type'], $ruleAttr['name']);

									if($addressApi instanceof Api\Address)
									{
										$status = $addressApi->configure($ruleAttr['addresses'][0]);

										if(!$status) {
											throw new E\Message("Unable to configure ".$addressApi::API_LABEL." '".$ruleAttr['name']."' with address '".$ruleAttr['addresses'][0]."'", E_USER_WARNING);
										}
									}
									else {
										$addressApiLabel = $this->_addressManager->getName($ruleAttr['type']);
										throw new E\Message("Unable to create ".$addressApiLabel." '".$ruleAttr['name']."'", E_USER_WARNING);
									}
								}

								if(count($ruleAttr['addresses']) > 1)
								{
									if(!$addressApi->isIPv6())
									{
										$status = $addressApi->configure($ruleAttr['addresses'][1]);

										if(!$status) {
											throw new E\Message("Unable to configure ".$addressApi::API_LABEL." '".$ruleAttr['name']."' with address '".$ruleAttr['addresses'][1]."'", E_USER_WARNING);
										}
									}
									elseif(!$addressApi->equal($ruleAttr['addresses'][1])) {
										throw new E\Message("Unable to configure ".$addressApi::API_LABEL." '".$ruleAttr['name']."' with address '".$ruleAttr['addresses'][1]."', another address '".$addressApi->attributeV6."' already exists", E_USER_WARNING);
									}
								}

								if(!$addressApi->isValid()) {
									$addressApiLabel = $this->_addressManager->getName($ruleAttr['type'], true);
									throw new E\Message($addressApiLabel." '".$ruleAttr['name']."' is not valid", E_USER_WARNING);
								}
							}
							else {
								$addressApi = current($addresses);
							}

							$addressCounter[$addressApi::API_TYPE]++;
							$ruleAttr = $addressApi;
						}
						else {
							continue(3);
						}
					}
					unset($ruleAttr);
				}

				/**
				  * Case: [any-ipv4, any-ipv6] --> [any, any]
				  * 2 different objects on remote can be unique object on local
				  */
				$rule['sources'] = array_unique($rule['sources']);
				$rule['destinations'] = array_unique($rule['destinations']);

				foreach($rule['protocols'] as &$protocol)
				{
					$protocolApi = new Api\Protocol($protocol, $protocol);
					$status = $protocolApi->protocol($protocol);

					if($status && $protocolApi->isValid()) {
						$protocol = $protocolApi;
					}
					else {
						throw new E\Message("Unable to create ".$protocolApi::API_LABEL." '".$protocol."'", E_USER_WARNING);
					}
				}
				unset($protocol);

				// @todo code keepName, see import CSV

				$ruleName = $this->_aclRuleManager->getNextName($rule['type']);
				$ruleName = $prefix.$ruleName.$suffix;

				$aclRuleApi = $this->_aclRuleManager->insert($rule['type'], $ruleName, null);

				if($aclRuleApi instanceof Api\AclRule)
				{
					$aclRuleApi->category(Api\AclRule::CATEGORY_MONOSITE);

					$aclRuleApi->state($rule['status']);
					$aclRuleApi->action($rule['action']);
					$aclRuleApi->description($rule['description']);

					foreach($rule['tags'] as $tag)
					{
						$tagApi = new Api\Tag($tag, $tag);
						$status = $tagApi->tag($tag);

						if($status && $tagApi->isValid()) {
							$aclRuleApi->tag($tagApi);
						}
					}

					$status = 0;
					$status += $aclRuleApi->setSources($rule['sources']);
					$status += $aclRuleApi->setDestinations($rule['destinations']);
					$status += $aclRuleApi->setProtocols($rule['protocols']);
				
					if(!$aclRuleApi->isValid()) {
						$invalidFields = implode(', ', $aclRuleApi->isValid(true));
						throw new E\Message($aclRuleApi::API_LABEL." '".$rule['name']."' is not valid (".$invalidFields.")", E_USER_WARNING);
					}
					elseif($status !== 3) {
						throw new E\Message("Unable to configure ".$aclRuleApi::API_LABEL." '".$rule['name']."'", E_USER_WARNING);
					}
					else {
						$ruleCounter[$aclRuleApi::API_TYPE]++;
						$this->_SHELL->print($aclRuleApi::API_LABEL." ".$rule['name']." converted! (rule ".$ruleName.")", 'green');
					}
				}
				else {
					$ruleApiLabel = $this->_aclRuleManager->getName($rule['type']);
					throw new E\Message("Unable to create ".$ruleApiLabel." '".$ruleName."'", E_USER_WARNING);
				}
			}

			return array(
				Api\Host::class => $addressCounter[Api\Host::API_TYPE],
				Api\Subnet::class => $addressCounter[Api\Subnet::API_TYPE],
				Api\Network::class => $addressCounter[Api\Network::API_TYPE],
				Api\AclRule::class => $ruleCounter[Api\AclRule::API_TYPE],
				Api\NatRule::class => $ruleCounter[Api\NatRule::API_TYPE],
			);
		}
	}