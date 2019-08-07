<?php
	namespace PhpCliShell\Addon\Ipam\Common\Service;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Api;

	class Cache extends C\Addon\Service\Cache
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
		  * @return \PhpCliShell\Addon\Ipam\Common\Service\CacheContainer
		  */
		protected function _newContainer($type)
		{
			return new CacheContainer($this->_service, $type, false);
		}

		/**
		  * @param string $type
		  * @return bool
		  */
		protected function _refresh($type)
		{
			switch($type)
			{
				case Api\Subnet::OBJECT_TYPE: {
					return $this->refreshSubnets();
				}
				case Api\Vlan::OBJECT_TYPE: {
					return $this->refreshVlans();
				}
				case Api\Address::OBJECT_TYPE: {
					return $this->refreshAddresses();
				}
				default: {
					return false;
				}
			}
		}

		/**
		  * @return bool
		  */
		public function refreshSubnets()
		{
			if($this->isEnabled() && $this->cleaner(Api\Subnet::OBJECT_TYPE))
			{
				$subnets = $this->service->adapter->getAllSubnets();

				if($subnets !== false)
				{
					$container = $this->getContainer(Api\Subnet::OBJECT_TYPE, true);
					$status = $container->registerSet(Api\Subnet::FIELD_ID, $subnets);

					if(!$status) {
						$container->reset();
					}

					return $status;
				}
			}

			return false;
		}

		/**
		  * @return bool
		  */
		public function refreshVlans()
		{
			if($this->isEnabled() && $this->cleaner(Api\Vlan::OBJECT_TYPE))
			{
				$vlans = $this->service->adapter->getAllVlans();

				if($vlans !== false)
				{
					$container = $this->getContainer(Api\Vlan::OBJECT_TYPE, true);
					$status = $container->registerSet(Api\Vlan::FIELD_ID, $vlans);

					if(!$status) {
						$container->reset();
					}

					return $status;
				}
			}

			return false;
		}

		/**
		  * @return bool
		  */
		public function refreshAddresses()
		{
			return false;
		}
	}