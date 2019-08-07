<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Phpipam\Tools;
	use PhpCliShell\Addon\Ipam\Phpipam\Adapter;

	/**
	  * /!\ Folder has subnet and mask equal to null
	  * Do not use these fields, it is not a subnet!
	  *
	  * Folder has same model as subnet, same DB table!
	  */
	class Folder extends Common\Api\Folder implements InterfaceApi
	{
		use TraitApi;

		/**
		  * /!\ Folder est un subnet donc se baser sur subnet (même modèle)
		  *
		  * @var string
		  */
		const FIELD_SECTION_ID = Subnet::FIELD_SECTION_ID;

		/**
		  * /!\ Folder est un subnet donc se baser sur subnet (même modèle)
		  *
		  * @var string
		  */
		const FIELD_PARENT_ID = Subnet::FIELD_PARENT_ID;
	}