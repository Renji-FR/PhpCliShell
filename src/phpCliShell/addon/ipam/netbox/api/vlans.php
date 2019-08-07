<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Netbox\Adapter;

	class Vlans extends AbstractGetters
	{
		use Common\Api\TraitVlans;

		/**
		  * @var string
		  */
		const OBJECT_TYPE = Vlan::OBJECT_TYPE;

		/**
		  * @var string
		  */
		const FIELD_ID = Vlan::FIELD_ID;

		/**
		  * @var string
		  */
		const FIELD_NAME = Vlan::FIELD_NAME;

		/**
		  * @var string
		  */
		const RESOLVER_OBJECT_API_NAME = 'Vlan';
	}