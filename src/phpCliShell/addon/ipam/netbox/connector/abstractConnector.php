<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Connector;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Netbox\Adapter;

	abstract class AbstractConnector extends Adapter implements Common\Connector\InterfaceConnector, InterfaceConnector
	{
		use Common\Connector\TraitConnector;
	}