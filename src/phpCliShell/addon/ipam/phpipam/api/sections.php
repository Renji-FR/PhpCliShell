<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Phpipam\Adapter;

	class Sections extends AbstractGetters
	{
		use Common\Api\TraitSections;

		/**
		  * @var string
		  */
		const OBJECT_TYPE = Section::OBJECT_TYPE;

		/**
		  * @var string
		  */
		const FIELD_ID = Section::FIELD_ID;

		/**
		  * @var string
		  */
		const FIELD_NAME = Section::FIELD_NAME;

		/**
		  * @var string
		  */
		const FIELD_PARENT_ID = Section::FIELD_PARENT_ID;

		/**
		  * @var string
		  */
		const RESOLVER_OBJECT_API_NAME = 'Section';


		/**
		  * Subnet name is not unique (IPv4 and IPv6)
		  * Return false if more than one subnet found
		  *
		  * @var string $subnetName
		  * @return false|array Subnet
		  */
		public function getSubnet($subnetName)
		{
			$subnets = $this->getSubnets($subnetName);
			return ($subnets !== false && count($subnets) === 1) ? ($subnets[0]) : (false);
		}

		/**
		  * Subnet name is not unique (IPv4 and IPv6)
		  * Return false if more than one subnet found
		  *
		  * @var string $subnetName
		  * @return false|array Subnet ID
		  */
		public function getSubnetId($subnetName)
		{
			$subnet = $this->getSubnet($subnetName);
			$subnetApi = $this->_resolver->resolve('Subnet');
			return ($subnet !== false) ? ($subnet[$subnetApi::FIELD_ID]) : (false);
		}

		/**
		  * Subnet name is not unique (IPv4 and IPv6)
		  * Return false if more than one subnet found
		  *
		  * @var string $subnetName
		  * @return false|array Subnet API
		  */
		public function getSubnetApi($subnetName)
		{
			$subnetId = $this->getSubnetId($subnetName);
			$subnetApi = $this->_resolver->resolve('Subnet');
			return ($subnetId !== false) ? ($subnetApi::factory($subnetId, $this->_service)) : (false);
		}

		/**
		  * Return all subnets matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $subnetName Subnet label, wildcard * is allowed
		  * @return false|array Subnets
		  */
		public function getSubnets($subnetName = null)
		{
			if($this->hasSectionId()) {
				$subnetsApi = $this->_resolver->resolve('Subnets');
				return $subnetsApi::searchSubnets($subnetName, null, null, null, $this->getSectionId(), true, $this->_adapter);
			}
			else {
				return false;
			}
		}

		/**
		  * Return root subnets matches request
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed for name only
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array Subnets
		  */
		public function findSubnets($subnet, $IPv = null, $strict = false)
		{
			if($this->hasSectionId()) {
				$subnetsApi = $this->_resolver->resolve('Subnets');
				return $subnetsApi::searchSubnets($subnet, $IPv, $this->_adapter::SUBNET_ROOT_ID, null, $this->getSectionId(), $strict, $this->_adapter);
			}
			else {
				return false;
			}
		}
	}