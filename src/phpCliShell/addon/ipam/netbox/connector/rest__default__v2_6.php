<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Connector;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Addon\Ipam\Netbox\Api;
	use PhpCliShell\Addon\Ipam\Netbox\Tools;
	use PhpCliShell\Addon\Ipam\Netbox\Service;
	use PhpCliShell\Addon\Ipam\Netbox\Exception;

	class Rest__default__v2_6 extends AbstractRestConnector
	{
		/**
		  * @var string
		  */
		const LABEL = 'default_v2.6';

		/**
		  * @var array
		  */
		const REST_URN = array(
			'sections' => 'dcim/regions',
			'folders' => 'dcim/sites',
			'subnets' => 'ipam/prefixes',
			'addresses' => 'ipam/ip-addresses',
			'vlans' => 'ipam/vlans',
		);


		// =========== READER ============
		/**
		  * @param string $objectType
		  * @param int $objectId
		  * @return false|string
		  */
		public function resolvToLabel($objectType, $objectId)
		{
			switch($objectType)
			{
				case Api\Section::OBJECT_TYPE:
					$restAPI = $this->_restAPI->sections;
					$fieldName = Api\Section::FIELD_NAME;
					break;
				case Api\Folder::OBJECT_TYPE:
					$restAPI = $this->_restAPI->folders;
					$fieldName = Api\Folder::FIELD_NAME;
					break;
				case Api\Subnet::OBJECT_TYPE:
					$restAPI = $this->_restAPI->subnets;
					$fieldName = Api\Subnet::FIELD_NAME;
					break;
				case Api\Vlan::OBJECT_TYPE:
					$restAPI = $this->_restAPI->vlans;
					$fieldName = Api\Vlan::FIELD_NAME;
					break;
				case Api\Address::OBJECT_TYPE:
					$restAPI = $this->_restAPI->addresses;
					$fieldName = Api\Address::FIELD_NAME;
					break;
				default:
					throw new Exception("Object type '".$objectType."' is unknown", E_USER_ERROR);
			}

			$response = $this->_get($restAPI->{$objectId});

			return ($response !== false) ? ($response[$fieldName]) : (false);
		}

		/**
		  * @param int $subnetId Subnet ID
		  * @return false|string Gateway IP address
		  */
		public function getGatewayBySubnetId($subnetId)
		{
			if(C\Tools::is('int&&>0', $subnetId))
			{
				$subnet = $this->getSubnet($subnetId);

				if($subnet !== false)
				{
					if($subnet['vrf'] === null) {
						$subnet['vrf_id'] = 'null';
					}

					$args = array(
						'vrf_id' => $subnet['vrf_id'],
						'parent' => $subnet['prefix'],
						'tag' => 'gateway'
					);

					$addresses = $this->_get($this->_restAPI->addresses);
					$addresses = $this->_filterAddressesWithMask($addresses, $subnet['prefix']);

					if(isset($addresses[0]['address'])) {
						return $this->_formatIP($addresses[0]['address']);
					}
				}
			}

			return false;
		}

		/**
		  * @param array $vlanIds VLAN number IDs
		  * @return array VLAN names
		  */
		public function getVlanNamesByVlanIds(array $vlanIds)
		{
			$vlanNames = array();
			$vlanIdParts = array_chunk($vlanIds, 10);

			foreach($vlanIdParts as $vlanIdPart)
			{
				$args = array(
					'id__in' => implode(',', $vlanIdPart)
				);

				$vlans = $this->_get($this->_restAPI->vlans, $args);

				if($vlans !== false)
				{
					/*foreach($vlans as $vlan) {
						$vlanNames[$vlan[Api\Vlan::FIELD_NAME]] = $vlan[Api\Vlan::FIELD_VLAN];
					}*/

					$vlans = array_column($vlans, Api\Vlan::FIELD_VLAN, Api\Vlan::FIELD_NAME);
					$vlanNames = array_merge($vlanNames, $vlans);
				}
			}

			return $vlanNames;
		}

		// ----------- SECTION -----------
		/**
		  * @return false|array Sections
		  */
		public function getAllSections()
		{
			$sections = $this->_get($this->_restAPI->sections);
			return $this->_formatSections($sections);
		}

		/**
		  * @return false|array Root sections
		  */
		public function getRootSections()
		{
			return $this->getSections(static::SECTION_ROOT_ID);
		}

		/**
		  * @param int Parent section ID
		  * @return false|array Sections
		  */
		public function getSections($sectionId)
		{
			if(C\Tools::is('int&&>=0', $sectionId))
			{
				if($sectionId === static::SECTION_ROOT_ID) {
					$sectionId = 'null';
				}

				$args = array(
					'parent_id' => $sectionId
				);

				$sections = $this->_get($this->_restAPI->sections, $args);
				return $this->_formatSections($sections);
			}
			else {
				return false;
			}
		}

		/**
		  * @param int $sectionId Section ID
		  * @return false|array Sections
		  */
		public function getSection($sectionId)
		{
			if(C\Tools::is('int&&>0', $sectionId)) {
				$section = $this->_get($this->_restAPI->sections->{$sectionId});
				return $this->_formatSection($section);
			}
			else {
				return false;
			}
		}
		// -------------------------------

		// ----------- FOLDER ------------
		/**
		  * @return array Folders
		  */
		public function getAllFolders()
		{
			$folders = $this->_get($this->_restAPI->folders);
			$folders = $this->_formatFolders($folders);

			return ($folders !== false) ? ($folders) : (array());
		}

		/**
		  * @return false|array Folders
		  */
		public function getMasterFolders()
		{
			$args = array(
				'region' => 'null'
			);

			$folders = $this->_get($this->_restAPI->folders, $args);
			return $this->_formatFolders($folders);
		}

		/**
		  * @param int $sectionId Parent section ID
		  * @return false|array Folders
		  */
		public function getRootFolders($sectionId)
		{
			$masterFolders = $this->getMasterFolders();
			$masterFolders = $this->_formatFolders($masterFolders);

			if($masterFolders !== false && C\Tools::is('int&&>0', $sectionId))
			{
				$rootFolders = $this->getFolders(self::FOLDER_ROOT_ID, $sectionId);

				if($rootFolders !== false) {
					return array_merge($masterFolders, $rootFolders);
				}
				else {
					return false;
				}
			}
			else {
				return $masterFolders;
			}
		}

		/**
		  * @param null|int $folderId Parent folder ID
		  * @param null|int $sectionId Parent section ID
		  * @return false|array Folders
		  */
		public function getFolders($folderId, $sectionId = null)
		{
			$args = array();

			if(C\Tools::is('int&&>0', $folderId)) {
				return false;
			}

			if(C\Tools::is('int&&>0', $sectionId)) {
				$args['region_id'] = $sectionId;
			}

			$folders = $this->_get($this->_restAPI->folders, $args);
			$folders = $this->_filterFolders($folders, null, $sectionId);
			return $this->_formatFolders($folders);
		}

		/**
		  * @param int $folderId Folder ID
		  * @return false|array Folder
		  */
		public function getFolder($folderId)
		{
			if(C\Tools::is('int&&>0', $folderId)) {
				$folder = $this->_get($this->_restAPI->folders->{$folderId});
				return $this->_formatFolder($folder);
			}
			else {
				return false;
			}
		}
		// -------------------------------

		// ----------- SUBNET ------------
		/**
		  * @return array Subnets
		  */
		public function getAllSubnets()
		{
			$subnets = $this->_get($this->_restAPI->subnets);
			$subnets = $this->_formatSubnets($subnets);

			return ($subnets !== false) ? ($subnets) : (array());
		}

		/**
		  * @param null|int $folderId Parent folder ID
		  * @return false|array Subnets
		  */
		public function getRootSubnets($folderId)
		{
			$args = array(
				'site' => 'null'
			);

			$subnets = $this->_get($this->_restAPI->subnets, $args);

			if($subnets !== false && C\Tools::is('int&&>0', $folderId))
			{
				$args = array(
					'site_id' => $folderId
				);

				$results = $this->_get($this->_restAPI->subnets, $args);

				if($results !== false) {
					$subnets = array_merge($subnets, $results);
				}
			}

			return $this->_formatSubnets($subnets);
		}

		/**
		  * @param null|int $subnetId Parent subnet ID
		  * @param null|int $folderId Parent folder ID
		  * @return false|array Subnets
		  */
		public function getSubnets($subnetId, $folderId = null)
		{
			$args = array();

			/**
			  * Ne retourne pas exclusivement les subnets enfants du niveau -1 !
			  */
			if(C\Tools::is('int&&>0', $subnetId))
			{
				$subnet = $this->_getSubnet($subnetId);

				if($subnet !== false)
				{
					if($subnet['vrf'] === null) {
						$subnet['vrf_id'] = 'null';
					}

					$args['vrf_id'] = $subnet['vrf_id'];
					$args['within'] = $subnet['prefix'];
				}
				else {
					return array();
				}
			}

			if(C\Tools::is('int&&>0', $folderId)) {
				$args['site_id'] = $folderId;
			}

			$subnets = $this->_get($this->_restAPI->subnets, $args);
			$subnets = $this->_filterSubnetsWithParent($subnets, $subnetId);
			$subnets = $this->_filterSubnetsWithParents($subnets, $subnetId, $folderId);

			return $this->_formatSubnets($subnets, $subnetId);
		}

		/**
		  * @param int $subnetId Subnet ID
		  * @return false|array Subnet
		  */
		public function getSubnet($subnetId)
		{
			$subnet = $this->_getSubnet($subnetId);
			return $this->_formatSubnet($subnet);
		}

		/**
		  * @param int $subnetId Subnet ID
		  * @return false|array Subnet
		  */
		protected function _getSubnet($subnetId)
		{
			if(C\Tools::is('int&&>0', $subnetId)) {
				return $this->_get($this->_restAPI->subnets->{$subnetId});
			}
			else {
				return false;
			}
		}

		/**
		  * @param int|string $subnet Subnet ID or prefix
		  * @param null|int $vrfId Subnet VRF ID
		  * @param null|int $folderId Folder ID
		  * @return false|null|int Subnet ID
		  */
		protected function _getParentSubnetId($subnet, $vrfId = null, $folderId = null)
		{
			if(C\Tools::is('int&&>0', $subnet))
			{
				// Ne pas appeler getSubnet sinon boucle infinie
				$subnet = $this->_getSubnet($subnet);

				if($subnet !== false)
				{
					if($subnet['vrf'] === null) {
						$subnet['vrf_id'] = null;
					}

					$vrfId = $subnet['vrf_id'];
					$subnet = $subnet['prefix'];
					$folderId = $subnet[Api\Subnet::FIELD_FOLDER_ID];
				}
				else {
					return false;
				}
			}

			if(Tools::isSubnet($subnet))
			{
				$args = array(
					'contains' => $subnet
				);

				if(C\Tools::is('int&&>0', $vrfId)) {
					$args['vrf_id'] = $vrfId;
				}
				else {
					$args['vrf_id'] = 'null';
				}

				if(C\Tools::is('int&&>0', $folderId)) {
					$args[Api\Subnet::FIELD_FOLDER_ID] = $folderId;
				}

				$subnets = $this->_get($this->_restAPI->subnets, $args);

				if($subnets !== false)
				{
					/**
					  * Il y a toujours au moins 1 résultat, le subnet lui-même
					  */
					if(count($subnets) > 1)
					{
						$subnets = array_column($subnets, 'prefix', Api\Subnet::FIELD_ID);

						$subnets = array_filter($subnets, function($prefix) use ($subnet) {
							return ($prefix !== $subnet);
						});

						natcasesort($subnets);
						$subnets = array_keys($subnets);
						return end($subnets);

						/*$subnets = array_filter($subnets, function($_subnet) use ($subnet) {
							return ($_subnet['prefix'] !== $subnet);
						});

						usort($subnets, function($a, $b) {
							return strnatcasecmp($a['prefix'], $b['prefix']);
						});

						$subnet = end($subnets);
						return $subnet[Api\Subnet::FIELD_ID];*/
					}
					else {
						return null;
					}
				}
			}

			return false;
		}

		/**
		  * @param int $vlanId
		  * @param false|int $folderId
		  * @return false|array Subnets
		  */
		public function getSubnetsFromVlan($vlanId, $folderId = false)
		{
			if(C\Tools::is('int&&>0', $vlanId))
			{
				$args = array(
					'vlan_id' => $vlanId
				);

				if(C\Tools::is('int&&>0', $folderId)) {
					$args['site_id'] = $folderId;
				}

				$subnets = $this->_get($this->_restAPI->subnets, $args);
				return $this->_formatSubnets($subnets);
			}
			else {
				return false;
			}
		}

		/**
		  * @param int $subnetId Subnet ID
		  * @return false|array Subnet usage
		  */
		public function getSubnetUsage($subnetId)
		{
			return false;
		}
		// -------------------------------

		// ------------ VLAN -------------
		/**
		  * @return false|array VLANs
		  */
		public function getAllVlans()
		{
			return $this->_get($this->_restAPI->vlans);
		}

		/**
		  * @param int $vlanId VLAN ID
		  * @return false|array VLAN
		  */
		public function getVlan($vlanId)
		{
			if(C\Tools::is('int&&>0', $vlanId)) {
				return $this->_get($this->_restAPI->vlans->{$vlanId});
			}
			else {
				return false;
			}
		}
		// -------------------------------

		// ----------- ADDRESS -----------
		/**
		  * @param int $subnetId Parent subnet ID
		  * @return false|array Addresses
		  */
		public function getAddresses($subnetId)
		{
			if(C\Tools::is('int&&>0', $subnetId))
			{
				$subnet = $this->_getSubnet($subnetId);

				if($subnet !== false)
				{
					if($subnet['vrf'] === null) {
						$subnet['vrf_id'] = 'null';
					}

					$args = array(
						'vrf_id' => $subnet['vrf_id'],
						'parent' => $subnet['prefix']
					);

					$prefix = $subnet['prefix'];

					$addresses = $this->_get($this->_restAPI->addresses, $args);
					$addresses = $this->_filterAddressesWithMask($addresses, $prefix);

					return $this->_formatAddresses($addresses, $subnetId);
				}
			}

			return false;
		}

		/**
		  * @param int $id Subnet or address ID
		  * @param null|string $ip Address IP
		  * @return false|array Address
		  */
		public function getAddress($id, $ip = null)
		{
			if(C\Tools::is('int&&>0', $id))
			{
				$address = $this->_getAddress($id, $ip);

				if($address !== false) {
					$subnetId = ($ip !== null) ? ($id) : (null);
					return $this->_formatAddress($address, $subnetId);
				}
			}

			return false;
		}

		/**
		  * @param int $id Subnet or address ID
		  * @param null|string $ip Address IP
		  * @return false|array Address
		  */
		protected function _getAddress($id, $ip = null)
		{
			if(C\Tools::is('int&&>0', $id))
			{
				if($ip === null) {
					return $this->_get($this->_restAPI->addresses->{$id});
				}
				elseif(Tools::isIP($ip))
				{
					$subnet = $this->_getSubnet($id);

					if($subnet !== false)
					{
						$prefixParts = explode('/', $subnet['prefix']);
						$mask = $prefixParts[1];

						if($subnet['vrf'] === null) {
							$subnet['vrf_id'] = 'null';
						}

						$args = array(
							'vrf_id' => $subnet['vrf_id'],
							'parent' => $subnet['prefix'],
							'address' => $ip.'/'.$mask
						);

						$addresses = $this->_get($this->_restAPI->addresses);
						$addresses = $this->_filterAddressesWithMask($addresses, $subnet['prefix']);

						if($addresses != false)
						{
							if(count($addresses) === 1) {
								return current($addresses);
							}
							else {
								throw new Exception("Multiple addresses returned", E_USER_ERROR);
							}
						}
					}
				}
			}

			return false;
		}

		/**
		  * @param int|string $address Address ID or IP (with mask)
		  * @param null|int $vrfId Address VRF ID
		  * @return false|null|int Address ID
		  */
		protected function _getAddressSubnetId($address, $vrfId = null)
		{
			if(C\Tools::is('int&&>0', $address))
			{
				// Ne pas appeler getSubnet sinon boucle infinie
				$address = $this->_getAddress($address);

				if($address !== false)
				{
					if($address['vrf'] === null) {
						$address['vrf_id'] = null;
					}

					$vrfId = $address['vrf_id'];
					$address = $address['address'];
				}
				else {
					return false;
				}
			}

			$subnet = Tools::networkSubnet($address);

			if(Tools::isSubnet($subnet))
			{
				$args = array();

				/**
				  * Utiliser contains car Netbox n'oblige pas d'avoir le subnet parent
				  * dont le masque correspond exactement au masque de l'adresse IP
				  */
				$args['contains'] = $subnet;

				if(C\Tools::is('int&&>0', $vrfId)) {
					$args['vrf_id'] = $vrfId;
				}
				else {
					$args['vrf_id'] = 'null';
				}

				$subnets = $this->_get($this->_restAPI->subnets, $args);

				if($subnets !== false)
				{
					if(count($subnets) > 0)
					{
						$subnets = array_column($subnets, 'prefix', Api\Subnet::FIELD_ID);

						natcasesort($subnets);
						$subnets = array_keys($subnets);
						return end($subnets);
					}
					else {
						return null;
					}
				}
			}

			return false;
		}
		// -------------------------------

		// ----------- SEARCH ------------
		/**
		  * @param string $sectionName Section name (* or % is allowed)
		  * @param null|int $sectionId Parent section ID
		  * @param bool $strict
		  * @return false|array Sections
		  */
		public function searchSectionName($sectionName, $sectionId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $sectionName))
			{
				$args = array();

				if($strict) {
					$args[] = Api\Section::FIELD_NAME.'='.urlencode($sectionName);
				}
				else
				{
					$sectionName = trim($sectionName, '*%');
					$sectionNames = explode(Api\Sections::WILDCARD, $sectionName);

					/**
					  * q ne peut pas être la clé de args
					  * puisqu'il y a possiblement plusieurs filtres q
					  */
					foreach($sectionNames as $sectionName) {
						$args[] = 'q='.urlencode($sectionName);
					}
				}

				// Search in section or not
				if($sectionId === static::SECTION_ROOT_ID) {
					$args[] = 'parent_id=null';
				}
				elseif(C\Tools::is('int&&>0', $sectionId)) {
					$args[] = 'parent_id='.$sectionId;
				}

				$args = implode('&', $args);
				$sections = $this->_get($this->_restAPI->sections, $args);
				return $this->_formatSections($sections);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $folderName
		  * @param null|int $folderId
		  * @param null|int $sectionId
		  * @param bool $strict
		  * @return false|array Folders
		  */
		public function searchFolderName($folderName, $folderId = null, $sectionId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $folderName))
			{
				$args = array();

				if($strict) {
					$args[] = Api\Folder::FIELD_NAME.'='.urlencode($folderName);
				}
				else
				{
					$folderName = trim($folderName, '*%');
					$folderNames = explode(Api\Folders::WILDCARD, $folderName);

					/**
					  * q ne peut pas être la clé de args
					  * puisqu'il y a possiblement plusieurs filtres q
					  */
					foreach($folderNames as $folderName) {
						$args[] = 'q='.urlencode($folderName);
					}
				}

				// Search in folder or not
				if(C\Tools::is('int&&>0', $folderId)) {
					return array();
				}
				/*
				if($folderId === static::FOLDER_ROOT_ID) {
					$args[] = 'parent_id=null';
				}
				elseif(C\Tools::is('int&&>0', $folderId)) {
					$args[] = 'parent_id='.$folderId;
				}
				*/

				// Gets master folders
				if($sectionId === self::SECTION_ROOT_ID) {
					//$args[] = Api\Folder::FIELD_SECTION_ID.'=null';			// @todo issue https://github.com/netbox-community/netbox/issues/3357
					return array();
				}
				// Search in section or not
				elseif(C\Tools::is('int&&>0', $sectionId))
				{
					/**
					  * Puisqu'un site peut ne pas avoir une région comme parent alors il faut
					  * rechercher à la fois les sites orphelins et ceux appartenant à la région
					  *
					  * Attention, à ne réaliser que lorsque folderId n'est pas définie sinon
					  * les sites orphelins seront toujours des enfants des autres sites
					  *
					  * On peut cumuler plusieurs attributs de même nom lors d'une rechercher
					  * Dans la majorité des cas cela se comporte en OU || sauf pour tag par exemple
					  */
					if(!C\Tools::is('int&&>0', $folderId)) {
						//$args[] = Api\Folder::FIELD_SECTION_ID.'=null';	// @todo issue https://github.com/netbox-community/netbox/issues/3357
					}

					$args[] = Api\Folder::FIELD_SECTION_ID.'='.$sectionId;
				}

				$args = implode('&', $args);

				$folders = $this->_get($this->_restAPI->folders, $args);
				$folders = $this->_filterFolders($folders, $folderName, $sectionId, $strict);
				return $this->_formatFolders($folders);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $subnetCidr Subnet CIDR
		  * @return false|array Subnets
		  */
		public function searchSubnets($subnetCidr)
		{
			if(Tools::isSubnet($subnetCidr))
			{
				$args = array(
					'prefix' => $subnetCidr
				);

				$subnets = $this->_get($this->_restAPI->subnets, $args);
				return $this->_formatSubnets($subnets);
			}
			else {
				return false;
			}
		}

		/**
		  * $strict for future use
		  *
		  * @param string $subnetCidr
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param bool $strict
		  * @return false|array Subnets
		  */
		public function searchSubnetCidr($subnetCidr, $subnetId = null, $folderId = null, $strict = false)
		{
			if(Tools::isSubnet($subnetCidr))
			{
				$args = array(
					'prefix' => $subnetCidr
				);

				// Search in subnet or not
				if($subnetId === static::SUBNET_ROOT_ID)
				{
					/**
					  * /!\ Does not work !
					  *
					  * Load subnets matches other filters
					  * see _filterSubnetsWithParents about root subnet filter
					  */
					//$args['within'] = 'null';
				}
				elseif(C\Tools::is('int&&>0', $subnetId))
				{
					$subnet = $this->_getSubnet($subnetId);

					if($subnet !== false)
					{
						if($subnet['vrf'] === null) {
							$subnet['vrf_id'] = 'null';
						}

						$args['vrf_id'] = $subnet['vrf_id'];
						$args['within'] = $subnet['prefix'];
					}
					else {
						return false;
					}
				}

				// Gets master subnets
				if($folderId === static::FOLDER_ROOT_ID) {
					$args[Api\Subnet::FIELD_FOLDER_ID] = 'null';
				}
				// Search in folder or not
				elseif(C\Tools::is('int&&>0', $folderId)) {
					$args[Api\Subnet::FIELD_FOLDER_ID] = $folderId;
				}

				$subnets = $this->_get($this->_restAPI->subnets, $args);
				$subnets = $this->_filterSubnetsWithParent($subnets, $subnetId);
				$subnets = $this->_filterSubnetsWithParents($subnets, $subnetId, $folderId);

				return $this->_formatSubnets($subnets, $subnetId);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $subnetName
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param bool $strict
		  * @return false|array Subnets
		  */
		public function searchSubnetName($subnetName, $IPv = null, $subnetId = null, $folderId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $subnetName))
			{
				$args = array();

				if($strict) {
					$args[] = Api\Subnet::FIELD_NAME.'='.urlencode($subnetName);
				}
				else
				{
					$subnetName = trim($subnetName, '*%');
					$subnetNames = explode(Api\Subnets::WILDCARD, $subnetName);

					/**
					  * q ne peut pas être la clé de args
					  * puisqu'il y a possiblement plusieurs filtres q
					  */
					foreach($subnetNames as $subnetName) {
						$args[] = 'q='.urlencode($subnetName);
					}
				}

				if($IPv === 4 || $IPv === 6) {
					$args[] = 'family='.$IPv;
				}

				if($subnetId === static::SUBNET_ROOT_ID)
				{
					/**
					  * /!\ Does not work !
					  *
					  * Load subnets matches other filters
					  * see _filterSubnetsWithParents about root subnet filter
					  */
					//$args[] = 'within=null';
				}
				elseif(C\Tools::is('int&&>0', $subnetId))
				{
					$subnet = $this->getSubnet($subnetId);

					if($subnet !== false)
					{
						if($subnet['vrf'] === null) {
							$subnet['vrf_id'] = 'null';
						}

						$args[] = 'vrf_id='.$subnet['vrf_id'];
						$args[] = 'within='.urlencode($subnet['prefix']);
					}
					else {
						return false;
					}
				}

				// Gets master subnets
				if($folderId === static::FOLDER_ROOT_ID) {
					$args[] = Api\Subnet::FIELD_FOLDER_ID.'=null';
				}
				// Search in folder or not
				elseif(C\Tools::is('int&&>0', $folderId)) {
					$args[] = Api\Subnet::FIELD_FOLDER_ID.'='.$folderId;
				}

				$args = implode('&', $args);

				$subnets = $this->_get($this->_restAPI->subnets, $args);
				$subnets = $this->_filterSubnets($subnets, $subnetName, $strict);
				$subnets = $this->_filterSubnetsWithParent($subnets, $subnetId);
				$subnets = $this->_filterSubnetsWithParents($subnets, $subnetId, $folderId);

				return $this->_formatSubnets($subnets, $subnetId);
			}
			else {
				return false;
			}
		}

		/**
		  * @param int $vlanNumber VLAN number
		  * @return false|array VLANs
		  */
		public function searchVlans($vlanNumber)
		{
			if(C\Tools::is('int&&>0', $vlanNumber))
			{
				$args = array(
					Api\Vlan::FIELD_VLAN => $vlanNumber
				);

				$vlans = $this->_get($this->_restAPI->vlans, $args);
				return $this->_formatVlans($vlans);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $vlanName VLAN name
		  * @param bool $strict
		  * @return false|array VLANs
		  */
		public function searchVlanName($vlanName, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $vlanName))
			{
				$args = array();

				if($strict) {
					$args[] = Api\Vlan::FIELD_NAME.'='.urlencode($vlanName);
				}
				else
				{
					$vlanName = trim($vlanName, '*%');
					$vlanNames = explode(Api\Vlans::WILDCARD, $vlanName);

					/**
					  * q ne peut pas être la clé de args
					  * puisqu'il y a possiblement plusieurs filtres q
					  */
					foreach($vlanNames as $vlanName) {
						$args[] = 'q='.urlencode($vlanName);
					}
				}

				$args = implode('&', $args);
				$vlans = $this->_get($this->_restAPI->vlans, $args);
				return $this->_formatVlans($vlans);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $ip IP address
		  * @return false|array IP addresses
		  */
		public function searchAddresses($ip)
		{
			if(Tools::isIP($ip)) {
				return $this->searchAddressIP($ip);
			}
			else {
				return false;
			}
		}

		/**
		  * $strict for future use
		  *
		  * @param string $addressIP Address IP or wildcard *
		  * @param null|int $subnetId Parent subnet ID
		  * @param bool $strict
		  * @return array IP addresses
		  */
		public function searchAddressIP($addressIP, $subnetId = null, $strict = false)
		{
			// Allow * or IP address
			if(C\Tools::is('string&&!empty', $addressIP))
			{
				$prefix = null;
				$args = array();

				if(Tools::isIP($addressIP)) {
					$args['q'] = $addressIP;
				}

				if(C\Tools::is('int&&>0', $subnetId))
				{
					$subnet = $this->_getSubnet($subnetId);

					if($subnet !== false)
					{
						if($subnet['vrf'] === null) {
							$subnet['vrf_id'] = 'null';
						}

						$args['vrf_id'] = $subnet['vrf_id'];
						$args['parent'] = $subnet['prefix'];
						$prefix = $subnet['prefix'];
					}
					else {
						return false;
					}
				}

				$addresses = $this->_get($this->_restAPI->addresses, $args);
				$addresses = $this->_filterAddressesWithMask($addresses, $prefix);

				return $this->_formatAddresses($addresses, $subnetId);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $addName Address hostname
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId Parent subnet ID
		  * @param bool $strict
		  * @return array IP addresses
		  */
		public function searchAddHostname($addName, $IPv = null, $subnetId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $addName))
			{
				$prefix = null;
				$args = array();

				if($strict) {
					$args[] = Api\Address::FIELD_DESC.'='.urlencode($addName);
				}
				else
				{
					$addName = trim($addName, '*%');
					$hostnames = explode(Api\Addresses::WILDCARD, $addName);

					/**
					  * q ne peut pas être la clé de args
					  * puisqu'il y a possiblement plusieurs filtres q
					  */
					foreach($hostnames as $hostname) {
						$args[] = 'q='.urlencode($hostname);
					}
				}

				if($IPv === 4 || $IPv === 6) {
					$args[] = 'family='.$IPv;
				}

				if(C\Tools::is('int&&>0', $subnetId))
				{
					$subnet = $this->getSubnet($subnetId);

					if($subnet !== false)
					{
						if($subnet['vrf'] === null) {
							$subnet['vrf_id'] = 'null';
						}

						$prefix = $subnet['prefix'];
						$args[] = 'vrf_id='.$subnet['vrf_id'];
						$args[] = 'parent='.urlencode($prefix);
					}
					else {
						return false;
					}
				}

				$args = implode('&', $args);

				$addresses = $this->_get($this->_restAPI->addresses, $args);
				$addresses = $this->_filterAddresses($addresses, $addName, null, $strict);
				$addresses = $this->_filterAddressesWithMask($addresses, $prefix);

				return $this->_formatAddresses($addresses, $subnetId);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $addDesc Address description
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId Parent subnet ID
		  * @param bool $strict
		  * @return array IP addresses
		  */
		public function searchAddDescription($addDesc, $IPv = null, $subnetId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $addDesc))
			{
				$prefix = null;
				$args = array();

				if($strict) {
					$args[] = Api\Address::FIELD_DESC.'='.urlencode($addDesc);
				}
				else
				{
					$addDesc = trim($addDesc, '*%');
					$descriptions = explode(Api\Addresses::WILDCARD, $addDesc);

					/**
					  * q ne peut pas être la clé de args
					  * puisqu'il y a possiblement plusieurs filtres q
					  */
					foreach($descriptions as $description) {
						$args[] = 'q='.urlencode($description);
					}
				}

				if($IPv === 4 || $IPv === 6) {
					$args[] = 'family='.$IPv;
				}

				if(C\Tools::is('int&&>0', $subnetId))
				{
					$subnet = $this->_getSubnet($subnetId);

					if($subnet !== false)
					{
						if($subnet['vrf'] === null) {
							$subnet['vrf_id'] = 'null';
						}

						$prefix = $subnet['prefix'];
						$args[] = 'vrf_id='.$subnet['vrf_id'];
						$args[] = 'parent='.urlencode($prefix);
					}
					else {
						return false;
					}
				}

				$args = implode('&', $args);

				$addresses = $this->_get($this->_restAPI->addresses, $args);
				$addresses = $this->_filterAddresses($addresses, null, $addDesc, $strict);
				$addresses = $this->_filterAddressesWithMask($addresses, $prefix);

				return $this->_formatAddresses($addresses, $subnetId);
			}
			else {
				return false;
			}
		}
		// ===============================

		// =========== WRITER ============
		// ----------- Address -----------
		public function createAddress($subnetId, $address, $hostname, $description = '', $note = '', $port = '', $tag = Api\Address::ACTIVE)
		{
			return false;
		}

		public function modifyAddress($addressId, $hostname = null, $description = null, $note = null, $port = null, $tag = null)
		{
			return false;
		}

		public function removeAddress($addressId)
		{
			return false;
		}
		// -------------------------------
		// ===============================
	}