<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam;

	use PhpCliShell\Addon\Ipam\Common;

	class Resolvers extends Common\Resolvers
	{
		/**
		  * @param null|string $namespace Namespace
		  * @return \PhpCliShell\Addon\Ipam\Common\Resolver;
		  */
		protected function _newResolver($namespace = null)
		{
			$resolver = parent::_newResolver($namespace);
			$namespace = __NAMESPACE__ .'\\'.$namespace;
			$resolver->addNamespace($namespace, true);
			return $resolver;
		}
	}