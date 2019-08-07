<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Addon\Ipam\Common;

	/**
	  * /!\ Folder has subnet and mask equal to null
	  * Do not use these fields, it is not a subnet!
	  */
	class Folder extends Common\Api\Folder implements InterfaceApi
	{
		use TraitApi;

		/**
		  * @var string
		  */
		const FIELD_ID = 'id';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var string
		  */
		const FIELD_DESC = 'description';

		/**
		  * @var string
		  */
		const FIELD_SECTION_ID = 'region_id';

		/**
		  * @var string
		  */
		const FIELD_PARENT_ID = 'parent_id';


		/**
		  * Gets parent folder ID
		  *
		  * @return false|int Folder ID
		  */
		public function getParentId()
		{
			return false;
		}

		/**
		  * Gets parent folder
		  * Do not filter folder
		  *
		  * @return false|array Folder
		  */
		public function getParent()
		{
			return false;
		}
	}