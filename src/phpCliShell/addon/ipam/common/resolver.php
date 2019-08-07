<?php
	namespace PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Core as C;

	class Resolver extends C\Addon\Resolver
	{
		/**
		  * @return string
		  */
		protected function _getNamespace()
		{
			return __NAMESPACE__;
		}
	}