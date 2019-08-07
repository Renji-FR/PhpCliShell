<?php
	namespace PhpCliShell\Addon\Ipam\Netbox;

	use PhpCliShell\Addon\Ipam\Common;

	class Resolver extends Common\Resolver
	{
		/**
		  * @return string
		  */
		protected function _getNamespace()
		{
			return __NAMESPACE__;
		}
	}