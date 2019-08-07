<?php
	namespace PhpCliShell\Core\Addon\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Core\Addon\Service;
	use PhpCliShell\Core\Addon\Adapter;
	use PhpCliShell\Core\Addon\Resolver;
	use PhpCliShell\Core\Addon\Exception;

	abstract class AbstractGetters
	{
		/**
		  * @var string
		  */
		const RESOLVER_OBJECT_API_NAME = null;

		/**
		  * @var string
		  */
		const WILDCARD = '*';

		/**
		  * @var \PhpCliShell\Core\Addon\Service
		  */
		protected $_service = null;

		/**
		  * @var \PhpCliShell\Core\Addon\Adapter
		  */
		protected $_adapter = null;

		/**
		  * @var \PhpCliShell\Core\Addon\Resolver
		  */
		protected $_resolver = null;

		/**
		  * @var string
		  */
		protected $_objectApiClass = null;

		/**
		  * @var mixed
		  */
		protected $_objectId = null;

		/**
		  * @var \PhpCliShell\Core\Addon\Api\AbstractApi
		  */
		protected $_objectApi = null;


		/**
		  * @param int|\PhpCliShell\Core\Addon\Api\AbstractApi $object
		  * @param \PhpCliShell\Core\Addon\Service $service
		  * @return $this
		  */
		public function __construct($object = null, Service $service = null)
		{
			$this->_initialization($object, $service);
		}

		/**
		  * @param null|int|\PhpCliShell\Core\Addon\Api\AbstractApi $object
		  * @param null|\PhpCliShell\Core\Addon\Service $service
		  * @return void
		  */
		protected function _initialization($object, Service $service = null)
		{
			if($object instanceof AbstractApi)
			{
				if($object->hasObjectId()) {
					$this->_objectApi = $object;
					$this->_objectId = $object->getObjectId();
					$this->_service = $object->getService();
					$this->_adapter = $object->getAdapter();
					$this->_resolver = $object->getResolver();
					$this->_objectApiClass = get_class($object);
				}
			}
			elseif($this->_objectIdIsValid($object)) {
				$this->_objectId = $object;
			}

			if($this->_service === null)
			{
				if($service !== null) {
					$this->_service = $service;
				}
				else {
					$this->_service = static::_getService();
				}

				$this->_adapter = $this->_service->adapter;

				if(($resolver = $this->_service->getResolver('api')) === false) {
					$resolver = $this->_newResolver();
				}

				/**
				  * Il faut toujours un Resolver afin de simplifier le code
				  * /!\ Le Resolver doit avoir son/ses namespace(s) directement sur API
				  */
				$this->_resolver = $resolver;
			}

			if(!$this->_service instanceof Service) {
				throw new Exception("Service is missing", E_USER_ERROR);
			}
			elseif(!$this->_adapter instanceof Adapter) {
				throw new Exception("Adapter is missing", E_USER_ERROR);
			}
			elseif(!$this->_resolver instanceof Resolver) {
				throw new Exception("Resolver is missing", E_USER_ERROR);
			}
		}

		/**
		  * @param null|mixed $objectId
		  * @param null|\PhpCliShell\Core\Addon\Service $service
		  * @return \PhpCliShell\Core\Addon\Api\AbstractApi
		  */
		public function factoryObjectApi($objectId = null, Service $service = null)
		{
			if($this->hasObjectId())
			{
				if($objectId === null || $objectId === $this->getObjectId())
				{
					if($this->hasObjectApi()) {
						return $this->getObjectApi();
					}
					else {
						$objectId = $this->getObjectId();
						$service = $this->_service;
						$registerObjectApi = true;
					}
				}
				elseif($objectId == $this->getObjectId()) {
					// @todo verifier si utile sinon supprimer
					throw new Exception("Object ID type missmatch, please open an issue", E_USER_ERROR);
				}
			}
			elseif($objectId === null) {
				throw new Exception("Unable to instanciate object API, object ID is missing", E_USER_ERROR);
			}

			if($service === null) {
				$service = $this->_service;
			}

			$apiClass = $this->getObjectApiClass();
			$objectApi = $apiClass::factory($objectId, $service);

			if(isset($registerObjectApi)) {
				$this->_objectApi = $objectApi;
			}

			return $objectApi;
		}

		/**
		  * @return string Return object API classname
		  */
		public function getObjectApiClass()
		{
			if($this->_objectApiClass === null) {
				$this->_objectApiClass = $this->_resolver->resolve(static::RESOLVER_OBJECT_API_NAME);
			}

			return $this->_objectApiClass;
		}

		/**
		  * @return \PhpCliShell\Core\Addon\Resolver
		  */
		abstract protected function _newResolver();

		/**
		  * @param mixed $objectId
		  * @return bool
		  */
		protected function _objectIdIsValid($objectId)
		{
			$apiClass = $this->getObjectApiClass();
			return $apiClass::objectIdIsValid($objectId);
		}

		/**
		  * @return bool
		  */
		public function hasObjectId()
		{
			return ($this->_objectId !== null);
		}

		/**
		  * @return false|mixed
		  */
		public function getObjectId()
		{
			return ($this->hasObjectId()) ? ($this->_objectId) : (false);
		}

		/**
		  * @return bool
		  */
		public function hasObjectApi()
		{
			return ($this->_objectApi !== null);
		}

		/**
		  * @return false|\PhpCliShell\Core\Addon\Api\AbstractApi
		  */
		public function getObjectApi()
		{
			return ($this->hasObjectApi()) ? ($this->_objectApi) : (false);
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'service': {
					return $this->_service;
				}
				case 'adapter': {
					return $this->_adapter;
				}
				case 'resolver': {
					return $this->_resolver;
				}
				case 'apiClass':
				case 'objectApiClass': {
					return $this->getObjectApiClass();
				}
				default: {
					throw new Exception("This attribute '".$name."' does not exist", E_USER_ERROR);
				}
			}
		}

		public function __call($name, array $arguments)
		{
			if(substr($name, 0, 3) === 'get')
			{
				$name = substr($name, 3);
				$name = mb_strtolower($name);

				switch($name)
				{
					case 'service': {
						return $this->_service;
					}
					case 'adapter': {
						return $this->_adapter;
					}
					case 'resolver': {
						return $this->_resolver;
					}
				}
			}

			throw new Exception("This method '".$name."' does not exist", E_USER_ERROR);
		}

		public static function __callStatic($name, array $arguments)
		{
			if(substr($name, 0, 3) === 'get')
			{
				$name = substr($name, 3);
				$name = mb_strtolower($name);

				switch($name)
				{
					case 'service': {
						return static::_getService();
					}
					case 'adapter': {
						return static::_getAdapter();
					}
				}
			}

			throw new Exception("This method '".$name."' does not exist", E_USER_ERROR);
		}

		/**
		  * @param false|array $objects
		  * @param null|string $field
		  * @param null|string|array $value
		  * @param bool $caseSensitive
		  * @return false|array
		  */
		protected static function _filterObjects($objects, $field, $value, $caseSensitive = true)
		{
			if(is_array($objects))
			{
				if($field !== null && $value !== null)
				{
					$results = array();
					$values = (array) $value;

					if(!$caseSensitive)
					{
						array_walk($values, function(&$value) {
							$value = mb_strtolower($value);
						});
						unset($value);
						
						array_walk($objects, function(&$object) use($field) {
							$object[$field] = mb_strtolower($object[$field]);
						});
						unset($object);
					}

					foreach($objects as $object)
					{
						if(in_array($object[$field], $values, true)) {
							$results[] = $object;
						}
					}

					return $results;
				}
				else {
					return $objects;
				}
			}
			else {
				return false;
			}
		}

		/**
		  * @param false|array $objects
		  * @param null|string $field
		  * @param null|string|array $value
		  * @param bool $strict
		  * @return false|array
		  */
		protected static function _searchObjects($objects, $field, $value, $strict = false)
		{
			if(is_array($objects))
			{
				if($field !== null && $value !== null)
				{
					$results = array();
					$values = (array) $value;

					if(in_array(static::WILDCARD, $values, true)) {
						return $objects;
					}
					else
					{
						$wc = preg_quote(static::WILDCARD, '#');

						foreach($values as $value)
						{
							$value = preg_quote($value, '#');
							$value = str_replace($wc, '.*', $value);
							$value = ($strict) ? ('^('.$value.')$') : ('^('.$value.')');

							foreach($objects as $index => $object)
							{
								if(preg_match('#'.$value.'#i', $object[$field])) {
									unset($objects[$index]);
									$results[] = $object;
								}
							}
						}

						return $results;
					}
				}
				else {
					return $objects;
				}
			}
			else {
				return false;
			}
		}

		/**
		  * @return \PhpCliShell\Core\Addon\Orchestrator
		  */
		abstract protected static function _getOrchestrator();

		/**
		  * @return \PhpCliShell\Core\Addon\Service
		  */
		protected static function _getService()
		{
			$service = static::_getOrchestrator()->service;

			if($service instanceof Service)
			{
				$isReady = $service->initialization();

				if($isReady) {
					return $service;
				}
				else {
					throw new Exception("Addon service is not ready", E_USER_ERROR);
				}
			}
			else {
				throw new Exception("Addon service is not available", E_USER_ERROR);
			}
		}

		/**
		  * @return \PhpCliShell\Core\Addon\Adapter
		  */
		protected static function _getAdapter()
		{
			$service = static::_getService();
			return $service->adapter;
		}

		/**
		  * @param string $type
		  * @param \PhpCliShell\Core\Addon\Adapter $adapter
		  * @return false|array
		  */
		protected function _getThisCache($type, Adapter $adapter = null)
		{
			if($adapter !== null) {
				$service = $adapter->service;
			}
			else {
				$service = $this->_service;
			}

			return static::_getServiceCache($service, $type);
		}

		/**
		  * @param string $type
		  * @param \PhpCliShell\Core\Addon\Adapter $adapter
		  * @return false|array
		  */
		protected static function _getSelfCache($type, Adapter $adapter = null)
		{
			if($adapter !== null) {
				$service = $adapter->service;
			}
			else {
				$service = static::_getService();
			}

			return static::_getServiceCache($service, $type);
		}

		/**
		  * @param \PhpCliShell\Core\Addon\Service $service
		  * @param string $type
		  * @return false|array
		  */
		protected static function _getServiceCache(Service $service, $type)
		{
			$cache = $service->cache;

			if($cache !== false && $cache->isEnabled())
			{
				$container = $cache->getContainer($type);

				if($container !== false) {
					return $container->getAll();
				}
			}

			return false;
		}
	}