<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Netbox\Resolver;
	use PhpCliShell\Addon\Ipam\Netbox\Orchestrator;

	abstract class AbstractGetters extends Common\Api\AbstractGetters implements InterfaceGetters
	{
		/**
		  * @return \PhpCliShell\Addon\Ipam\Netbox\Resolver
		  */
		protected function _newResolver()
		{
			return new Resolver(__NAMESPACE__);
		}

		/**
		  * @return \PhpCliShell\Addon\Ipam\Netbox\Orchestrator
		  */
		protected static function _getOrchestrator()
		{
			return Orchestrator::getInstance();
		}
	}