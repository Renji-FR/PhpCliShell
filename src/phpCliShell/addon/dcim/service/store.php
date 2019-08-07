<?php
	namespace PhpCliShell\Addon\Dcim\Service;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Dcim\Api;

	class Store extends C\Addon\Service\Store
	{
		/**
		  * @return bool Return initialization status
		  */
		protected function _initialization()
		{
			return (
				$this->newContainer(Api\Location::OBJECT_TYPE) !== false &&
				$this->newContainer(Api\Cabinet::OBJECT_TYPE) !== false &&
				$this->newContainer(Api\Equipment::OBJECT_TYPE) !== false
			);
		}

		/**
		  * @param string $type
		  * @return PhpCliShell\Addon\Dcim\Service\StoreContainer
		  */
		protected function _newContainer($type)
		{
			return new StoreContainer($this->_service, $type, false);
		}
	}