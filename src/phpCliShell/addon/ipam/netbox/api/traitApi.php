<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use ReflectionClass;

	use PhpCliShell\Addon\Ipam\Netbox\Resolver;
	use PhpCliShell\Addon\Ipam\Netbox\Orchestrator;

	trait TraitApi
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