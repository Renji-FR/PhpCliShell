<?php
	namespace PhpCliShell\Addon\Ipam\Common\Service;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Api;

	class Store extends C\Addon\Service\Store
	{
		/**
		  * @return bool Return initialization status
		  */
		protected function _initialization()
		{
			return (
				$this->newContainer(Api\Subnet::OBJECT_TYPE) !== false &&
				$this->newContainer(Api\Vlan::OBJECT_TYPE) !== false &&
				$this->newContainer(Api\Address::OBJECT_TYPE) !== false
			);
		}

		/**
		  * @param string $type
		  * @return \PhpCliShell\Addon\Ipam\Common\Service\StoreContainer
		  */
		protected function _newContainer($type)
		{
			return new StoreContainer($this->_service, $type, false);
		}
	}