<?php
	namespace PhpCliShell\Core\Addon\Api;

	use ReflectionClass;

	use PhpCliShell\Core as C;

	use PhpCliShell\Core\Addon\Service;
	use PhpCliShell\Core\Addon\Adapter;
	use PhpCliShell\Core\Addon\Resolver;
	use PhpCliShell\Core\Addon\Exception;

	abstract class AbstractApi implements InterfaceApi
	{
		/**
		  * @var string
		  */
		const RESOLVER_GETTERS_API_NAME = null;

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
		  * @var \PhpCliShell\Core\Addon\Api\AbstractGetters
		  */
		protected $_gettersApi = null;

		/**
		  * @var string
		  */
		protected $_errorMessage = null;

		/**
		  * @var int
		  */
		protected $_objectId = null;

		/**
		  * @var bool
		  */
		protected $_objectExists = null;		// /!\ Important null pour forcer la detection

		/**
		  * @var string
		  */
		protected $_objectLabel = null;			// /!\ Important null pour forcer la detection

		/**
		  * @var array
		  */
		protected $_objectDatas = null;


		/**
		  * @param mixed $objectId
		  * @param \PhpCliShell\Core\Addon\Service $service
		  * @return $this
		  */
		public function __construct($objectId = null, Service $service = null)
		{
			$this->_initialization($service);
			$this->_setObjectId($objectId);
		}

		/**
		  * @param mixed $objectId
		  * @param \PhpCliShell\Core\Addon\Service $service
		  * @return \PhpCliShell\Core\Addon\Api\AbstractApi
		  */
		public static function factory($objectId, Service $service = null)
		{
			if($service === null) {
				$service = static::_getService();
			}

			$store = $service->store;

			if($store !== false && $store->isReady(static::OBJECT_TYPE))
			{
				$storeContainer = $store->getContainer(static::OBJECT_TYPE);
				$api = $storeContainer->get($objectId);

				if($api !== false) {
					return $api;
				}
				else {
					throw new Exception("Unable to get API object '".static::class."' from store container", E_USER_ERROR);
				}
			}
			else {
				$className = static::class;
				return new $className($objectId, $service);
			}
		}

		/**
		  * @return string
		  */
		public static function getObjectType()
		{
			return static::OBJECT_TYPE;
		}

		/**
		  * @param null|\PhpCliShell\Core\Addon\Service $service
		  * @return void
		  */
		protected function _initialization(Service $service = null)
		{
			/**
			  * Permet de garder la référence du service
			  * actuellement activé pour cette instance d'Api
			  */
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
		  * @return \PhpCliShell\Core\Addon\Resolver
		  */
		abstract protected function _newResolver();

		/**
		  * @return false|string Return getters API classname
		  */
		public function getGettersApiClass()
		{
			return $this->_resolver->resolve(static::RESOLVER_GETTERS_API_NAME);
		}

		/**
		  * @return false|\PhpCliShell\Core\Addon\Api\AbstractGetters Return getters API object
		  */
		public function getGettersApi()
		{
			if($this->_gettersApi === null) {
				$gettersApi = $this->getGettersApiClass();
				$this->_gettersApi = new $gettersApi($this);
			}

			return $this->_gettersApi;
		}

		/**
		  * @return bool
		  */
		public function hasObjectId()
		{
			return ($this->_objectId !== null);
		}

		/**
		  * @return int
		  */
		public function getObjectId()
		{
			return $this->_objectId;
		}

		protected function _setObjectId($objectId)
		{
			if($this->objectIdIsValid($objectId)) {
				$this->_objectId = (int) $objectId;
				$this->objectExists();
			}
			elseif($objectId !== null) {
				throw new Exception("This object ID must be an integer greater to 0, '".gettype($objectId)."' is not valid", E_USER_ERROR);
			}
		}

		abstract protected function _getObject();

		/**
		  * @param $datas array
		  * @return bool
		  */
		public function wakeup(array $datas)
		{
			if(array_key_exists(static::FIELD_ID, $datas) && array_key_exists(static::FIELD_NAME, $datas))
			{
				$objectId = $datas[static::FIELD_ID];
				$objectLabel = $datas[static::FIELD_NAME];

				if($this->objectIdIsValid($objectId)) {
					$this->_objectId = $objectId;
					$this->_objectLabel = $objectLabel;
					$this->_objectDatas = $datas;
					$this->_objectExists = true;
					return true;
				}
			}

			return false;
		}

		/**
		  * @return bool
		  */
		public function isOnline()
		{
			$this->_objectDatas = null;
			$this->_objectExists = null;
			return $this->objectExists();
		}

		/**
		  * @param string $field
		  * @param null|string $validator
		  * @param false|string $cast
		  * @return false|mixed
		  */
		protected function _getField($field, $validator = null, $cast = false)
		{
			if($this->objectExists())
			{
				$object = $this->_getObject();

				if($object !== false && array_key_exists($field, $object) &&
					($validator === null || C\Tools::is($validator, $object[$field])))
				{
					switch($cast)
					{
						case 'int': {
							return (int) $object[$field];
						}
						case 'string': {
							return (string) $object[$field];
						}
						case 'bool': {
							return (bool) $object[$field];
						}
						default: {
							return $object[$field];
						}
					}
				}
			}

			return false;
		}

		/**
		  * @return $this
		  */
		public function refresh()
		{
			$objectId = $this->_objectId;
			$this->_softReset(true);
			$this->_setObjectId($objectId);
			return $this;
		}

		/**
		  * @param bool $resetObjectId
		  * @return void
		  */
		protected function _softReset($resetObjectId = false)
		{
			if($resetObjectId) {
				$this->_objectId = null;
			}

			$this->_objectExists = null;
			$this->_objectLabel = null;
			$this->_objectDatas = null;
		}

		/**
		  * @return $this
		  */
		public function reset()
		{
			$objectId = $this->_objectId;
			$this->_hardReset(true);
			$this->_setObjectId($objectId);
			return $this;
		}

		/**
		  * @param bool $resetObjectId
		  * @return void
		  */
		protected function _hardReset($resetObjectId = false)
		{
			$this->_softReset($resetObjectId);
		}

		/**
		  * @param null|string $type Object type
		  * @return false|\PhpCliShell\Core\Addon\Service\CacheContainer
		  */
		protected function _getCacheContainer($type = null)
		{
			$cache = $this->_service->cache;

			if($cache !== false)
			{
				if($type === null) {
					$type = static::OBJECT_TYPE;
				}

				if($cache->isReady($type)) {
					return $cache->getContainer($type);
				}
			}

			return false;
		}

		/**
		  * @return $this
		  */
		protected function _registerToStore()
		{
			if($this->objectExists())
			{
				$store = $this->_service->store;
				$objectId = $this->getObjectId();

				if($store !== false && $store->isReady(static::OBJECT_TYPE) && !isset($store[$objectId])) {
					$store->getContainer(static::OBJECT_TYPE)->assign($this);
				}
			}

			return $this;
		}

		/**
		  * @return $this
		  */
		protected function _unregisterFromStore()
		{
			if($this->objectExists())
			{
				$store = $this->_service->store;
				$objectId = $this->getObjectId();

				if($store !== false && $store->isReady(static::OBJECT_TYPE) && isset($store[$objectId])) {
					$store->getContainer(static::OBJECT_TYPE)->unassign($this);
				}
			}

			return $this;
		}

		public function hasErrorMessage()
		{
			return ($this->_errorMessage !== null);
		}

		public function getErrorMessage()
		{
			return $this->_errorMessage;
		}

		protected function _setErrorMessage($message)
		{
			$this->_errorMessage = $message;
		}

		protected function _resetErrorMessage()
		{
			return $this->_errorMessage = null;
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
				case 'getters': 
				case 'gettersApi': {
					return $this->getGettersApi();
				}
				case 'id': {
					return $this->getObjectId();
				}
				case 'name':
				case 'label': {
					return $this->getObjectLabel();
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
				$attribute = substr($name, 3);
				$attribute = mb_strtolower($attribute);

				switch($attribute)
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
					case 'id': {
						return $this->getObjectId();
					}
					case 'name':
					case 'label': {
						return $this->getObjectLabel();
					}
				}
			}

			throw new Exception("This method '".$name."' does not exist", E_USER_ERROR);
		}

		public static function __callStatic($name, array $arguments)
		{
			if(substr($name, 0, 3) === 'get')
			{
				$attribute = substr($name, 3);
				$attribute = mb_strtolower($attribute);

				switch($attribute)
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
	}