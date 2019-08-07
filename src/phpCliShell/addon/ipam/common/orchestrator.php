<?php
	namespace PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Core as C;

	abstract class Orchestrator extends C\Addon\Orchestrator
	{
		/**
		  * @var string
		  */
		const SERVICE_NAME = 'IPAM';


		/**
		  * @param string $id
		  * @return \PhpCliShell\Addon\Ipam\Common\ServiceAbstract
		  */
		protected function _newService($id)
		{
			return new Service($id, $this->_config);
		}
	}