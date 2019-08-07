<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Service;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Netbox\Api;

	class StoreContainer extends Common\Service\StoreContainer
	{
		/**
		  * @param null|string $id
		  * @return false|\PhpCliShell\Addon\Ipam\Netbox\Api\InterfaceApi
		  */
		protected function _new($id = null)
		{
			switch($this->_type)
			{
				case Api\Section::OBJECT_TYPE: {
					return new Api\Section($id, $this->_service);
				}
				case Api\Folder::OBJECT_TYPE: {
					return new Api\Folder($id, $this->_service);
				}
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