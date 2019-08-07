<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Adapter;

	trait TraitVlans
	{
		/**
		  * @return bool
		  */
		public function hasVlanId()
		{
			return $this->hasObjectId();
		}

		/**
		  * @return false|int
		  */
		public function getVlanId()
		{
			return $this->getObjectId();
		}

		/**
		  * @return bool
		  */
		public function hasVlanApi()
		{
			return $this->hasObjectApi();
		}

		/**
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Vlan
		  */
		public function getVlanApi()
		{
			return $this->getObjectApi();
		}

		/**
		  * Return all subnets matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $vlan VLAN number or name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array VLANs
		  */
		public function getVlans($vlan = '*', $strict = false)
		{
			return $this->findVlans($vlan, $strict);
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param string $vlan VLAN number or name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array VLANs
		  */
		public function findVlans($vlan, $strict = false)
		{
			return static::_searchVlans($this->_adapter, $vlan, $strict);
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param string $vlan VLAN number or name, wildcard * is allowed
		  * @param bool $strict
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @return false|array VLANs
		  */
		public static function searchVlans($vlan, $strict = false, Adapter $IPAM = null)
		{
			return static::_searchVlans($IPAM, $vlan, $strict);
		}

		/**
		  * Return all vlans matches request
		  *
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $vlan VLAN number or name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array VLANs
		  */
		protected static function _searchVlans(Adapter $IPAM = null, $vlan = '*', $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if(C\Tools::is('int&&>0', $vlan)) {
				return static::_searchVlanNumbers($IPAM, $vlan);
			}
			else {
				return static::_searchVlanNames($IPAM, $vlan, $strict);
			}
		}

		public function findVlanNumbers($vlanNumber)
		{
			return static::_searchVlanNumbers($this->_adapter, $vlanNumber);
		}

		public static function searchVlanNumbers($vlanNumber)
		{
			return static::_searchVlanNumbers(null, $vlanNumber);
		}

		/**
		  * Return all vlans matches request
		  *
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param int $vlanNumber VLAN number
		  * @return false|array VLANs
		  */
		protected static function _searchVlanNumbers(Adapter $IPAM = null, $vlanNumber = null)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if(($vlans = static::_getSelfCache(static::OBJECT_TYPE, $IPAM)) !== false) {
				return static::_filterObjects($vlans, 'number', (string) $vlanNumber);
			}
			else {
				return $IPAM->searchVlans($vlanNumber);
			}
		}

		public function findVlanNames($name, $strict = false)
		{
			return static::_searchVlanNames($this->_adapter, $name, $strict);
		}

		public static function searchVlanNames($name, $strict = false)
		{
			return static::_searchVlanNames(null, $name, $strict);
		}

		/**
		  * Return all vlans matches request
		  *
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $name VLAN name
		  * @param bool strict
		  * @return false|array VLANs
		  */
		protected static function _searchVlanNames(Adapter $IPAM = null, $name, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if(($vlans = static::_getSelfCache(static::OBJECT_TYPE, $IPAM)) !== false) {
				return static::_searchObjects($vlans, static::FIELD_NAME, $name, $strict);
			}
			else {
				return $IPAM->searchVlanName($name, $strict);
			}
		}
	}