<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Service;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Phpipam\Api;

	class Cache extends Common\Service\Cache
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
		  * @return \PhpCliShell\Addon\Ipam\Phpipam\Service\CacheContainer
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
				case Api\Section::OBJECT_TYPE: {
					return $this->refreshSections();
				}
				case Api\Folder::OBJECT_TYPE: {
					return $this->refreshFolders();
				}
				default: {
					return parent::_refresh($type);
				}
			}
		}

		/**
		  * @return bool
		  */
		public function refreshSections()
		{
			if($this->isEnabled() && $this->cleaner(Api\Section::OBJECT_TYPE))
			{
				$sections = $this->service->adapter->getAllSections();

				if($sections !== false)
				{
					$container = $this->getContainer(Api\Section::OBJECT_TYPE, true);
					$status = $container->registerSet(Api\Section::FIELD_ID, $sections);

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
		public function refreshFolders()
		{
			if($this->isEnabled() && $this->cleaner(Api\Folder::OBJECT_TYPE))
			{
				$folders = $this->service->adapter->getAllFolders();

				if($folders !== false)
				{
					$container = $this->getContainer(Api\Folder::OBJECT_TYPE, true);
					$status = $container->registerSet(Api\Folder::FIELD_ID, $folders);

					if(!$status) {
						$container->reset();
					}

					return $status;
				}
			}

			return false;
		}
	}