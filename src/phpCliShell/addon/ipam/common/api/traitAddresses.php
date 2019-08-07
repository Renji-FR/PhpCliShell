<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Adapter;

	trait TraitAddresses
	{
		/**
		  * @return bool
		  */
		public function hasAddressId()
		{
			return $this->hasObjectId();
		}

		/**
		  * @return false|int
		  */
		public function getAddressId()
		{
			return $this->getObjectId();
		}

		/**
		  * @return bool
		  */
		public function hasAddressApi()
		{
			return $this->hasObjectApi();
		}

		/**
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Address
		  */
		public function getAddressApi()
		{
			return $this->getObjectApi();
		}

		/**
		  * Return all addresses matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $address Address IP or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array
		  */
		public function getAddresses($address = '*', $IPv = null, $strict = false)
		{
			return $this->findAddresses($address, $IPv, $strict);
		}

		/**
		  * Return all addresses matches request
		  *
		  * @param string $address Address IP or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array
		  */
		public function findAddresses($address, $IPv = null, $strict = false)
		{
			if($this->hasAddressId()) {
				$addressApi = $this->factoryObjectApi();
				return static::_searchAddresses($this->_adapter, $address, $IPv, $addressApi->getSubnetId(), $strict);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all addresses matches request
		  *
		  * @param string $address Address IP or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param int $subnetId Subnet ID
		  * @param bool $strict
		  * @return false|array
		  */
		public static function searchAddresses($address, $IPv = null, $subnetId = null, $strict = false)
		{
			return static::_searchAddresses(null, $address, $IPv, $subnetId, $strict);
		}

		/**
		  * Return all addresses matches request
		  *
		  * @param \PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $address Address IP or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param int $subnetId Subnet ID
		  * @param bool $strict
		  * @return false|array
		  */
		protected static function _searchAddresses(Adapter $IPAM = null, $address = '*', $IPv = null, $subnetId = null, $strict = false)
		{
			if(Tools::isIP($address)) {
				return static::_searchIpAddresses($IPAM, $address, $subnetId, $strict);
			}
			else
			{
				$addresses = static::_searchAddressNames($IPAM, $address, $IPv, $subnetId, $strict);

				if(!C\Tools::is('array&&count>0', $addresses)) {
					$addresses = static::_searchAddressDescs($IPAM, $address, $IPv, $subnetId, $strict);
				}

				return $addresses;
			}
		}

		public function findIpAddresses($ip, $subnetId = null, $strict = false)
		{
			return static::_searchIpAddresses($this->_adapter, $ip, $subnetId, $strict);
		}

		public static function searchIpAddresses($ip, $subnetId = null, $strict = false)
		{
			return static::_searchIpAddresses(null, $ip, $subnetId, $strict);
		}

		// $strict for future use
		protected static function _searchIpAddresses(Adapter $IPAM = null, $ip = '*', $subnetId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			return $IPAM->searchAddressIP($ip, $subnetId, $strict);
		}

		public function findAddressNames($name, $IPv = null, $subnetId = null, $strict = false)
		{
			return static::_searchAddressNames($this->_adapter, $name, $IPv, $subnetId, $strict);
		}

		public static function searchAddressNames($name, $IPv = null, $subnetId = null, $strict = false)
		{
			return static::_searchAddressNames(null, $name, $IPv, $subnetId, $strict);
		}

		protected static function _searchAddressNames(Adapter $IPAM = null, $name = '*', $IPv = null, $subnetId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if($name === null) {
				$name = static::WILDCARD;
			}

			return $IPAM->searchAddHostname($name, $IPv, $subnetId, $strict);
		}

		public function findAddressDescs($desc, $IPv = null, $subnetId = null, $strict = false)
		{
			return static::_searchAddressDescs($this->_adapter, $desc, $IPv, $subnetId, $strict);
		}

		public static function searchAddressDescs($desc, $IPv = null, $subnetId = null, $strict = false)
		{
			return static::_searchAddressDescs(null, $desc, $IPv, $subnetId, $strict);
		}

		protected static function _searchAddressDescs(Adapter $IPAM = null, $desc = '*', $IPv = null, $subnetId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if($desc === null) {
				$desc = static::WILDCARD;
			}

			return $IPAM->searchAddDescription($desc, $IPv, $subnetId, $strict);
		}
	}