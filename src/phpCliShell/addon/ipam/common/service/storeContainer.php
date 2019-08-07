<?php
	namespace PhpCliShell\Addon\Ipam\Common\Service;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Api;

	class StoreContainer extends C\Addon\Service\StoreContainer
	{
		/**
		  * @param null|string $id
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\AbstractApi
		  */
		protected function _new($id = null)
		{
			switch($this->_type)
			{
				case Api\Subnet::OBJECT_TYPE: {
					return new Api\Subnet($id, $this->_service);
				}
				case Api\Vlan::OBJECT_TYPE: {
					return new Api\Vlan($id, $this->_service);
				}
				case Api\Address::OBJECT_TYPE: {
					return new Api\Address($id, $this->_service);
				}
				default: {
					return false;
				}
			}
		}
	}