<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Tools;
	use PhpCliShell\Addon\Ipam\Common\Adapter;

	trait TraitFolderChild
	{
		/**
		  * @var false|\PhpCliShell\Addon\Ipam\Common\Api\Folder
		  */
		protected $_folderApi = null;


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
		  * @return false|int
		  */
		public function getFolderId()
		{
			if(static::FIELD_FOLDER_ID !== null) {
				return $this->_getField(static::FIELD_FOLDER_ID, 'int&&>0', 'int');
			}
			else {
				return false;
			}
		}

		/**
		  * @return false|\PhpCliShell\Addon\Ipam\Phpipam\Api\Folder
		  */
		public function getFolderApi()
		{
			if($this->_folderApi === null)
			{
				$folderId = $this->getFolderId();

				if($folderId !== false) {
					$folderApi = $this->_resolver->resolve('Folder');
					$this->_folderApi = $folderApi::factory($folderId, $this->_service);
				}
				else {
					$this->_folderApi = false;
				}
			}

			return $this->_folderApi;
		}

		/**
		  * @param bool $resetObjectId
		  * @return void
		  */
		protected function _hardReset($resetObjectId = false)
		{
			parent::_hardReset($resetObjectId);
			$this->_resetFolder();
		}

		/**
		  * @return void
		  */
		protected function _resetFolder()
		{
			$this->_folderApi = null;
		}

		/**
		  * @param string $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'folderApi':
				case 'parentFolderApi': {
					return $this->getFolderApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}