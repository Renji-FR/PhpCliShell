<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Netbox\Adapter;

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
			$foldersApi = $this->_resolver->resolve('Folders');

			if(!$this->hasSectionId()) {
				return $foldersApi::searchMasterFolders($folderName, $strict, $this->_adapter);
			}
			else {
				return $foldersApi::searchFolders($folderName, $this->_adapter::FOLDER_ROOT_ID, $this->getSectionId(), $strict, $this->_adapter);
			}
		}
	}