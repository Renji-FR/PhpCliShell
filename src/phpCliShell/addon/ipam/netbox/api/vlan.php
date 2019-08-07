<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	class Vlan extends Common\Api\Vlan implements InterfaceApi
	{
		use TraitApi;

		/**
		  * @var string
		  */
		const FIELD_ID = 'id';

		/**
		  * @var string
		  */
		const FIELD_VLAN = 'vid';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var string
		  */
		const FIELD_DESC = 'description';
	}