<?php
	namespace PhpCliShell\Addon\Ipam\Netbox;

	use PhpCliShell\Addon\Ipam\Common;

	class Orchestrator extends Common\Orchestrator
	{
		use Common\OrchestratorTrait;

		/**
		  * @var string
		  */
		const SERVICE_NAME = 'NetBox';


		/**
		  * @param string $id
		  * @return \PhpCliShell\Addon\Ipam\Netbox\Service
		  */
		protected function _newService($id)
		{
			return new Service($id, $this->_config);
		}
	}