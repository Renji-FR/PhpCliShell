<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Addon\Ipam\Common;

	class Section extends Common\Api\Section implements InterfaceApi
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
		const FIELD_DESC = 'slug';

		/**
		  * @var string
		  */
		const FIELD_PARENT_ID = 'parent_id';
	}