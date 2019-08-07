<?php
	namespace PhpCliShell\Core\Addon\Service;

	use PhpCliShell\Core\Addon\Exception;
	use PhpCliShell\Core\Addon\Api\AbstractApi;

	abstract class StoreContainer extends Container
	{
		/**
		  * @param \PhpCliShell\Core\Addon\Api\AbstractApi $object
		  * @return $this
		  */
		public function assign(AbstractApi $object)
		{
			return $this->register($object->id, $object);
		}

		/**
		  * @param \PhpCliShell\Core\Addon\Api\AbstractApi $object
		  * @return $this
		  */
		public function unassign(AbstractApi $object)
		{
			return $this->unregister($object->id);
		}

		/**
		  * @param string $id
		  * @param \PhpCliShell\Core\Addon\Api\AbstractApi $object
		  * @return $this
		  */
		public function register($id, AbstractApi $object)
		{
			if($object->service->id !== $this->_service->id) {
				throw new Exception(ucfirst($object::OBJECT_NAME)." object service does not match Store container service", E_USER_ERROR);
			}

			$this->_register($id, $object);
			return $this;
		}

		public function unregister($id)
		{
			$this->_unregister($id);
			return $this;
		}

		/**
		  * @param string $id
		  * @return false|\PhpCliShell\Core\Addon\Api\AbstractApi
		  */
		public function get($id)
		{
			$object = $this->retrieve($id);

			if($object === false)
			{
				$objectCache = $this->retrieveFromCache($id);

				if($objectCache === false)
				{
					$object = $this->_new($id);

					if($object !== false) {
						$this->assign($object);
					}
				}
				else
				{
					$object = $this->_new(null);

					if($object !== false)
					{
						$status = $object->wakeup($objectCache);

						if($status) {
							$this->assign($object);
						}
						else {
							$object = false;
						}
					}
				}
			}

			return $object;
		}

		/**
		  * @param null|string $id
		  * @return false|\PhpCliShell\Core\Addon\Api\AbstractApi
		  */
		abstract protected function _new($id = null);

		/**
		  * @param string $id
		  * @return false|mixed
		  */
		protected function retrieveFromCache($id)
		{
			$cache = $this->_service->cache;

			if($cache !== false && $cache->isReady($this->_type)) {
				$container = $cache->getContainer($this->_type);
				return $container->retrieve($id);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $id
		  * @return false|mixed
		  */
		protected function getFromCache($id)
		{
			return $this->retrieveFromCache($id);
		}
	}