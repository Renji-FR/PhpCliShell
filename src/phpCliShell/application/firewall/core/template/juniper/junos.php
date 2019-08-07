<?php
	namespace PhpCliShell\Application\Firewall\Core\Template\Juniper;

	use ArrayObject;

	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Addon\Ipam\Phpipam as Ipam;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Core\Template\Appliance;

	class Junos extends Appliance
	{
		const VENDOR = 'juniper';
		const PLATFORM = 'junos';
		const TEMPLATE = null;

		const ALLOW_RULE_MULTIZONES = self::RULE_MULTIZONES_NONE;

		const ADD_NAME_PREFIX = 'AD_';
		const APP_NAME_PREFIX = 'APP_';
		const ACL_NAME_PREFIX = 'PL_AUTO_';

		const ADDRESS_IPv_SEPARATOR = '-';
		const ADDRESS_ESCAPE_CHARACTERS = '---';
		const ADDRESS_FORBIDDEN_CHARACTERS = array('#', '@');


		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi
		  * @param string $zone
		  * @return array|false Address datas
		  */
		protected function _getObjectAdd(Api\Address $addressApi, $zone = null)
		{
			$addressName = $addressApi->name;

			if(isset($this->_addressBook[$zone]) && array_key_exists($addressName, $this->_addressBook[$zone])) {
				return $this->_addressBook[$zone][$addressName];
			}
			else {
				return false;
			}
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi
		  * @param string $zone
		  * @return array|\ArrayObject Object address datas
		  */
		protected function _toObjectAdd(Api\Address $addressApi, $zone = null)
		{
			$objectAdd = $this->_getObjectAdd($addressApi, $zone);

			if($objectAdd !== false) {
				return $objectAdd;
			}

			$addresses = array();

			$apiAddressName = $addressApi->name;

			/**
			  * CLEANER
			  */
			// ------------------------------
			if($addressApi::API_TYPE === Api\Subnet::API_TYPE) {
				$addressName = ltrim($apiAddressName, Ipam\Api\Subnets::SEPARATOR_SECTION);
				$addressName = str_ireplace(Ipam\Api\Subnets::SEPARATOR_SECTION, self::ADDRESS_ESCAPE_CHARACTERS, $addressName);
			}
			else {
				$addressName = $apiAddressName;
			}

			$addressName = str_ireplace(self::ADDRESS_FORBIDDEN_CHARACTERS, self::ADDRESS_ESCAPE_CHARACTERS, $addressName);
			// ------------------------------

			foreach(array('4' => $addressApi::FIELD_ATTRv4, '6' => $addressApi::FIELD_ATTRv6) as $IPv => $attrName)
			{
				if(!$addressApi->isIPv($IPv)) {
					continue;
				}		

				$result = array();

				if($addressApi->isANY($IPv)) {
					$name = 'any-ipv'.$IPv;
					$result['__doNotCreateAdd__'] = true;
				}
				else {
					$name = self::ADD_NAME_PREFIX.$addressName.self::ADDRESS_IPv_SEPARATOR.$IPv;
				}

				$type = $addressApi::API_TYPE;
				$address = $addressApi->{$attrName};

				if($addressApi::API_TYPE === Api\Network::API_TYPE) {
					$address = explode(Api\Network::SEPARATOR, $address);
				}

				$result['name'] = $name;
				$result['type'] = $type;
				$result['address'] = $address;
				$result['IPv'] = $IPv;

				$addresses[] = $result;

				/**
				  * /!\ Important, utiliser $apiAddressName et non $addressName
				  * Voir méthode _getObjectAdd, la vérification est effectuée avec le nom d'origine
				  */
				$this->_addressBook[$zone][$apiAddressName][] = $result;
			}

			return $addresses;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi
		  * @param string $zone
		  * @return array|\ArrayObject Protocol application datas
		  */
		protected function _toProtocolApp(Api\Protocol $protocolApi, $zone = null)
		{
			$protocolApp = $this->_getProtocolApp($protocolApi, $zone);

			if($protocolApp !== false) {
				return $protocolApp;
			}

			$result = array();

			$protocolName = $protocolApi->name;
			$protocol = $protocolApi->protocol;

			$result['name'] = self::APP_NAME_PREFIX;

			$result['name'].= str_replace(
				array(
					$protocolApi::PROTO_SEPARATOR,
					$protocolApi::PROTO_RANGE_SEPARATOR,
					$protocolApi::PROTO_OPTIONS_SEPARATOR
				),
				array('-', '-', '-'),
				$protocol
			);

			$protocolParts = explode($protocolApi::PROTO_SEPARATOR, $protocol);

			$result['protocol'] = $protocolParts[0];

			if(array_key_exists(1, $protocolParts))
			{
				switch($result['protocol'])
				{
					case 'tcp':
					case 'udp': {
						$result['options'] = str_replace($protocolApi::PROTO_RANGE_SEPARATOR, '-', $protocolParts[1]);
						break;
					}
					case 'icmp':
					case 'icmp4':
					case 'icmp6':
					{
						$options = explode($protocolApi::PROTO_OPTIONS_SEPARATOR, $protocolParts[1], 2);

						$result['options']['type'] = $options[0];

						if(array_key_exists(1, $options)) { 
							$result['options']['code'] = $options[1];
						}
						break;
					}
				}
			}

			$this->_applications[$protocolName] = $result;
			return $result;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AclRule $ruleApi
		  * @param array $srcZones
		  * @param array $sources
		  * @param array $dstZones
		  * @param array $destinations
		  * @return array|\ArrayObject Policy accesslist datas
		  */
		protected function _toPolicyAcl(Api\AclRule $ruleApi, array $srcZones, array $sources, array $dstZones, array $destinations)
		{
			$policyAcl = $this->_getPolicyAcl($ruleApi);

			if($policyAcl !== false) {
				return $policyAcl;
			}

			$ruleName = $ruleApi->name;
			$result = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);

			$result['aclName'] = self::ACL_NAME_PREFIX;
			$result['aclName'] .= $ruleApi->timestamp.'-'.$ruleName;

			$result['state'] = $ruleApi->state;
			$result['action'] = $ruleApi->action;

			$result['sources'] = $sources;
			$result['destinations'] = $destinations;

			foreach(array('src', 'dst') as $attr)
			{
				${$attr.'Zones'} = array_unique(${$attr.'Zones'});

				if(count(${$attr.'Zones'}) === 1) {
					${$attr.'Zone'} = current(${$attr.'Zones'});
					$result[$attr.'Zone'] = ${$attr.'Zone'};
				}
				else {
					throw new E\Message("Ce template '".static::VENDOR."-".static::PLATFORM."' n'est pas compatible avec l'ACL multi-zones '".$ruleApi->name."'", E_USER_ERROR);
				}
			}

			$result['srcAdds'] = array();
			$result['dstAdds'] = array();

			foreach(array('src' => &$sources, 'dst' => &$destinations) as $attr => $attributes)
			{
				foreach($attributes as $attribute)
				{
					$addresses = $this->_toObjectAdd($attribute, ${$attr.'Zone'});

					foreach($addresses as $address) {
						$result[$attr.'Adds'][] = $address['name'];
					}
				}
			}

			$result['protoApps'] = array();

			foreach($ruleApi->protocols as $protocol) {
				$protocol = $this->_toProtocolApp($protocol);
				$result['protoApps'][] = $protocol['name'];
			}

			$result['tags'] = array();

			foreach($ruleApi->tags as $tag) {
				$result['tags'][] = $tag->tag;
			}

			$result['description'] = (string) $ruleApi->description;
			$this->_accessLists[$ruleName] = $result;
			return $result;
		}
	}