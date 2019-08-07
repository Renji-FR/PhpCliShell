<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Netbox\Adapter;

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
		const FIELD_SECTION_ID = Folder::FIELD_SECTION_ID;

		/**
		  * @var string
		  */
		const FIELD_PARENT_ID = Folder::FIELD_PARENT_ID;

		/**
		  * @var string
		  */
		const RESOLVER_OBJECT_API_NAME = 'Folder';


		/**
		  * Return all master folders matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $folderName Folder name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function getMasterFolders($folderName = null, $strict = false)
		{
			return $this->findMasterFolders($folderName, $strict);
		}

		/**
		  * Return all master folders matches request
		  *
		  * @param string $folderName Folder name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function findMasterFolders($folderName, $strict = false)
		{
			return static::searchMasterFolders($folderName, $strict, $this->_adapter);
		}

		/**
		  * Return all master folders matches request
		  *
		  * @param string $folderName Folder name, wildcard * is allowed
		  * @param bool $strict
		  * @param null|\PhpCliShell\Addon\Ipam\Common\Adapter $IPAM IPAM adapter
		  * @return false|array
		  */
		public static function searchMasterFolders($folderName, $strict = false, Adapter $IPAM = null)
		{
			if($IPAM === null) {
				$IPAM = static::_getAdapter();
			}

			if(($folders = static::_getSelfCache(static::OBJECT_TYPE, $IPAM)) !== false) {
				$folders = static::_filterObjects($folders, static::FIELD_SECTION_ID, null);
				return static::_searchObjects($folders, static::FIELD_NAME, $folderName, $strict);
			}
			else {
				return $IPAM->searchFolderName($folderName, null, $IPAM::SECTION_ROOT_ID, $strict);
			}
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
			return array();
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
			if(!C\Tools::is('int&&>0', $folderId))
			{
				if($IPAM === null) {
					$IPAM = static::_getAdapter();
				}

				if(($folders = static::_getSelfCache(static::OBJECT_TYPE, $IPAM)) !== false)
				{
					if(C\Tools::is('int&&>=0', $sectionId)) {
						$folders = static::_filterObjects($folders, static::FIELD_SECTION_ID, (string) $sectionId);
					}

					return static::_searchObjects($folders, static::FIELD_NAME, $folderName, $strict);
				}
				else {
					return $IPAM->searchFolderName($folderName, $folderId, $sectionId, $strict);
				}
			}
			else {
				return array();
			}
		}
	}