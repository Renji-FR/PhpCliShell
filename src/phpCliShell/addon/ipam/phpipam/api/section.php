<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Api;

	use PhpCliShell\Addon\Ipam\Common;

	class Section extends Common\Api\Section implements InterfaceApi
	{
		use TraitApi;

		/**
		  * @var string
		  */
		const FIELD_PARENT_ID = 'masterSection';
	}