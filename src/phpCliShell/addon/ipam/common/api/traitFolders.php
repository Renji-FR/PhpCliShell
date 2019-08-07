<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Adapter;

	trait TraitFolders
	{
		/**
		  * @return bool
		  */
		public function hasFolderId()
		{
			return $this->hasObjectId();
		}

		/**
		  * @return false|int
		  */
		public function getFolderId()
		{
			return $this->getObjectId();
		}

		/**
		  * @return bool
		  */
		public function hasFolderApi()
		{
			return $this->hasObjectApi();
		}

		/**
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Folder
		  */
		public function getFolderApi()
		{
			return $this->getObjectApi();
		}

		/**
		  * Retrieve folder from parent folder
		  *
		  * Folder name must be unique
		  * Return false if more than one folder found
		  *
		  * @param string $folderName Folder name
		  * @return false|int Folder
		  */
		public function retrieveFolder($folderName)
		{
			$folders = $this->retrieveFolders($folderName);
			return ($folders !== false && count($folders) === 1) ? ($folders[0]) : (false);
		}

		/**
		  * Retrieve folder ID from parent folder
		  *
		  * Folder name must be unique
		  * Return false if more than one folder found
		  *
		  * @param string $folderName Folder name
		  * @return false|int Folder ID
		  */
		public function retrieveFolderId($folderName)
		{
			$folder = $this->retrieveFolder($folderName);
			return ($folder !== false) ? ($folder[static::FIELD_ID]) : (false);
		}

		/**
		  * Retrieve folder API from parent folder
		  *
		  * Folder name must be unique
		  * Return false if more than one folder found
		  *
		  * @param string $folderName Folder name
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Folder Folder API
		  */
		public function retrieveFolderApi($folderName)
		{
			$folderId = $this->retrieveFolderId($folderName);
			return ($folderId !== false) ? ($this->factoryObjectApi($folderId)) : (false);
		}

		/**
		  * Retrieves all folders matches request from parent folder
		  *
		  * All arguments must be optional
		  *
		  * @param string $folderName Folder name
		  * @return false|array Folders
		  */
		public function retrieveFolders($folderName = null)
		{
			if($this->hasFolderId())
			{
				if(($folders = $this->_getThisCache(static::OBJECT_TYPE)) !== false) {
					$folders = static::_filterObjects($folders, static::FIELD_PARENT_ID, (string) $this->getFolderId());
					return static::_filterObjects($folders, static::FIELD_NAME, $folderName);
				}
				else {
					$folders = $this->_adapter->getFolders($this->getFolderId());
					return $this->_filterObjects($folders, static::FIELD_NAME, $folderName);
				}
			}
			else {
				return false;
			}
		}

		/**
		  * Gets all folders matches request from parent folder
		  *
		  * All arguments must be optional
		  *
		  * @param string $folderName Folder name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array Folders
		  */
		public function getFolders($folderName = '*', $strict = false)
		{
			return $this->findFolders($folderName, $strict);
		}

		/**
		  * Finds all folders matches request from parent folder
		  *
		  * @param string $folderName Folder name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array Folders
		  */
		public function findFolders($folderName, $strict = false)
		{
			if($this->hasFolderId()) {
				return static::_searchFolders($this->_adapter, $folderName, $this->getFolderId(), null, $strict);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all folders matches request
		  *
		  * @param string $folderName Folder name, wildcard * is allowed
		  * @param null|int $folderId Folder ID
		  * @param null|int $sectionId Section ID
		  * @param bool $strict
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @return false|array Folders
		  */
		public static function searchFolders($folderName, $folderId = null, $sectionId = null, $strict = false, Adapter $IPAM = null)
		{
			return static::_searchFolders($IPAM, $folderName, $folderId, $sectionId, $strict);
		}

		/**
		  * Return all folders matches request
		  *
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $folderName Folder name, wildcard * is allowed
		  * @param null|int $folderId Folder ID
		  * @param null|int $sectionId Section ID
		  * @param bool $strict
		  * @return false|array Folders
		  */
		protected static function _searchFolders(Adapter $IPAM = null, $folderName = '*', $folderId = null, $sectionId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if(($folders = static::_getSelfCache(static::OBJECT_TYPE, $IPAM)) !== false)
			{
				if(C\Tools::is('int&&>=0', $folderId)) {
					$folders = static::_filterObjects($folders, static::FIELD_PARENT_ID, (string) $folderId);
				}

				if(C\Tools::is('int&&>=0', $sectionId)) {
					$folders = static::_filterObjects($folders, static::FIELD_SECTION_ID, (string) $sectionId);
				}

				return static::_searchObjects($folders, static::FIELD_NAME, $folderName, $strict);
			}
			else {
				return $IPAM->searchFolderName($folderName, $folderId, $sectionId, $strict);
			}
		}

		/**
		  * Subnet name is not unique (IPv4 and IPv6)
		  * Return false if more than one subnet found
		  *
		  * @var string $subnet Subnet CIDR or name, wildcard * is allowed for name only
		  * @return false|array Subnet
		  */
		public function getSubnet($subnet)
		{
			$subnets = $this->getSubnets($subnet);
			return ($subnets !== false && count($subnets) === 1) ? ($subnets[0]) : (false);
		}

		/**
		  * Subnet name is not unique (IPv4 and IPv6)
		  * Return false if more than one subnet found
		  *
		  * @var string $subnet Subnet CIDR or name, wildcard * is allowed for name only
		  * @return false|array Subnet ID
		  */
		public function getSubnetId($subnet)
		{
			$subnet = $this->getSubnet($subnet);
			$subnetApi = $this->_resolver->resolve('Subnet');
			return ($subnet !== false) ? ($subnet[$subnetApi::FIELD_ID]) : (false);
		}

		/**
		  * Subnet name is not unique (IPv4 and IPv6)
		  * Return false if more than one subnet found
		  *
		  * @var string $subnet Subnet CIDR or name, wildcard * is allowed for name only
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Folder Subnet API
		  */
		public function getSubnetApi($subnet)
		{
			$subnetId = $this->getSubnetId($subnet);
			$subnetApi = $this->_resolver->resolve('Subnet');
			return ($subnetId !== false) ? ($subnetApi::factory($subnetId, $this->_service)) : (false);
		}

		/**
		  * Gets all subnets matches request from parent folder
		  *
		  * All arguments must be optional
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed for name only
		  * @return false|array Subnets
		  */
		public function getSubnets($subnet = null)
		{
			if($this->hasFolderId()) {
				$subnetsApi = $this->_resolver->resolve('Subnets');
				return $subnetsApi::searchSubnets($subnet, null, null, $this->getFolderId(), true, $this->_adapter);
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
			if($this->hasFolderId()) {
				$subnetsApi = $this->_resolver->resolve('Subnets');
				return $subnetsApi::searchSubnets($subnet, $IPv, $this->_adapter::SUBNET_ROOT_ID, $this->getFolderId(), $strict);
			}
			else {
				return false;
			}
		}
	}