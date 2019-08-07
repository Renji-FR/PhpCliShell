<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Phpipam\Tools;
	use PhpCliShell\Addon\Ipam\Phpipam\Adapter;

	class Subnets extends AbstractGetters
	{
		use Common\Api\TraitSubnets;

		/**
		  * @var string
		  */
		const OBJECT_TYPE = Subnet::OBJECT_TYPE;

		/**
		  * @var string
		  */
		const FIELD_ID = Subnet::FIELD_ID;

		/**
		  * @var string
		  */
		const FIELD_NAME = Subnet::FIELD_NAME;

		/**
		  * @var string
		  */
		const FIELD_PARENT_ID = Subnet::FIELD_PARENT_ID;

		/**
		  * @var string
		  */
		const RESOLVER_OBJECT_API_NAME = 'Subnet';

		/**
		  * @var string
		  */
		const SEPARATOR_SECTION = '#';


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
				return static::_searchSubnets($this->_adapter, $subnet, $IPv, $this->getSubnetId(), null, null, $strict);
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
		  * @param null|int $sectionId Section ID
		  * @param bool $strict
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @return false|array
		  */
		public static function searchSubnets($subnet, $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false, Adapter $IPAM = null)
		{
			return static::_searchSubnets($IPAM, $subnet, $IPv, $subnetId, $folderId, $sectionId, $strict);
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId Subnet ID
		  * @param null|int $folderId Folder ID
		  * @param null|int $sectionId Section ID
		  * @param bool $strict
		  * @return false|array
		  */
		protected static function _searchSubnets(Adapter $IPAM = null, $subnet = '*', $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if(Tools::isSubnet($subnet)) {
				return static::_searchCidrSubnets($IPAM, $subnet, $subnetId, $folderId, $sectionId, $strict);
			}
			else {
				return static::_searchSubnetNames($IPAM, $subnet, $IPv, $subnetId, $folderId, $sectionId, $strict);
			}
		}

		public function findCidrSubnets($subnet, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			return static::_searchCidrSubnets($this->_adapter, $subnet, $subnetId, $folderId, $sectionId, $strict);
		}

		public static function searchCidrSubnets($subnet, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			return static::_searchCidrSubnets(null, $subnet, $subnetId, $folderId, $sectionId, $strict);
		}

		/**
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $subnet
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param null|int $sectionId
		  * @param bool $strict
		  * @return false|array Subnets
		  *
		  * $strict for future use
		  */
		protected static function _searchCidrSubnets(Adapter $IPAM = null, $subnet = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
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
						// Pas de elseif
						if(C\Tools::is('int&&>=0', $sectionId)) {
							$subnets = static::_filterObjects($subnets, static::FIELD_SECTION_ID, (string) $sectionId);
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
						return $IPAM->searchSubnetCidr($subnet, $subnetId, $folderId, $sectionId, $strict);
					}
				}
			}
			else {
				// @todo return all subnets?
				//return static::_searchSubnetNames($IPAM, '*', null, $subnetId, $folderId, $sectionId, $strict);
			}

			return array();
		}

		/**
		  * @param string $name
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param null|int $sectionId Section ID
		  * @param bool $strict
		  * @return array Subnets
		  */
		public function findSubnetNames($name, $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			return static::_searchSubnetNames($this->_adapter, $name, $IPv, $subnetId, $folderId, $sectionId, $strict);
		}

		/**
		  * @param string $name
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param null|int $sectionId Section ID
		  * @param bool $strict
		  * @return array Subnets
		  */
		public static function searchSubnetNames($name, $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			return static::_searchSubnetNames(null, $name, $IPv, $subnetId, $folderId, $sectionId, $strict);
		}

		/**
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $name
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param null|int $sectionId
		  * @param bool $strict
		  * @return array Subnets
		  */
		protected static function _searchSubnetNames(Adapter $IPAM = null, $name = '*', $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if($name === null) {
				$name = '*';
			}

			$subnets = array();

			if($sectionId === null && $folderId === null && $subnetId === null)
			{
				$separator = preg_quote(static::SEPARATOR_SECTION, '#');
				$status = preg_match('#(?:'.$separator.'(?<section>.+?)'.$separator.')?(?<name>.+)#i', $name, $nameParts);

				if($status && C\Tools::is('string&&!empty', $nameParts['section']) && C\Tools::is('string&&!empty', $nameParts['name']))
				{
					$sections = Sections::searchSections($nameParts['section'], null, true);

					if($sections !== false && count($sections) === 1) {
						$name = $nameParts['name'];
						$sectionId = $sections[0][Section::FIELD_ID];
						$sectionName = $sections[0][Section::FIELD_NAME];
					}
					else {
						return $subnets;
					}
				}
			}

			if(($subnets = static::_getSelfCache(static::OBJECT_TYPE, $IPAM)) !== false)
			{
				if(C\Tools::is('int&&>=0', $subnetId)) {
					$subnets = static::_filterObjects($subnets, static::FIELD_PARENT_ID, (string) $subnetId);
				}
				elseif(C\Tools::is('int&&>=0', $folderId)) {
					$subnets = static::_filterObjects($subnets, static::FIELD_FOLDER_ID, (string) $folderId);
				}
				// Pas de elseif
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$subnets = static::_filterObjects($subnets, static::FIELD_SECTION_ID, (string) $sectionId);
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
				$subnets = $IPAM->searchSubnetName($name, $IPv, $subnetId, $folderId, $sectionId, $strict);
			}

			if(isset($sectionName))
			{
				foreach($subnets as &$subnet) {
					$subnetNamePrefix = static::SEPARATOR_SECTION.$sectionName.static::SEPARATOR_SECTION;
					$subnet[static::FIELD_NAME] = $subnetNamePrefix.$subnet[static::FIELD_NAME];
				}
				unset($subnet);
			}

			return $subnets;
		}
	}