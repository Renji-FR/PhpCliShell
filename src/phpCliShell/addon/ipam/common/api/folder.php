<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Tools;
	use PhpCliShell\Addon\Ipam\Common\Adapter;

	abstract class Folder extends AbstractParent
	{
		use TraitSectionChild;

		/**
		  * @var string
		  */
		const OBJECT_KEY = 'FOLDER';

		/**
		  * @var string
		  */
		const OBJECT_TYPE = 'folder';

		/**
		  * @var string
		  */
		const OBJECT_NAME = 'folder';

		/**
		  * @var string
		  */
		const FIELD_ID = 'id';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'description';

		/**
		  * @var string
		  */
		const FIELD_SECTION_ID = 'sectionId';

		/**
		  * @var string
		  */
		const RESOLVER_GETTERS_API_NAME = 'Folders';


		public function folderIdIsValid($folderId)
		{
			return $this->objectIdIsValid($folderId);
		}

		public function hasFolderId()
		{
			return $this->hasObjectId();
		}

		public function getFolderId()
		{
			return $this->getObjectId();
		}

		public function folderExists()
		{
			return $this->objectExists();
		}

		public function getFolderLabel()
		{
			return $this->getObjectLabel();
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_adapter->getFolder($this->getFolderId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		/**
		  * @param bool $includeLabel
		  * @return false|array
		  */
		public function getFolderPaths($includeLabel = false)
		{
			if($this->objectExists())
			{			
				$objectApi = $this->getParentApi();

				if($objectApi instanceof self) {
					$path = $objectApi->getFolderPaths(true);
				}
				else {
					$path = array();
				}

				if($includeLabel) {
					$path[] = $this->label;
				}

				return $path;
			}
			else {
				return false;
			}
		}

		/**
		  * Gets parent folder ID
		  *
		  * @return false|int Folder ID
		  */
		public function getParentId()
		{
			return $this->_getField(static::FIELD_PARENT_ID, 'int&&>0');
		}

		/**
		  * Gets parent folder
		  * Do not filter folder
		  *
		  * @return false|array Folder
		  */
		public function getParent()
		{
			$parentId = $this->getParentId();

			if($parentId !== false)
			{
				$cacheContainer = $this->_getCacheContainer();
				
				if($cacheContainer !== false) {
					return $cacheContainer->retrieve($parentId);
				}
				else {
					return $this->_adapter->getFolder($parentId);
				}
			}

			return false;
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
				case 'parentApi':
				case 'folderApi':
				case 'parentFolderApi': {
					return $this->getParentApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}