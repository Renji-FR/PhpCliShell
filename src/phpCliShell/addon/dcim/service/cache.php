<?php
	namespace PhpCliShell\Addon\Dcim\Service;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Dcim\Api;

	class Cache extends C\Addon\Service\Cache
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
		  * @return \PhpCliShell\Addon\Dcim\Service\CacheContainer
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
				case Api\Location::OBJECT_TYPE: {
					return $this->refreshLocations();
				}
				case Api\Cabinet::OBJECT_TYPE: {
					return $this->refreshCabinets();
				}
				case Api\Equipment::OBJECT_TYPE: {
					return $this->refreshEquipments();
				}
				default: {
					return false;
				}
			}
		}

		/**
		  * @return bool
		  */
		public function refreshLocations()
		{
			if($this->isEnabled() && $this->cleaner(Api\Location::OBJECT_TYPE))
			{
				$locations = Api\Location::searchLocations(Api\Location::WILDCARD);

				if($locations !== false)
				{
					$container = $this->getContainer(Api\Location::OBJECT_TYPE, true);
					$status = $container->registerSet(Api\Location::FIELD_ID, $locations);

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
		public function refreshCabinets()
		{
			if($this->isEnabled() && $this->cleaner(Api\Cabinet::OBJECT_TYPE))
			{
				$cabinets = Api\Cabinet::searchCabinets(Api\Cabinet::WILDCARD);

				if($cabinets !== false)
				{
					$container = $this->getContainer(Api\Cabinet::OBJECT_TYPE, true);
					$status = $container->registerSet(Api\Cabinet::FIELD_ID, $cabinets);

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
		public function refreshEquipments()
		{
			return false;
		}
	}