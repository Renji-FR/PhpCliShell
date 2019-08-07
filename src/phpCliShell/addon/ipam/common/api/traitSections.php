<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Adapter;

	trait TraitSections
	{
		/**
		  * @return bool
		  */
		public function hasSectionId()
		{
			return $this->hasObjectId();
		}

		/**
		  * @return false|int
		  */
		public function getSectionId()
		{
			return $this->getObjectId();
		}

		/**
		  * @return bool
		  */
		public function hasSectionApi()
		{
			return $this->hasObjectApi();
		}

		/**
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Section
		  */
		public function getSectionApi()
		{
			return $this->getObjectApi();
		}

		/**
		  * Retrieve section from parent or root section
		  *
		  * Section name must be unique
		  * Return false if more than one section found
		  *
		  * @param string $sectionName Section name
		  * @return false|array Section
		  */
		public function retrieveSection($sectionName)
		{
			$sections = $this->retrieveSections($sectionName);
			return ($sections !== false && count($sections) === 1) ? ($sections[0]) : (false);
		}

		/**
		  * Retrieve section ID from parent or root section
		  *
		  * Section name must be unique
		  * Return false if more than one section found
		  *
		  * @param string $sectionName Section name
		  * @return false|int Section ID
		  */
		public function retrieveSectionId($sectionName)
		{
			$section = $this->retrieveSection($sectionName);
			return ($section !== false) ? ($section[static::FIELD_ID]) : (false);
		}

		/**
		  * Retrieve section API from parent or root section
		  *
		  * Section name must be unique
		  * Return false if more than one section found
		  *
		  * @param string $sectionName Section name
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Section Section API
		  */
		public function retrieveSectionApi($sectionName)
		{
			$sectionId = $this->retrieveSectionId($sectionName);
			return ($sectionId !== false) ? ($this->factoryObjectApi($sectionId)) : (false);
		}

		/**
		  * Retrieves all sections matches request from parent or root sections
		  * If section ID is not present, root sections are returned
		  *
		  * All arguments must be optional
		  *
		  * @param string $sectionName Section name
		  * @return false|array Sections
		  */
		public function retrieveSections($sectionName = null)
		{
			if($this->hasSectionId())
			{
				if(($sections = $this->_getThisCache(static::OBJECT_TYPE)) !== false) {
					$sections = static::_filterObjects($sections, static::FIELD_PARENT_ID, (string) $this->getSectionId());
					return static::_filterObjects($sections, static::FIELD_NAME, $sectionName);
				}
				else {
					$sections = $this->_adapter->getSections($this->getSectionId());
					return $this->_filterObjects($sections, static::FIELD_NAME, $sectionName);
				}
			}
			else {
				return $this->getRootSections($sectionName, true);
			}
		}

		/**
		  * Return all root sections matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $sectionName Section name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function getRootSections($sectionName = null, $strict = false)
		{
			return $this->findRootSections($sectionName, $strict);
		}

		/**
		  * Return all root sections matches request
		  *
		  * @param string $sectionName Section name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function findRootSections($sectionName, $strict = false)
		{
			return static::searchRootSections($sectionName, $strict, $this->_adapter);
		}

		/**
		  * Return all root sections matches request
		  *
		  * @param string $sectionName Section name, wildcard * is allowed
		  * @param bool $strict
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @return false|array
		  */
		public static function searchRootSections($sectionName, $strict = false, Adapter $IPAM = null)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if(($sections = static::_getSelfCache(static::OBJECT_TYPE, $IPAM)) !== false) {
				$sections = static::_filterObjects($sections, static::FIELD_PARENT_ID, $IPAM::SECTION_ROOT_ID);
				return static::_searchObjects($sections, static::FIELD_NAME, $sectionName, $strict);
			}
			else {
				return $IPAM->searchSectionName($sectionName, $IPAM::SECTION_ROOT_ID, $strict);
			}
		}

		/**
		  * Return all sections matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $sectionName Section name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function getSections($sectionName = '*', $strict = false)
		{
			return $this->findSections($sectionName, $strict);
		}

		/**
		  * Return all sections matches request
		  *
		  * @param string $sectionName Section name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function findSections($sectionName, $strict = false)
		{
			if(!$this->hasSectionId()) {
				return $this->findRootSections($sectionName, $strict);
			}
			else {
				return static::_searchSections($this->_adapter, $sectionName, $this->getSectionId(), $strict);
			}
		}

		/**
		  * Return all sections matches request
		  *
		  * Ne pas rechercher que les sections root si sectionId est égale à null
		  *
		  * @param string $sectionName Section name, wildcard * is allowed
		  * @param int $sectionId Section ID
		  * @param bool $strict
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @return false|array
		  */
		public static function searchSections($sectionName, $sectionId = null, $strict = false, Adapter $IPAM = null)
		{
			return static::_searchSections($IPAM, $sectionName, $sectionId, $strict);
		}

		/**
		  * Return all sections matches request
		  *
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $sectionName Section name, wildcard * is allowed
		  * @param null|int $sectionId Section ID
		  * @param bool $strict
		  * @return false|array
		  */
		protected static function _searchSections(Adapter $IPAM = null, $sectionName = '*', $sectionId = null, $strict = false)
		{
			return static::_searchSectionNames($IPAM, $sectionName, $sectionId, $strict);
		}

		/**
		  * Return all sections matches request
		  *
		  * Ne pas rechercher que les sections root si sectionId est égale à null
		  *
		  * @param string $sectionName Section name, wildcard * is allowed
		  * @param int $sectionId Section ID
		  * @param bool $strict
		  * @return false|array
		  */
		public function findSectionNames($sectionName, $sectionId = null, $strict = false)
		{
			return static::_searchSectionNames($this->_adapter, $sectionName, $sectionId, $strict);
		}

		/**
		  * Return all sections matches request
		  *
		  * Ne pas rechercher que les sections root si sectionId est égale à null
		  *
		  * @param string $sectionName Section name, wildcard * is allowed
		  * @param int $sectionId Section ID
		  * @param bool $strict
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @return false|array
		  */
		public static function searchSectionNames($sectionName, $sectionId = null, $strict = false, Adapter $IPAM = null)
		{
			return static::_searchSectionNames($IPAM, $sectionName, $sectionId, $strict);
		}

		/**
		  * Return all sections matches request
		  *
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @param string $sectionName Section name, wildcard * is allowed
		  * @param null|int $sectionId Section ID
		  * @param bool $strict
		  * @return false|array
		  */
		protected static function _searchSectionNames(Adapter $IPAM = null, $sectionName = '*', $sectionId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if(($sections = static::_getSelfCache(static::OBJECT_TYPE, $IPAM)) !== false)
			{
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$sections = static::_filterObjects($sections, static::FIELD_PARENT_ID, (string) $sectionId);
				}

				return static::_searchObjects($sections, static::FIELD_NAME, $sectionName, $strict);
			}
			else {
				return $IPAM->searchSectionName($sectionName, $sectionId, $strict);
			}
		}

		/**
		  * Folder name is not unique (IPv4 and IPv6)
		  * Return false if more than one folder found
		  *
		  * @var string $folderName
		  * @return false|array Folder
		  */
		public function getFolder($folderName)
		{
			$folders = $this->getFolders($folderName);
			return ($folders !== false && count($folders) === 1) ? ($folders[0]) : (false);
		}

		/**
		  * Folder name is not unique (IPv4 and IPv6)
		  * Return false if more than one folder found
		  *
		  * @var string $folderName
		  * @return false|int Folder ID
		  */
		public function getFolderId($folderName)
		{
			$folder = $this->getFolder($folderName);
			$folderApi = $this->_resolver->resolve('Folder');
			return ($folder !== false) ? ($folder[$folderApi::FIELD_ID]) : (false);
		}

		/**
		  * Folder name is not unique (IPv4 and IPv6)
		  * Return false if more than one folder found
		  *
		  * @var string $folderName
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Folder Folder API
		  */
		public function getFolderApi($folderName)
		{
			$folderId = $this->getFolderId($folderName);
			$folderApi = $this->_resolver->resolve('Folder');
			return ($folderId !== false) ? ($folderApi::factory($folderId, $this->_service)) : (false);
		}

		/**
		  * Gets all folders matches request from parent section
		  *
		  * All arguments must be optional
		  *
		  * @param string $folderName Folder name, wildcard * is allowed
		  * @return false|array Folders
		  */
		public function getFolders($folderName = null)
		{
			if($this->hasSectionId()) {
				$foldersApi = $this->_resolver->resolve('Folders');
				return $foldersApi::searchFolders($folderName, null, $this->getSectionId(), true, $this->_adapter);
			}
			else {
				return false;
			}
		}

		/**
		  * Finds root folders matches request from parent section
		  *
		  * Only root folder are returned
		  *
		  * @param string $folderName Folder name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array Folders
		  */
		public function findFolders($folderName, $strict = false)
		{
			if($this->hasSectionId()) {
				$foldersApi = $this->_resolver->resolve('Folders');
				return $foldersApi::searchFolders($folderName, $this->_adapter::FOLDER_ROOT_ID, $this->getSectionId(), $strict, $this->_adapter);
			}
			else {
				return false;
			}
		}
	}