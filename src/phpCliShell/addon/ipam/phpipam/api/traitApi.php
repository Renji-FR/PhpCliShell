<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Api;

	use PhpCliShell\Addon\Ipam\Phpipam\Resolver;
	use PhpCliShell\Addon\Ipam\Phpipam\Orchestrator;

	trait TraitApi
	{
		/**
		  * @return \PhpCliShell\Addon\Ipam\Phpipam\Resolver
		  */
		protected function _newResolver()
		{
			return new Resolver(__NAMESPACE__);
		}

		/**
		  * @return \PhpCliShell\Addon\Ipam\Phpipam\Orchestrator
		  */
		protected static function _getOrchestrator()
		{
			return Orchestrator::getInstance();
		}
	}