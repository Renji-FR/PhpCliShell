<?php
	namespace PhpCliShell\Addon\Dcim;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Dcim\Connector;

	class Service extends C\Addon\Service
	{
		/**
		  * @var string
		  */
		const SERVICE_TYPE = 'dcim';

		/**
		  * @var string
		  */
		const SERVICE_NAME = 'DCIM';

		/**
		  * @var string
		  */
		const URL_CONFIG_FIELD = 'serverLocation';

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
		  * @return false|string
		  */
		public function getAdapterClass()
		{
			if(($adapter = $this->getAdapter()) !== false) {
				return get_class($adapter);
			}
			else {
				return Connector\Soap::class;
			}
		}

		/**
		  * @return string
		  */
		public function getAdapterMethod()
		{
			if(($adapter = $this->getAdapterClass()) !== false) {
				return $adapter::METHOD;
			}
			else {
				return 'UNKNOWN';
			}
		}

		/**
		  * @return bool
		  */
		public function hasServiceConfig()
		{
			return isset($this->_config['servers'][$this->_id]);
		}

		/**
		  * @param string $default
		  * @return mixed|\PhpCliShell\Core\Config
		  */
		protected function _getServiceConfig($default = null)
		{
			return ($this->hasServiceConfig()) ? ($this->_config['servers'][$this->_id]) : ($default);
		}

		/**
		  * @return \PhpCliShell\Addon\Dcim\Service\Cache
		  */
		protected function _newCache()
		{
			$cache = new Service\Cache($this, false);
			$cache->debug($this->_debug);
			return $cache;
		}

		/**
		  * @return \PhpCliShell\Addon\Dcim\Service\Store
		  */
		protected function _newStore()
		{
			$store = new Service\Store($this, true);
			$store->debug($this->_debug);
			return $store;
		}

		protected function _initAdapter()
		{
			if(($config = $this->_getServiceConfig(false)) !== false) {
				$this->_adapter = $this->_newAdapter($config);
			}
			else {
				throw new Exception("Unable to retrieve ".static::SERVICE_NAME." service '".$this->_id."' configuration", E_USER_ERROR);
			}
		}

		/**
		  * @param null|\PhpCliShell\Core\Config $config
		  * @return \PhpCliShell\Addon\Dcim\Service\Store
		  */
		protected function _newAdapter(C\Config $config = null)
		{
			$serverUrl = $this->_getUrl($config, $this->_id);
			$credentials = $this->_getCredentials($config, $this->_id);
			list($loginCredential, $passwordCredential) = $credentials;

			return new Connector\Soap($this, $this->_config, $serverUrl, $loginCredential, $passwordCredential, $this->_debug);
		}

		/**
		  * @return \PhpCliShell\Addon\Dcim\Resolvers
		  */
		protected function _newResolvers()
		{
			return new Resolvers();
		}
	}