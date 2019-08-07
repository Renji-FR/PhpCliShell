<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Netbox\Adapter;

	class Addresses extends AbstractGetters
	{
		use Common\Api\TraitAddresses;

		/**
		  * @var string
		  */
		const OBJECT_TYPE = Address::OBJECT_TYPE;

		/**
		  * @var string
		  */
		const FIELD_ID = Address::FIELD_ID;

		/**
		  * @var string
		  */
		const FIELD_NAME = Address::FIELD_NAME;

		/**
		  * @var string
		  */
		const FIELD_DESC = Address::FIELD_DESC;

		/**
		  * @var string
		  */
		const FIELD_SUBNET_ID = Address::FIELD_SUBNET_ID;

		/**
		  * @var string
		  */
		const RESOLVER_OBJECT_API_NAME = 'Address';

		/**
		  * @var string
		  */
		const SEPARATOR_SECTION = '#';


		protected static function _searchAddressNames(Adapter $IPAM = null, $name = '*', $IPv = null, $subnetId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if($name === null) {
				$name = '*';
			}

			$vrfNameFilter = null;

			if($subnetId === null)
			{
				$addresses = array();
				$separator = preg_quote(static::SEPARATOR_SECTION, '#');
				$status = preg_match('#(?:'.$separator.'(?<vrf>.+?)'.$separator.')?(?<name>.+)#i', $name, $nameParts);

				if($status && C\Tools::is('string&&!empty', $nameParts['vrf']) && C\Tools::is('string&&!empty', $nameParts['name'])) {
					$name = $nameParts['name'];
					$vrfNameFilter = $nameParts['vrf'];
				}
			}

			$addresses = $IPAM->searchAddHostname($name, $IPv, $subnetId, $strict);
			$addresses = static::_filterAddressOnVrfName($addresses, static::FIELD_NAME, $vrfNameFilter);

			return $addresses;
		}

		protected static function _searchAddressDescs(Adapter $IPAM = null, $desc = '*', $IPv = null, $subnetId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if($desc === null) {
				$desc = '*';
			}

			$vrfNameFilter = null;

			if($subnetId === null)
			{
				$addresses = array();
				$separator = preg_quote(static::SEPARATOR_SECTION, '#');
				$status = preg_match('#(?:'.$separator.'(?<vrf>.+?)'.$separator.')?(?<desc>.+)#i', $desc, $descParts);

				if($status && C\Tools::is('string&&!empty', $descParts['vrf']) && C\Tools::is('string&&!empty', $descParts['desc'])) {
					$desc = $descParts['desc'];
					$vrfNameFilter = $descParts['vrf'];
				}
			}

			$addresses = $IPAM->searchAddDescription($desc, $IPv, $subnetId, $strict);
			$addresses = static::_filterAddressOnVrfName($addresses, static::FIELD_DESC, $vrfNameFilter);

			return $addresses;
		}

		protected static function _filterAddressOnVrfName(array $addresses, $field, $vrfNameFilter = null)
		{
			if(C\Tools::is('string&&!empty', $vrfNameFilter))
			{
				$addresses = static::_filterObjects($addresses, 'vrf', $vrfNameFilter, false);

				foreach($addresses as &$address) {
					$addressNamePrefix = static::SEPARATOR_SECTION.$vrfName.static::SEPARATOR_SECTION;
					$address[static::FIELD_NAME] = $addressNamePrefix.$address[static::FIELD_NAME];
				}
				unset($address);
			}

			return $addresses;
		}
	}