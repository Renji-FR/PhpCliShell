<?php
	namespace PhpCliShell\Core\Addon;

	use PhpCliShell\Core as C;

	abstract class Service
	{
		/**
		  * @var string
		  */
		const SERVICE_NAME = 'unknown';

		/**
		  * @var string
		  */
		const URL_CONFIG_FIELD = 'url';

		/**
		  * @var string
		  */
		const TOKEN_CONFIG_FIELD = 'tokenCredential';

		/**
		  * @var string
		  */
		const TOKEN_ENV_CONFIG_FIELD = 'tokenEnvVarName';

		/**
		  * @var string
		  */
		const LOGIN_CONFIG_FIELD = 'loginCredential';

		/**
		  * @var string
		  */
		const LOGIN_ENV_CONFIG_FIELD = 'loginEnvVarName';

		/**
		  * @var string
		  */
		const PASSWORD_CONFIG_FIELD = 'passwordCredential';

		/**
		  * @var string
		  */
		const PASSWORD_ENV_CONFIG_FIELD = 'passwordEnvVarName';

		/**
		  * @var string
		  */
		protected $_id = null;

		/**
		  * @var \PhpCliShell\Core\Config
		  */
		protected $_config = null;

		/**
		  * @var \PhpCliShell\Core\Addon\Service\Cache
		  */
		protected $_cache = null;

		/**
		  * @var \PhpCliShell\Core\Addon\Service\Store
		  */
		protected $_store = null;

		/**
		  * @var \PhpCliShell\Core\Addon\Adapter
		  */
		protected $_adapter = null;

		/**
		  * @var \PhpCliShell\Core\Addon\Resolvers
		  */
		protected $_resolvers = null;

		/**
		  * @var bool
		  */
		protected $_isReady = false;

		/**
		  * @var bool
		  */
		protected $_debug = false;


		/**
		  * @param string $id
		  * @param \PhpCliShell\Core\Config $config
		  * @return $this
		  */
		public function __construct($id, C\Config $config = null)
		{
			$this->_id = $id;
			$this->_config = $config;
		}

		/**
		  * @return string
		  */
		public function getId()
		{
			return $this->_id;
		}

		/**
		  * @return bool
		  */
		public function hasConfig()
		{
			return ($this->_config !== null);
		}

		/**
		  * @param \PhpCliShell\Core\Config $config
		  * @return bool
		  */
		public function setConfig(C\Config $config)
		{
			if(!$this->_isReady) {
				$this->_config = $config;
				return true;
			}
			else {
				return false;
			}
		}

		/**
		  * @return false|\PhpCliShell\Core\Config
		  */
		public function getConfig()
		{
			return ($this->hasConfig()) ? ($this->_config) : (false);
		}

		/**
		  * @return bool
		  */
		public function hasServiceConfig()
		{
			return isset($this->_config[$this->_id]);
		}

		/**
		  * @return false|\PhpCliShell\Core\Config
		  */
		public function getServiceConfig()
		{
			return $this->_getServiceConfig(false);
		}

		/**
		  * @param string $default
		  * @return mixed|\PhpCliShell\Core\Config
		  */
		protected function _getServiceConfig($default = null)
		{
			return ($this->hasServiceConfig()) ? ($this->_config[$this->_id]) : ($default);
		}

		/**
		  * @return bool
		  */
		public function initialization()
		{
			if(!$this->_isReady) {
				$this->_initCache();
				$this->_initStore();
				$this->_initAdapter();
				$this->_initResolvers();
				$this->_isReady = true;
			}

			return true;
		}

		/**
		  * @return bool
		  */
		public function isReady()
		{
			return $this->_isReady;
		}

		/**
		  * @return bool
		  */
		protected function _initCache()
		{
			if($this->_cache === null) {
				$this->_cache = $this->_newCache();
			}
			
			$this->_cache->debug($this->_debug);
			return $this->_cache->initialization();
		}

		/**
		  * @return \PhpCliShell\Core\Addon\Service\Cache
		  */
		abstract protected function _newCache();

		/**
		  * @param \PhpCliShell\Core\Addon\Service\Cache $cache
		  * @return bool
		  */
		protected function setCache(Service\Cache $cache)
		{
			if(!$this->_isReady) {
				$this->_cache = $cache;
				return true;
			}
			else {
				return false;
			}
		}

		/**
		  * @return bool
		  */
		protected function _initStore()
		{
			if($this->_store === null) {
				$this->_store = $this->_newStore();
			}

			$this->_store->debug($this->_debug);
			return $this->_store->initialization();
		}

		/**
		  * @return \PhpCliShell\Core\Addon\Service\Store
		  */
		abstract protected function _newStore();

		/**
		  * @param \PhpCliShell\Core\Addon\Service\Store $store
		  * @return bool
		  */
		protected function setStore(Service\Store $store)
		{
			if(!$this->_isReady) {
				$this->_store = $store;
				return true;
			}
			else {
				return false;
			}
		}

		/**
		  * @return void
		  */
		protected function _initAdapter()
		{
			$config = $this->_getServiceConfig(null);

			/**
			  * Si il n'y a pas d'objet Config déclaré
			  * OU
			  * Si il y en a un ET qu'on peut récupérer la config
			  */
			if($this->_config === null || $config !== null) {
				$this->_adapter = $this->_newAdapter($config);
				$this->_adapter->debug($this->_debug);
			}
			else {
				throw new Exception("Unable to retrieve ".static::SERVICE_NAME." service '".$this->_id."' configuration", E_USER_ERROR);
			}
		}

		/**
		  * @param null|\PhpCliShell\Core\Config $config
		  * @return \PhpCliShell\Core\Addon\Adapter
		  */
		abstract protected function _newAdapter(C\Config $config = null);

		/**
		  * @return void
		  */
		protected function _initResolvers()
		{
			if($this->_resolvers === null) {
				$this->_resolvers = $this->_newResolvers();
			}

			$this->_resolvers->debug($this->_debug);

			$this->_resolvers->factory('addon', null);
			$this->_resolvers->factory('api', 'api');
		}

		/**
		  * @return \PhpCliShell\Core\Addon\Resolvers
		  */
		abstract protected function _newResolvers();

		/**
		  * @param \PhpCliShell\Core\Addon\Resolvers $resolvers
		  * @return bool
		  */
		protected function setResolvers(Service\Resolvers $resolvers)
		{
			if(!$this->_isReady) {
				$this->_resolvers = $resolvers;
				return true;
			}
			else {
				return false;
			}
		}

		protected function _getUrl(C\Config $serviceConfig, $id)
		{
			if($serviceConfig->key_exists(static::URL_CONFIG_FIELD)) {
				return $serviceConfig[static::URL_CONFIG_FIELD];
			}
			else {
				throw new Exception("Unable to retrieve '".static::URL_CONFIG_FIELD."' configuration for ".static::SERVICE_NAME." service '".$id."'", E_USER_ERROR);
			}
		}

		protected function _getToken(C\Config $serviceConfig, $id, $throwException = true)
		{
			if($serviceConfig->key_exists(static::TOKEN_CONFIG_FIELD) && C\Tools::is('string&&!empty', $serviceConfig[static::TOKEN_CONFIG_FIELD])) {
				return $serviceConfig[static::TOKEN_CONFIG_FIELD];
			}
			elseif($serviceConfig->key_exists(static::TOKEN_ENV_CONFIG_FIELD) && C\Tools::is('string&&!empty', $serviceConfig[static::TOKEN_ENV_CONFIG_FIELD]))
			{
				$tokenEnvVarName = $serviceConfig[static::TOKEN_ENV_CONFIG_FIELD];
				$tokenCredential = getenv($tokenEnvVarName);

				if($tokenCredential !== false) {
					return $tokenCredential;
				}
				elseif($throwException) {
					throw new Exception("Unable to retrieve token credential for ".static::SERVICE_NAME." service '".$id."' from environment with variable name '".$tokenEnvVarName."'", E_USER_ERROR);
				}
			}
			elseif($throwException) {
				throw new Exception("Unable to retrieve '".static::TOKEN_CONFIG_FIELD."' or '".static::TOKEN_ENV_CONFIG_FIELD."' configuration for ".static::SERVICE_NAME." service '".$id."'", E_USER_ERROR);
			}

			return false;
		}

		protected function _getCredentials(C\Config $serviceConfig, $id, $throwException = true)
		{
			if($serviceConfig->key_exists(static::LOGIN_CONFIG_FIELD) && C\Tools::is('string&&!empty', $serviceConfig[static::LOGIN_CONFIG_FIELD])) {
				$loginCredential = $serviceConfig[static::LOGIN_CONFIG_FIELD];
			}
			elseif($serviceConfig->key_exists(static::LOGIN_ENV_CONFIG_FIELD) && C\Tools::is('string&&!empty', $serviceConfig[static::LOGIN_ENV_CONFIG_FIELD]))
			{
				$loginEnvVarName = $serviceConfig[static::LOGIN_ENV_CONFIG_FIELD];
				$loginCredential = getenv($loginEnvVarName);

				if($loginCredential === false && $throwException) {
					throw new Exception("Unable to retrieve login credential for ".static::SERVICE_NAME." service '".$id."' from environment with variable name '".$loginEnvVarName."'", E_USER_ERROR);
				}
			}
			elseif($throwException) {
				throw new Exception("Unable to retrieve '".static::LOGIN_CONFIG_FIELD."' or '".static::LOGIN_ENV_CONFIG_FIELD."' configuration for ".static::SERVICE_NAME." service '".$id."'", E_USER_ERROR);
			}
			else {
				$loginCredential = false;
			}

			if($serviceConfig->key_exists(static::PASSWORD_CONFIG_FIELD) && C\Tools::is('string&&!empty', $serviceConfig[static::PASSWORD_CONFIG_FIELD])) {
				$passwordCredential = $serviceConfig[static::PASSWORD_CONFIG_FIELD];
			}
			elseif($serviceConfig->key_exists(static::PASSWORD_ENV_CONFIG_FIELD) && C\Tools::is('string&&!empty', $serviceConfig[static::PASSWORD_ENV_CONFIG_FIELD]))
			{
				$passwordEnvVarName = $serviceConfig[static::PASSWORD_ENV_CONFIG_FIELD];
				$passwordCredential = getenv($passwordEnvVarName);

				if($passwordCredential === false && $throwException) {
					throw new Exception("Unable to retrieve password credential for ".static::SERVICE_NAME." service '".$id."' from environment with variable name '".$passwordEnvVarName."'", E_USER_ERROR);
				}
			}
			elseif($throwException) {
				throw new Exception("Unable to retrieve '".static::PASSWORD_CONFIG_FIELD."' or '".static::PASSWORD_ENV_CONFIG_FIELD."' configuration for ".static::SERVICE_NAME." service '".$id."'", E_USER_ERROR);
			}
			else {
				$passwordCredential = false;
			}

			return array($loginCredential, $passwordCredential);
		}

		public function hasCache()
		{
			return ($this->_cache !== null);
		}

		/**
		  * @return false|\PhpCliShell\Core\Addon\Service\Cache
		  */
		public function getCache()
		{
			return ($this->hasCache()) ? ($this->_cache) : (false);
		}

		public function hasStore()
		{
			return ($this->_store !== null);
		}

		/**
		  * @return false|\PhpCliShell\Core\Addon\Service\Store
		  */
		public function getStore()
		{
			return ($this->hasStore()) ? ($this->_store) : (false);
		}

		public function hasAdapter()
		{
			return ($this->_adapter !== null);
		}

		/**
		  * @return false|\PhpCliShell\Core\Addon\Adapter
		  */
		public function getAdapter()
		{
			return ($this->hasAdapter()) ? ($this->_adapter) : (false);
		}

		public function hasResolvers()
		{
			return ($this->_resolvers !== null);
		}

		/**
		  * @return false|\PhpCliShell\Core\Addon\Resolvers
		  */
		public function getResolvers()
		{
			return ($this->hasResolvers()) ? ($this->_resolvers) : (false);
		}

		public function hasResolver($name)
		{
			return $this->_resolvers->key_exists($name);
		}

		/**
		  * @param string $name Resolver name
		  * @return false|\PhpCliShell\Core\Addon\Resolver
		  */
		public function getResolver($name)
		{
			return $this->_resolvers->get($name);
		}

		/**
		  * @return false|string
		  */
		public function getAdapterClass()
		{
			if(($adapter = $this->getAdapter()) !== false) {
				return get_class($adapter);
			}
			else {
				return false;
			}
		}

		/**
		  * @return string
		  */
		public function getAdapterLabel()
		{
			if(($adapter = $this->getAdapterClass()) !== false) {
				return (substr($adapter, strrpos($adapter, '\\') + 1));
			}
			else {
				return 'UNKNOWN';
			}
		}

		/**
		  * @return string
		  */
		abstract public function getAdapterMethod();

		public function __isset($name)
		{
			switch($name)
			{
				case 'config': {
					return $this->hasConfig();
				}
				case 'serviceConfig': {
					return $this->hasServiceConfig();
				}
				case 'cache': {
					return $this->hasCache();
				}
				case 'store': {
					return $this->hasStore();
				}
				case 'adapter': {
					return $this->hasAdapter();
				}
				case 'resolver':		// Magic: isset($service->resolver->name)
				case 'resolvers': {
					return $this->hasResolvers();
				}
				default: {
					return false;
				}
			}
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'id': {
					return $this->getId();
				}
				case 'isReady': {
					return $this->isReady();
				}
				case 'config': {
					return $this->getConfig();
				}
				case 'serviceConfig': {
					return $this->getServiceConfig();
				}
				case 'cache': {
					return $this->getCache();
				}
				case 'store': {
					return $this->getStore();
				}
				case 'adapter': {
					return $this->getAdapter();
				}
				case 'resolver':		// Magic: $resolver = $service->resolver->name
				case 'resolvers': {
					return $this->getResolvers();
				}
				default: {
					throw new Exception("This attribute '".$name."' does not exist", E_USER_ERROR);
				}
			}
		}

		/**
		  * @param bool $debug
		  * @return $this
		  */
		public function debug($debug = true)
		{
			$this->_debug = (bool) $debug;

			if($this->_isReady) {
				$this->_cache->debug($this->_debug);
				$this->_store->debug($this->_debug);
				$this->_adapter->debug($this->_debug);
				$this->_resolvers->debug($this->_debug);
			}

			return $this;
		}
	}