<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam;

	use PhpCliShell\Addon\Ipam\Common;

	class Orchestrator extends Common\Orchestrator
	{
		use Common\OrchestratorTrait;

		/**
		  * @var string
		  */
		const SERVICE_NAME = 'phpIPAM';


		/**
		  * @param string $id
		  * @return \PhpCliShell\Addon\Ipam\Phpipam\Service
		  */
		protected function _newService($id)
		{
			return new Service($id, $this->_config);
		}
	}