<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	class Vlan extends Common\Api\Vlan implements InterfaceApi
	{
		use TraitApi;
	}