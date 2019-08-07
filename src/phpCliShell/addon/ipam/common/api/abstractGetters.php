<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use ReflectionClass;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Resolver;
	use PhpCliShell\Addon\Ipam\Common\Orchestrator;

	abstract class AbstractGetters extends C\Addon\Api\AbstractGetters
	{
		/**
		  * @return \PhpCliShell\Addon\Ipam\Common\Resolver
		  */
		protected function _newResolver()
		{
			$ReflectionClass = new ReflectionClass($this);
			$thisNamespace = $ReflectionClass->getNamespaceName();

			$resolver = new Resolver($thisNamespace);	// namespace of this
			$resolver->addNamespace(__NAMESPACE__);		// namespace of self
			return $resolver;
		}

		/**
		  * @return \PhpCliShell\Addon\Ipam\Common\Orchestrator
		  */
		protected static function _getOrchestrator()
		{
			return Orchestrator::getInstance();
		}
	}