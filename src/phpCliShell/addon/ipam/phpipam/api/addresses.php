<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Phpipam\Adapter;

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

			$sectionNameFilter = null;

			if($subnetId === null)
			{
				$addresses = array();
				$separator = preg_quote(static::SEPARATOR_SECTION, '#');
				$status = preg_match('#(?:'.$separator.'(?<section>.+?)'.$separator.')?(?<name>.+)#i', $name, $nameParts);

				if($status && C\Tools::is('string&&!empty', $nameParts['section']) && C\Tools::is('string&&!empty', $nameParts['name'])) {
					$sectionNameFilter = $nameParts['section'];
					$name = $nameParts['name'];
				}
			}

			$addresses = $IPAM->searchAddHostname($name, $IPv, $subnetId, $strict);
			$addresses = static::_filterAddressOnSectionName($addresses, static::FIELD_NAME, $sectionNameFilter);

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

			$sectionNameFilter = null;

			if($subnetId === null)
			{
				$addresses = array();
				$separator = preg_quote(static::SEPARATOR_SECTION, '#');
				$status = preg_match('#(?:'.$separator.'(?<section>.+?)'.$separator.')?(?<desc>.+)#i', $desc, $descParts);

				if($status && C\Tools::is('string&&!empty', $descParts['section']) && C\Tools::is('string&&!empty', $descParts['desc'])) {
					$sectionNameFilter = $descParts['section'];
					$desc = $descParts['desc'];
				}
			}

			$addresses = $IPAM->searchAddDescription($desc, $IPv, $subnetId, $strict);
			$addresses = static::_filterAddressOnSectionName($addresses, static::FIELD_DESC, $sectionNameFilter);

			return $addresses;
		}

		protected static function _filterAddressOnSectionName(array $addresses, $field, $sectionNameFilter = null)
		{
			if(C\Tools::is('string&&!empty', $sectionNameFilter))
			{
				$sections = Sections::searchSections($sectionNameFilter, null, true);

				if($sections !== false && count($sections) === 1)
				{
					$sectionId = (int) $sections[0][Section::FIELD_ID];
					$sectionName = $sections[0][Section::FIELD_NAME];
					$subnetApi = $this->_resolver->resolve('Subnet');

					foreach($addresses as $index => $address)
					{
						$Api_Subnet = $subnetApi::factory($address[static::FIELD_SUBNET_ID]);
						$subnetSectionId = (int) $Api_Subnet->getSectionId();

						if($subnetSectionId !== $sectionId) {
							unset($addresses[$index]);
						}
						else {
							$addresses[$index]['sectionId'] = $subnetSectionId;
						}
					}

					foreach($addresses as &$address) {
						$addressNamePrefix = static::SEPARATOR_SECTION.$sectionName.static::SEPARATOR_SECTION;
						$address[$field] = $addressNamePrefix.$address[$field];
					}
					unset($address);
				}
			}

			return $addresses;
		}
	}