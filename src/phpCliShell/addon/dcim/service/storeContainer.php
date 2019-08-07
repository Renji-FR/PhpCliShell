<?php
	namespace PhpCliShell\Addon\Dcim\Service;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Dcim\Api;

	class StoreContainer extends C\Addon\Service\StoreContainer
	{
		/**
		  * @param null|string $id
		  * @return false|\PhpCliShell\Addon\Dcim\Api\ApiAbstract
		  */
		protected function _new($id = null)
		{
			switch($this->_type)
			{
				case Api\Location::OBJECT_TYPE: {
					return new Api\Location($id, $this->_service);
				}
				case Api\Cabinet::OBJECT_TYPE: {
					return new Api\Cabinet($id, $this->_service);
				}
				case Api\Equipment::OBJECT_TYPE: {
					return new Api\Equipment($id, $this->_service);
				}
				default: {
					return false;
				}
			}
		}
	}