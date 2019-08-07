<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Service;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Phpipam\Api;

	class Store extends Common\Service\Store
	{
		/**
		  * @return bool Return initialization status
		  */
		protected function _initialization()
		{
			return (
				$this->newContainer(Api\Section::OBJECT_TYPE) !== false &&
				$this->newContainer(Api\Folder::OBJECT_TYPE) !== false &&
				parent::_initialization() !== false
			);
		}

		/**
		  * @param string $type
		  * @return \PhpCliShell\Addon\Ipam\Phpipam\Service\StoreContainer
		  */
		protected function _newContainer($type)
		{
			return new StoreContainer($this->_service, $type, false);
		}
	}