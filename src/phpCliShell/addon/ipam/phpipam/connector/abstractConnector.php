<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Connector;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Phpipam\Adapter;

	abstract class AbstractConnector extends Adapter implements Common\Connector\InterfaceConnector, InterfaceConnector
	{
		use Common\Connector\TraitConnector;
	}