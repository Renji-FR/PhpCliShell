<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Ipam;

	use ArrayObject;

	use PhpCliShell\Core as C;

	use PhpCliShell\Cli;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Program;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Ipam as IpamOrchestrator;

	abstract class AbstractIpam
	{
		/**
		  * @var string
		  */
		const FIELD_IPAM_SERVICE = IpamOrchestrator::FIELD_IPAM_SERVICE;

		/**
		  * @var string
		  */
		const FIELD_IPAM_ATTRIBUTES = IpamOrchestrator::FIELD_IPAM_ATTRIBUTES;

		/**
		  * @var \PhpCliShell\Cli\Terminal\Main
		  */
		protected $_TERMINAL;

		/**
		  * @var \PhpCliShell\Cli\Shell\Main
		  */
		protected $_SHELL;

		/**
		  * @var \PhpCliShell\Core\Config
		  */
		protected $_CONFIG;

		/**
		  * @var \PhpCliShell\Core\Addon\Orchestrator
		  */
		protected $_addonOrchestrator;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL)
		{
			$this->_SHELL = $SHELL;
			$this->_CONFIG = $SHELL->config;
			$this->_TERMINAL = $SHELL->terminal;
		}

		/**
		  * @return void
		  */
		public function initAddon(array $servers)
		{
			$orchestrator = $this->_initAddonOrchestrator();

			if($orchestrator !== false)
			{
				$this->_addonOrchestrator = $orchestrator;
				$printInfoMessages = !$this->_SHELL->isOneShotCall();

				foreach($servers as $server)
				{
					if(!$orchestrator->hasService($server))
					{
						$Addon_Service = $orchestrator->newService($server);

						if($printInfoMessages) {
							$adapterLabel = $Addon_Service->getAdapterLabel();
							$adapterMethod = $Addon_Service->getAdapterMethod();
							C\Tools::e(PHP_EOL."Connection ".$adapterMethod." à l'IPAM [".$Addon_Service::SERVICE_NAME."] {".$adapterLabel."} @ ".$server." veuillez patienter ... ", 'blue');
						}

						try {
							$isReady = $Addon_Service->initialization();
						}
						catch(\Exception $e)
						{
							if($printInfoMessages) {
								C\Tools::e("[KO]".PHP_EOL, 'red');
							}

							$this->_SHELL->error("Impossible de démarrer le service IPAM:", 'orange');
							$this->_SHELL->error($e->getMessage(), 'white', 'red', 'bold');

							if($this->_SHELL->addonDebug) {
								$this->_SHELL->error("File '".$e->getFile()."' line '".$e->getLine()."'", 'orange');
							}

							exit;
						}

						if(!$isReady) {
							if($printInfoMessages) { C\Tools::e("[KO]", 'red'); }
							$this->_SHELL->error("Le service IPAM n'a pas pu être correctement initialisé", 'red');
							exit;
						}

						if($printInfoMessages) {
							C\Tools::e("[OK]", 'green');
						}
					}
				}
			}
		}

		/*public function getObject($type, $id)
		{
			$results = $this->getObjects($type, $id, true);

			switch(count($results))
			{
				case 0: {
					return false;
				}
				case 1: {
					return current($results);
				}
				default:
				{
					// Best effort: Ne traite que le champs name
					$nameField = constant(self::API_TYPE_CLASS[$type].'::FIELD_NAME');

					foreach($results as $result)
					{
						if($result[$nameField] === $id) {
							return $result;
						}
					}

					return false;
				}
			}
		}*/

		public function getObjects($type, $arg, $strict = true)
		{
			switch($type)
			{
				case Api\Host::API_TYPE: {
					$results = $this->getAddresses($arg, $strict);
					break;
				}
				case Api\Subnet::API_TYPE: {
					$results = $this->getSubnets($arg, $strict);
					break;
				}
				case Api\Network::API_TYPE: {
					$results = array();
					break;
				}
				default: {
					throw new Exception("Unknown type '".$type."'", E_USER_ERROR);
				}
			}

			return $results;
		}

		public function getSubnets($search, $strict = false)
		{
			$results = array();
			$subnets = $this->searchSubnets($search, $strict);

			foreach($subnets as $subnet)
			{
				$subnetName = $subnet[Api\Subnet::FIELD_NAME];

				if(array_key_exists($subnetName, $results)) {
					$subnetObject = $results[$subnetName];
				}
				else {
					$results[$subnetName] = $subnet;
					continue;
				}

				$cidrIsIPv4 = ($subnet[Api\Subnet::FIELD_ATTRv4] !== null);
				$cidrIsIPv6 = ($subnet[Api\Subnet::FIELD_ATTRv6] !== null);

				if($cidrIsIPv4 && $subnetObject[Api\Subnet::FIELD_ATTRv4] === null) {
					$subnetObject[Api\Subnet::FIELD_ATTRv4] = $subnet[Api\Subnet::FIELD_ATTRv4];
					//$subnetObject['attributeV4'] = $subnetObject[Api\Subnet::FIELD_ATTRv4];
				}
				elseif($cidrIsIPv6 && $subnetObject[Api\Subnet::FIELD_ATTRv6] === null) {
					$subnetObject[Api\Subnet::FIELD_ATTRv6] = $subnet[Api\Subnet::FIELD_ATTRv6];
					//$subnetObject['attributeV6'] = $subnetObject[Api\Subnet::FIELD_ATTRv6];
				}
				elseif(!$cidrIsIPv4 && !$cidrIsIPv6) {
					throw new Exception("Unable to know the IP version of this subnet '".$subnetName."'", E_USER_ERROR);
				}
				else {
					$cidrSubnet = ($cidrIsIPv4) ? ($subnet[Api\Subnet::FIELD_ATTRv4]) : ($subnet[Api\Subnet::FIELD_ATTRv6]);
					throw new Exception("Duplicate subnet name found in IPAM '".$subnetName."' '".$cidrSubnet."'", E_USER_ERROR);
				}
			}

			return array_values($results);
		}

		public function getAddresses($search, $strict = false)
		{
			$results = array();
			$addresses = $this->searchAddresses($search, $strict);

			foreach($addresses as $address)
			{
				$addressName = $address[Api\Host::FIELD_NAME];

				if(array_key_exists($addressName, $results)) {
					$addressObject = $results[$addressName];
				}
				else {
					$results[$addressName] = $address;
					continue;
				}

				$addressIsIPv4 = ($address[Api\Host::FIELD_ATTRv4] !== null);
				$addressIsIPv6 = ($address[Api\Host::FIELD_ATTRv6] !== null);

				if($addressIsIPv4 && $addressObject[Api\Host::FIELD_ATTRv4] === null) {
					$addressObject[Api\Host::FIELD_ATTRv4] = $address[Api\Host::FIELD_ATTRv4];
					//$addressObject['attributeV4'] = $addressObject[Api\Host::FIELD_ATTRv4];
				}
				elseif($addressIsIPv6 && $addressObject[Api\Host::FIELD_ATTRv6] === null) {
					$addressObject[Api\Host::FIELD_ATTRv6] = $address[Api\Host::FIELD_ATTRv6];
					//$addressObject['attributeV6'] = $addressObject[Api\Host::FIELD_ATTRv6];
				}
				elseif(!$addressIsIPv4 && !$addressIsIPv6) {
					throw new Exception("Unable to know the IP version of this address '".$addressName."'", E_USER_ERROR);
				}
				else {
					$ipAddress = ($addressIsIPv4) ? ($address[Api\Host::FIELD_ATTRv4]) : ($address[Api\Host::FIELD_ATTRv6]);
					throw new Exception("Duplicate address name found in IPAM '".$addressName."' (".$ipAddress.")", E_USER_ERROR);
				}
			}

			return array_values($results);
		}

		public function searchObjects($type, $arg, $strict = true)
		{
			switch($type)
			{
				case Api\Host::API_TYPE: {
					$results = $this->searchAddresses($arg, $strict);
					break;
				}
				case Api\Subnet::API_TYPE: {
					$results = $this->searchSubnets($arg, $strict);
					break;
				}
				case Api\Network::API_TYPE: {
					$results = array();
					break;
				}
				default: {
					throw new Exception("Unknown type '".$type."'", E_USER_ERROR);
				}
			}

			return $results;
		}

		/**
		  * @return false|\PhpCliShell\Core\Addon\Orchestrator
		  */
		abstract protected function _initAddonOrchestrator();

		/**
		  * @return false|\PhpCliShell\Core\Addon\Orchestrator
		  */
		public function getAddonOrchestrator()
		{
			return ($this->_addonOrchestrator !== null) ? ($this->_addonOrchestrator) : (false);
		}

		/**
		  * @return bool
		  */
		public function hasServiceAvailable()
		{
			$orchestrator = $this->getAddonOrchestrator();
			return ($orchestrator !== false && count($orchestrator) > 0);
		}

		/**
		  * @return bool
		  */
		abstract public static function isAddonPresent();
	}