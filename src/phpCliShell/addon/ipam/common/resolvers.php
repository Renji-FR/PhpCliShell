<?php
	namespace PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Core as C;

	class Resolvers extends C\Addon\Resolvers
	{
		/**
		  * @param null|string $namespace Namespace
		  * @return \PhpCliShell\Addon\Ipam\Common\Resolver;
		  */
		protected function _newResolver($namespace = null)
		{
			$namespace = __NAMESPACE__ .'\\'.$namespace;
			return new Resolver(rtrim($namespace, '\\'));
		}
	}