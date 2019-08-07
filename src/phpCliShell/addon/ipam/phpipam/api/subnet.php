<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Phpipam\Tools;
	use PhpCliShell\Addon\Ipam\Phpipam\Adapter;

	class Subnet extends Common\Api\Subnet implements InterfaceApi
	{
		use TraitApi, Common\Api\TraitSectionChild;

		/**
		  * @var string
		  */
		const FIELD_SECTION_ID = 'sectionId';

		/**
		  * @var string
		  */
		const FIELD_FOLDER_ID = 'masterSubnetId';

		/**
		  * @var string
		  */
		const FIELD_PARENT_ID = 'masterSubnetId';

		/**
		  * @var array
		  */
		const USAGE_FIELDS = array(
				'used' => 'used', 'total' => 'maxhosts', 'free' => 'freehosts', 'free%' => 'freehosts_percent',
				'offline' => 'Offline_percent', 'used%' => 'Used_percent', 'reserved%' => 'Reserved_percent', 'dhcp%' => 'DHCP_percent'
		);


		/**
		  * @param array $subnet Subnet
		  * @return array Subnet
		  */
		protected function _formatName(array $subnet)
		{
			if(!C\Tools::is('string&&!empty', $subnet[static::FIELD_NAME])) {
				$subnet[static::FIELD_NAME] = $subnet[static::FIELD_SUBNET].'/'.$subnet['mask'];
			}

			return $subnet;
		}

		public function getNetwork()
		{
			return $this->_getField(static::FIELD_SUBNET, 'string&&!empty');
		}

		public function getCidrMask()
		{
			return $this->_getField('mask', 'int&&>0');
		}

		/**
		  * @return false|array
		  */
		public function getUsage()
		{
			if($this->subnetExists()) {
				return $this->_adapter->getSubnetUsage($this->getSubnetId());
			}
			else {
				return false;
			}
		}

		/**
		  * @param bool $includeLabel
		  * @return false|array
		  */
		public function getPaths($includeLabel = false)
		{
			if($this->objectExists())
			{
				if($this->_path === null)
				{
					$objectApi = $this->getParentApi();

					if($objectApi !== false) {
						$this->_path = $objectApi->getPaths(true);
					}
					elseif(($folderApi = $this->getFolderApi()) !== false) {
						$this->_path = $folderApi->getPaths(true);
					}
					elseif(($sectionApi = $this->getSectionApi()) !== false) {
						$this->_path = $sectionApi->getPaths(true);
					}
					else {
						$this->_path = array();
					}
				}

				if($this->_path !== false)
				{
					$path = $this->_path;

					if($includeLabel && $this->hasObjectLabel()) {
						$path[] = $this->getObjectLabel();
					}

					return $path;
				}

				return $path;
			}
			elseif($includeLabel && $this->hasObjectLabel()) {
				return array($this->getObjectLabel());
			}

			return false;
		}

		/**
		  * Gets parent folder/subnet ID
		  *
		  * @return false|int Folder/Subnet ID
		  */
		protected function _getParentId()
		{
			return $this->_getField(static::FIELD_PARENT_ID, 'int&&>0');
		}

		/**
		  * @return false|int
		  */
		public function getFolderId()
		{
			if($this->objectExists())
			{
				$parentApi = $this;

				while(true)
				{
					$newParentApi = $parentApi->getParentApi();

					if($newParentApi !== false) {
						$parentApi = $newParentApi;
					}
					else {
						break;
					}
				}

				return $parentApi->_getParentId();
			}
			else {
				return false;
			}
		}

		/**
		  * Gets parent
		  * Do not filter parent
		  *
		  * @return false|array Parent
		  */
		public function getParent()
		{
			$parentId = $this->_getParentId();

			if($parentId !== false)
			{
				$cacheContainer = $this->_getCacheContainer();
				
				if($cacheContainer !== false) {
					return $cacheContainer->retrieve($parentId);
				}
				else {
					return $this->_adapter->getSubnet($parentId);
				}
			}

			return false;
		}

		/**
		  * Gets parent ID
		  *
		  * @return false|int Parent ID
		  */
		public function getParentId()
		{
			$parent = $this->getParent();
			return ($parent !== false) ? ($parent[self::FIELD_ID]) : (false);
		}

		/**
		  * @param string $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'sectionApi':
				case 'parentSectionApi': {
					return $this->getSectionApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}