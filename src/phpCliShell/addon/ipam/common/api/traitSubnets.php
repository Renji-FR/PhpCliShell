<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Tools;
	use PhpCliShell\Addon\Ipam\Common\Adapter;

	trait TraitSubnets
	{
		/**
		  * @return bool
		  */
		public function hasSubnetId()
		{
			return $this->hasObjectId();
		}

		/**
		  * @return false|int
		  */
		public function getSubnetId()
		{
			return $this->getObjectId();
		}

		/**
		  * @return bool
		  */
		public function hasSubnetApi()
		{
			return $this->hasObjectApi();
		}

		/**
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Subnet
		  */
		public function getSubnetApi()
		{
			return $this->getObjectApi();
		}

		/**
		  * Retrieve subnet from parent subnet
		  *
		  * Subnet name must be unique
		  * Return false if more than one subnet found
		  *
		  * @param string $subnetName Subnet name
		  * @return false|int Subnet
		  */
		public function retrieveSubnet($subnetName)
		{
			$subnets = $this->retrieveSubnets($subnetName);
			return ($subnets !== false && count($subnets) === 1) ? ($subnets[0]) : (false);
		}

		/**
		  * Retrieve subnet ID from parent subnet
		  *
		  * Subnet name must be unique
		  * Return false if more than one subnet found
		  *
		  * @param string $subnetName Subnet name
		  * @return false|int Subnet ID
		  */
		public function retrieveSubnetId($subnetName)
		{
			$subnet = $this->retrieveSubnet($subnetName);
			return ($subnet !== false) ? ($subnet[static::FIELD_ID]) : (false);
		}

		/**
		  * Retrieve subnet API from parent subnet
		  *
		  * Subnet name must be unique
		  * Return false if more than one subnet found
		  *
		  * @param string $subnetName Subnet name
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Subnet Subnet API
		  */
		public function retrieveSubnetApi($subnetName)
		{
			$subnetId = $this->retrieveSubnetId($subnetName);
			return ($subnetId !== false) ? ($this->factoryObjectApi($subnetId)) : (false);
		}

		/**
		  * Retrieves all subnets matches request from parent subnet
		  *
		  * All arguments must be optional
		  *
		  * @param string $subnetName Subnet name
		  * @return false|array Subnets
		  */
		public function retrieveSubnets($subnetName = null)
		{
			if($this->hasSubnetId())
			{
				if(($subnets = $this->_getThisCache(static::OBJECT_TYPE)) !== false) {
					$subnets = static::_filterObjects($subnets, static::FIELD_PARENT_ID, (string) $this->getSubnetId());
					return static::_filterObjects($subnets, static::FIELD_NAME, $subnetName);
				}
				else {
					$subnets = $this->_adapter->getSubnets($this->getSubnetId());
					return $this->_filterObjects($subnets, static::FIELD_NAME, $subnetName);
				}
			}
			else {
				return false;
			}
		}

		/**
		  * Return all subnets matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed
		  * @param null|int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array Subnets
		  */
		public function getSubnets($subnet = '*', $IPv = null, $strict = false)
		{
			return $this->findSubnets($subnet, $IPv, $strict);
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed
		  * @param null|int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array Subnets
		  */
		public function findSubnets($subnet, $IPv = null, $strict = false)
		{
			if($this->hasSubnetId()) {
				return static::_searchSubnets($this->_adapter, $subnet, $IPv, $this->getSubnetId(), null, $strict);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId Subnet ID
		  * @param null|int $folderId Folder ID
		  * @param bool $strict
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @return false|array Subnets
		  */
		public static function searchSubnets($subnet, $IPv = null, $subnetId = null, $folderId = null, $strict = false, Adapter $IPAM = null)
		{
			return static::_searchSubnets($IPAM, $subnet, $IPv, $subnetId, $folderId, $strict);
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId Subnet ID
		  * @param null|int $folderId Folder ID
		  * @param bool $strict
		  * @return false|array SUbnets
		  */
		protected static function _searchSubnets(Adapter $IPAM = null, $subnet = '*', $IPv = null, $subnetId = null, $folderId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if(Tools::isSubnet($subnet)) {
				return static::_searchCidrSubnets($IPAM, $subnet, $subnetId, $folderId, $strict);
			}
			else {
				return static::_searchSubnetNames($IPAM, $subnet, $IPv, $subnetId, $folderId, $strict);
			}
		}

		public function findCidrSubnets($subnet, $subnetId = null, $folderId = null, $strict = false)
		{
			return static::_searchCidrSubnets($this->_adapter, $subnet, $subnetId, $folderId, $strict);
		}

		public static function searchCidrSubnets($subnet, $subnetId = null, $folderId = null, $strict = false)
		{
			return static::_searchCidrSubnets(null, $subnet, $subnetId, $folderId, $strict);
		}

		/**
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $subnet
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param bool $strict
		  * @return false|array Subnets
		  */
		protected static function _searchCidrSubnets(Adapter $IPAM = null, $subnet = null, $subnetId = null, $folderId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if($subnet !== null)
			{
				if(Tools::isSubnet($subnet))
				{
					$subnet = Tools::networkSubnet($subnet);

					if($subnet === false) {
						return false;
					}

					if(($subnets = static::_getSelfCache(static::OBJECT_TYPE, $IPAM)) !== false)
					{
						if(C\Tools::is('int&&>=0', $subnetId)) {
							$subnets = static::_filterObjects($subnets, static::FIELD_PARENT_ID, (string) $subnetId);
						}
						elseif(C\Tools::is('int&&>=0', $folderId)) {
							$subnets = static::_filterObjects($subnets, static::FIELD_FOLDER_ID, (string) $folderId);
						}

						foreach($subnets as $index => $_subnet)
						{
							$_subnet = $_subnet['subnet'].'/'.$_subnet['mask'];

							if(!Tools::subnetInSubnet($_subnet, $subnet)) {
								unset($subnets[$index]);
							}
						}

						return $subnets;
					}
					else {
						return $IPAM->searchSubnetCidr($subnet, $subnetId, $folderId, $strict);
					}
				}
			}
			else {
				// @todo return all subnets?
				//return static::_searchSubnetNames($IPAM, '*', null, $subnetId, $folderId, $strict);
			}

			return array();
		}

		/**
		  * @param string $name
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param bool $strict
		  * @return array Subnets
		  */
		public function findSubnetNames($name, $IPv = null, $subnetId = null, $folderId = null, $strict = false)
		{
			return static::_searchSubnetNames($this->_adapter, $name, $IPv, $subnetId, $folderId, $strict);
		}

		/**
		  * @param string $name
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param bool $strict
		  * @return array Subnets
		  */
		public static function searchSubnetNames($name, $IPv = null, $subnetId = null, $folderId = null, $strict = false)
		{
			return static::_searchSubnetNames(null, $name, $IPv, $subnetId, $folderId, $strict);
		}

		/**
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $name
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param bool $strict
		  * @return array Subnets
		  */
		protected static function _searchSubnetNames(Adapter $IPAM = null, $name = '*', $IPv = null, $subnetId = null, $folderId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if($name === null) {
				$name = static::WILDCARD;
			}

			$subnets = array();

			if(($subnets = static::_getSelfCache(static::OBJECT_TYPE, $IPAM)) !== false)
			{
				if(C\Tools::is('int&&>=0', $subnetId)) {
					$subnets = static::_filterObjects($subnets, static::FIELD_PARENT_ID, (string) $subnetId);
				}
				elseif(C\Tools::is('int&&>=0', $folderId)) {
					$subnets = static::_filterObjects($subnets, static::FIELD_FOLDER_ID, (string) $folderId);
				}

				if($IPv === 4 || $IPv === 6)
				{
					foreach($subnets as $index => $subnet)
					{
						$subnetCidr = $subnet['subnet'].'/'.$subnet['mask'];

						if(!Tools::isSubnetV($subnetCidr, $IPv)) {
							unset($subnets[$index]);
						}
					}
				}

				$subnets = static::_searchObjects($subnets, static::FIELD_NAME, $name, $strict);
			}
			else {
				$subnets = $IPAM->searchSubnetName($name, $IPv, $subnetId, $folderId, $strict);
			}

			return $subnets;
		}

		/**
		  * Return false if more than one address found
		  *
		  * @param string IP address or name
		  * @return false|array Address
		  */
		public function getAddress($address)
		{
			if(Tools::isIP($address)) {
				$address = $this->_adapter->getAddress($this->getSubnetId(), $address);
				return ($address !== false) ? ($address) : (false);
			}
			else {
				$addresses = $this->getAddresses($address);
				return ($addresses !== false && count($addresses) === 1) ? ($addresses[0]) : (false);
			}
		}

		/**
		  * Return false if more than one address found
		  *
		  * @param string IP address or name
		  * @return false|int Address ID
		  */
		public function getAddressId($address)
		{
			$address = $this->getAddress($address);
			$addressApi = $this->_resolver->resolve('Address');
			return ($address !== false) ? ($address[$addressApi::FIELD_ID]) : (false);
		}

		/**
		  * Return false if more than one address found
		  *
		  * @param string IP address or name
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Address Address API
		  */
		public function getAddressApi($address)
		{
			$addressId = $this->getAddressId($address);
			$addressApi = $this->_resolver->resolve('Address');
			return ($addressId !== false) ? ($addressApi::factory($addressId, $this->_service)) : (false);
		}

		/**
		  * Return all addresses matches request
		  *
		  * All arguments must be optional
		  *
		  * @param null|string Address name
		  * @return false|array Addresses
		  */
		public function getAddresses($addressName = null)
		{
			if($this->hasSubnetId()) {
				$addresses = $this->_adapter->getAddresses($this->getSubnetId());
				return $this->_filterObjects($addresses, static::FIELD_NAME, $addressName);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all addresses matches request
		  *
		  * @param string $address IP address or name, wildcard * is allowed for name only
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array Addresses
		  */
		public function findAddresses($address, $IPv = null, $strict = false)
		{
			if($this->hasSubnetId())
			{
				$addressesApi = $this->_resolver->resolve('Addresses');

				if(Tools::isIP($address)) {
					return $addressesApi::searchIpAddresses($address, $this->getSubnetId(), $strict);
				}
				else {
					return $addressesApi::searchAddresses($address, $IPv, $this->getSubnetId(), $strict);
				}
			}
			else {
				return false;
			}
		}
	}