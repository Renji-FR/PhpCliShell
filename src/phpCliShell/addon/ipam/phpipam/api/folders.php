<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Phpipam\Adapter;

	class Folders extends AbstractGetters
	{
		use Common\Api\TraitFolders;

		/**
		  * @var string
		  */
		const OBJECT_TYPE = Folder::OBJECT_TYPE;

		/**
		  * @var string
		  */
		const FIELD_ID = Folder::FIELD_ID;

		/**
		  * @var string
		  */
		const FIELD_NAME = Folder::FIELD_NAME;

		/**
		  * @var string
		  */
		const FIELD_PARENT_ID = Folder::FIELD_PARENT_ID;

		/**
		  * @var string
		  */
		const RESOLVER_OBJECT_API_NAME = 'Folder';


		/**
		  * Gets all subnets matches request from parent folder
		  *
		  * All arguments must be optional
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed for name only
		  * @return false|array Subnets
		  */
		public function getSubnets($subnetName = null)
		{
			if($this->hasFolderId()) {
				$subnetsApi = $this->_resolver->resolve('Subnets');
				return $subnetsApi::searchSubnets($subnetName, null, null, $this->getFolderId(), null, true, $this->_adapter);
			}
			else {
				return false;
			}
		}

		/**
		  * Finds root subnets matches request from parent folder
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed for name only
		  * @param null|int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array Subnets
		  */
		public function findSubnets($subnet, $IPv = null, $strict = false)
		{
			/**
			  * Ne pas forcer parentSubnetId = SUBNET_ROOT_ID puisque pour Phpipam
			  * un folder est un subnet donc si on recherche dans un folders alors
			  * on récupère automatiquement les subnets root de ce folders !
			  */
			if($this->hasFolderId()) {
				$subnetsApi = $this->_resolver->resolve('Subnets');
				return $subnetsApi::searchSubnets($subnet, $IPv, null, $this->getFolderId(), null, $strict);
			}
			else {
				return false;
			}
		}
	}