<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Addon\Ipam\Common;

	class Subnet extends Common\Api\Subnet implements InterfaceApi
	{
		use TraitApi;

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
		const FIELD_SUBNET = 'prefix';

		/**
		  * @var string
		  */
		const FIELD_FOLDER_ID = 'site_id';

		/**
		  * @var string
		  */
		const FIELD_PARENT_ID = 'parent_id';

		/**
		  * @var string
		  */
		const FIELD_VLAN_ID = 'vlan_id';


		/**
		  * @return false|array
		  */
		public function getUsage()
		{
			return false;
		}
	}