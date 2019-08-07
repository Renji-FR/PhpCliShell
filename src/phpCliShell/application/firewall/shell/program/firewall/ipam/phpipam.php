<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Ipam;

	use ArrayObject;

	use PhpCliShell\Core as C;

	use PhpCliShell\Cli;

	use PhpCliShell\Addon\Ipam\Phpipam as AddonIpam;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Core\Tools;
	use PhpCliShell\Application\Firewall\Shell\Program;
	use PhpCliShell\Application\Firewall\Shell\Exception;

	class Phpipam extends AbstractIpam
	{
		/**
		  * @return false|\PhpCliShell\Addon\Ipam\Phpipam\Orchestrator
		  */
		protected function _initAddonOrchestrator()
		{
			if($this->isAddonPresent())
			{
				if(!AddonIpam\Orchestrator::hasInstance()) {
					$orchestrator = AddonIpam\Orchestrator::getInstance($this->_CONFIG->IPAM_PHPIPAM);
					$orchestrator->debug($this->_SHELL->addonDebug);
					return $orchestrator;
				}
				else {
					return AddonIpam\Orchestrator::getInstance();
				}
			}
			else {
				return false;
			}
		}

		public function searchSubnets($search, $strict = false)
		{
			$results = array();
			$orchestrator = $this->getAddonOrchestrator();

			if($orchestrator !== false && count($orchestrator) > 0)
			{
				$subnets = array();

				foreach($orchestrator as $Ipam_Service)
				{
					$subnets[$Ipam_Service->id] = array();
					$orchestrator->useServiceId($Ipam_Service->id);

					$cidrSubnets = AddonIpam\Api\Subnets::searchCidrSubnets($search, null, null, null, $strict);

					/**
					  * Toujours rechercher un nom de subnet avec $search même si des résultats CIDR subnet ont été trouvés
					  * Un subnet peut se nommer par son CIDR dans l'IPAM donc il faut toujours effectuer la recherche
					  */
					$subnetNames = AddonIpam\Api\Subnets::searchSubnetNames($search, null, null, null, null, $strict);

					/**
					  * Recherche les entrées correspondants au nom des résultats de la recherche par subnet
					  * Permet d'obtenir l'ensemble des entrées correspondant à un subnet en partant d'un subnet
					  *
					  * Par exemple: Je recherche un subnet V4, je trouve un nom, je recherche ce nom, je trouve le subnet V6
					  */
					if(C\Tools::is('array&&count>0', $cidrSubnets))
					{
						if(!is_array($subnetNames)) {
							$subnetNames = array();
						}

						foreach($cidrSubnets as $index => $cidrSubnet)
						{
							if(C\Tools::is('string&&!empty', $cidrSubnet[AddonIpam\Api\Subnet::FIELD_NAME]))
							{
								$_subnetNames = AddonIpam\Api\Subnets::searchSubnetNames($cidrSubnet[AddonIpam\Api\Subnet::FIELD_NAME], null, null, null, null, $strict);

								if(C\Tools::is('array&&count>0', $_subnetNames))
								{
									$subnetNames = array_merge($subnetNames, $_subnetNames);

									/**
									  * /!\ Puisque l'on recherche dans un second temps par nom
									  * alors on aura des doublons si on ne réinitialise par les résultats de subnets
									  */
									unset($cidrSubnets[$index]);
								}
							}
						}
					}

					foreach(array($cidrSubnets, $subnetNames) as $_subnets)
					{
						if(C\Tools::is('array&&count>0', $_subnets)) {
							$subnets[$Ipam_Service->id] = array_merge($subnets[$Ipam_Service->id], $_subnets);
						}
					}
				}

				foreach($subnets as $serviceId => $_subnets)
				{
					$Ipam_Service = $orchestrator->getService($serviceId);

					foreach($_subnets as $subnet)
					{
						$subnetName = $subnet[AddonIpam\Api\Subnet::FIELD_NAME];
						$subnetName = preg_replace('#(^\s+)|(\s+$)#i', '', $subnetName);

						$subnetDatas = array(
							Api\Subnet::FIELD_NAME => $subnetName,
							Api\Subnet::FIELD_ATTRv4 => null,
							Api\Subnet::FIELD_ATTRv6 => null,
							self::FIELD_IPAM_SERVICE => $Ipam_Service,
							self::FIELD_IPAM_ATTRIBUTES => $subnet
						);

						$subnetObject = new ArrayObject($subnetDatas, ArrayObject::ARRAY_AS_PROPS);

						$cidrSubnet = $subnet['subnet'].'/'.$subnet['mask'];

						if(Tools::isSubnetV4($cidrSubnet)) {
							$subnetObject[Api\Subnet::FIELD_ATTRv4] = $cidrSubnet;
						}
						elseif(Tools::isSubnetV6($cidrSubnet)) {
							$subnetObject[Api\Subnet::FIELD_ATTRv6] = $cidrSubnet;
						}
						else {
							throw new Exception("Unable to know the IP version of this subnet '".$cidrSubnet."'", E_USER_ERROR);
						}

						$results[] = $subnetObject;
					}
				}
			}

			return $results;
		}

		public function searchAddresses($search, $strict = false)
		{
			$results = array();
			$orchestrator = $this->getAddonOrchestrator();

			if($orchestrator !== false && count($orchestrator) > 0)
			{
				$addresses = array();

				foreach($orchestrator as $Ipam_Service)
				{
					$addresses[$Ipam_Service->id] = array();
					$orchestrator->useServiceId($Ipam_Service->id);

					$addressIps = AddonIpam\Api\Addresses::searchIpAddresses($search, null, $strict);

					/**
					  * Toujours rechercher un nom d'hôte avec $search même si des résultats IP ont été trouvés
					  * Un hôte peut se nommer par son IP dans l'IPAM donc il faut toujours effectuer la recherche
					  */
					$addressNames = AddonIpam\Api\Addresses::searchAddressNames($search, null, null, $strict);
					$addressDescs = AddonIpam\Api\Addresses::searchAddressDescs($search, null, null, $strict);

					/**
					  * Recherche les entrées correspondants au nom des résultats de la recherche par IP
					  * Permet d'obtenir l'ensemble des entrées correspondant à une IP en partant d'une IP
					  *
					  * Par exemple: Je recherche une IPv4, je trouve un nom, je recherche ce nom, je trouve l'IPv6
					  */
					if(C\Tools::is('array&&count>0', $addressIps))
					{
						if(!is_array($addressNames)) {
							$addressNames = array();
						}

						foreach($addressIps as $index => $addressIp)
						{
							if(C\Tools::is('string&&!empty', $addressIp[AddonIpam\Api\Address::FIELD_NAME]))
							{
								$_addressNames = AddonIpam\Api\Addresses::searchAddressNames($addressIp[AddonIpam\Api\Address::FIELD_NAME], null, null, $strict);

								if(C\Tools::is('array&&count>0', $_addressNames))
								{
									$addressNames = array_merge($addressNames, $_addressNames);

									/**
									  * /!\ Puisque l'on recherche dans un second temps par nom
									  * alors on aura des doublons si on ne réinitialise par les résultats d'IPs
									  */
									unset($addressIps[$index]);
								}
							}
						}
					}

					foreach(array($addressIps, $addressNames, $addressDescs) as $_addresses)
					{
						if(C\Tools::is('array&&count>0', $_addresses)) {
							$addresses[$Ipam_Service->id] = array_merge($addresses[$Ipam_Service->id], $_addresses);
						}
					}
				}

				foreach($addresses as $serviceId => $_addresses)
				{
					$Ipam_Service = $orchestrator->getService($serviceId);

					foreach($_addresses as $address)
					{
						$addressName = $address[AddonIpam\Api\Address::FIELD_NAME];
						$addressName = preg_replace('#(^\s+)|(\s+$)#i', '', $addressName);

						if(!C\Tools::is('string&&!empty', $addressName)) {
							$addressName = $address[AddonIpam\Api\Address::FIELD_DESC];
						}

						$addressDatas = array(
							Api\Host::FIELD_NAME => $addressName,
							Api\Host::FIELD_ATTRv4 => null,
							Api\Host::FIELD_ATTRv6 => null,
							self::FIELD_IPAM_SERVICE => $Ipam_Service,
							self::FIELD_IPAM_ATTRIBUTES => $address
						);

						$addressObject = new ArrayObject($addressDatas, ArrayObject::ARRAY_AS_PROPS);

						$ip = $address[AddonIpam\Api\Address::FIELD_ADDRESS];

						if(Tools::isIPv4($ip)) {
							$addressObject[Api\Host::FIELD_ATTRv4] = $ip;
						}
						elseif(Tools::isIPv6($ip)) {
							$addressObject[Api\Host::FIELD_ATTRv6] = $ip;
						}
						else {
							throw new Exception("Unable to know the IP version of this address '".$ip."'", E_USER_ERROR);
						}

						$results[] = $addressObject;
					}
				}
			}

			return $results;
		}

		/**
		  * Pour la recherche IPAM, ne pas regrouper les objets de même nom afin d'avoir IPv4 et IPv6 joints
		  * Cela permet d'avoir une visibilité "large" pour l'utilisateur et de se rendre compte de doublons ou autres incohérences
		  *
		  * @param string $type
		  * @param string $search
		  * @return bool
		  */
		public function printSearch($type, $search)
		{
			$orchestrator = $this->getAddonOrchestrator();

			if($orchestrator !== false && count($orchestrator) > 0)
			{
				if($type !== null) {
					$types = array($type);
				}
				else {
					$types = array(Api\Subnet::API_TYPE, Api\Host::API_TYPE);
				}

				$objects = array();
				$time1 = microtime(true);

				foreach($types as $type)
				{
					try {
						$objects[$type] = $this->searchObjects($type, $search, false);
					}
					catch(Exception $e) {
						$this->_SHELL->throw($e);
						return false;
					}
				}

				$time2 = microtime(true);

				if(count($objects) > 0)
				{
					$this->_SHELL->results->append($objects);
					$this->_SHELL->EOL()->print('RECHERCHE phpIPAM ('.round($time2-$time1).'s)', 'black', 'white', 'bold');

					if(!$this->_SHELL->isOneShotCall())
					{
						if(array_key_exists(Api\Host::API_TYPE, $objects))
						{
							$counter = count($objects[Api\Host::API_TYPE]);
							$this->_SHELL->EOL()->print('HOSTS ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								$this->_SHELL->displayWaitingMsg(true, false, 'Searching attributes from IPAM');

								foreach($objects[Api\Host::API_TYPE] as &$host)
								{
									$hostService = $host[self::FIELD_IPAM_SERVICE];
									$hostAttributes = $host[self::FIELD_IPAM_ATTRIBUTES];
									$Ipam_Api_Address = new AddonIpam\Api\Address($hostAttributes[AddonIpam\Api\Address::FIELD_ID], $hostService);

									if(($Ipam_Api_Subnet = $Ipam_Api_Address->subnetApi) !== false) {
										$subnetPath = $Ipam_Api_Subnet->getPath(true);
									}
									else {
										$subnetPath = '';
									}

									$host = array(
										$subnetPath,
										$Ipam_Api_Address->getLabel(),
										$Ipam_Api_Address->getDescription(),
										$Ipam_Api_Address->getAddress()
									);
								}
								unset($host);

								$this->_SHELL->deleteWaitingMsg(true);
								$table = C\Tools::formatShellTable($objects[Api\Host::API_TYPE]);
								$this->_SHELL->print($table, 'grey');
							}
							else {
								$this->_SHELL->error('Aucun résultat', 'orange');
							}
						}

						if(array_key_exists(Api\Subnet::API_TYPE, $objects))
						{
							$counter = count($objects[Api\Subnet::API_TYPE]);
							$this->_SHELL->EOL()->print('SUBNETS ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								$this->_SHELL->displayWaitingMsg(true, false, 'Searching attributes from IPAM');

								foreach($objects[Api\Subnet::API_TYPE] as &$subnet)
								{
									$subnetService = $subnet[self::FIELD_IPAM_SERVICE];
									$subnetAttributes = $subnet[self::FIELD_IPAM_ATTRIBUTES];
									$Ipam_Api_Subnet = new AddonIpam\Api\Subnet($subnetAttributes[AddonIpam\Api\Subnet::FIELD_ID], $subnetService);

									$subnet = array(
										$Ipam_Api_Subnet->getPath(),
										$Ipam_Api_Subnet->getLabel(),
										$Ipam_Api_Subnet->getCidrSubnet()
									);
								}
								unset($subnet);

								$this->_SHELL->deleteWaitingMsg(true);
								$table = C\Tools::formatShellTable($objects[Api\Subnet::API_TYPE]);
								$this->_SHELL->print($table, 'grey');
							}
							else {
								$this->_SHELL->error('Aucun résultat', 'orange');
							}
						}

						$this->_SHELL->EOL();
					}
				}
				else {
					$this->_SHELL->error("Aucun résultat trouvé", 'orange');
				}
			}
			else {
				$this->_SHELL->error("L'addon IPAM 'phpIPAM' n'est pas installé ou n'est pas configuré", 'orange');
			}

			return true;
		}

		/**
		  * @return bool
		  */
		public static function isAddonPresent()
		{
			// Fonctionne même si l'Addon IPAM n'est pas présent
			return class_exists(AddonIpam\Orchestrator::class);
		}
	}
