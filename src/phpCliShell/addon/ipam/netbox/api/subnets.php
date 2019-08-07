<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Netbox\Tools;
	use PhpCliShell\Addon\Ipam\Netbox\Adapter;

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
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $name
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param null|int $sectionId
		  * @param bool $strict
		  * @return array Subnets
		  */
		protected static function _searchSubnetNames(Adapter $IPAM = null, $name = '*', $IPv = null, $subnetId = null, $folderId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if($name === null) {
				$name = '*';
			}

			$subnets = array();

			/**
			  * Permet de forcer la sélection d'un subnet par une VRF
			  * Une VRF n'est pas liée à une section (region) ou folder (site)
			  */
			$separator = preg_quote(static::SEPARATOR_SECTION, '#');
			$status = preg_match('#(?:'.$separator.'(?<vrf>.+?)'.$separator.')?(?<name>.+)#i', $name, $nameParts);

			if($status && C\Tools::is('string&&!empty', $nameParts['vrf']) && C\Tools::is('string&&!empty', $nameParts['name'])) {
				$name = $nameParts['name'];
				$vrfName = $nameParts['vrf'];
			}

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

			if(isset($vrfName))
			{
				$subnets = static::_filterObjects($subnets, 'vrf', $vrfName, false);

				foreach($subnets as &$subnet) {
					$subnetNamePrefix = static::SEPARATOR_SECTION.$vrfName.static::SEPARATOR_SECTION;
					$subnet[static::FIELD_NAME] = $subnetNamePrefix.$subnet[static::FIELD_NAME];
				}
				unset($subnet);
			}

			return $subnets;
		}
	}