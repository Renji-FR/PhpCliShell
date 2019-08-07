<?php
	namespace PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Service;
	use PhpCliShell\Addon\Ipam\Common\Connector;

	abstract class Service extends C\Addon\Service
	{
		/**
		  * @var string
		  */
		const SERVICE_TYPE = 'ipam';

		/**
		  * @var string
		  */
		const SERVICE_NAME = 'IPAM';

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
		  * @return string
		  */
		public function getAdapterLabel()
		{
			if(($adapter = $this->getAdapterClass()) !== false) {
				return $adapter::LABEL;
			}
			else {
				return 'UNKNOWN';
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
		  * @return \PhpCliShell\Addon\Ipam\Common\Service\Cache
		  */
		protected function _newCache()
		{
			return new Service\Cache($this, false);
		}

		/**
		  * @return \PhpCliShell\Addon\Ipam\Common\Service\Store
		  */
		protected function _newStore()
		{
			return new Service\Store($this, true);
		}

		/**
		  * @return void
		  */
		protected function _initAdapter()
		{
			if(($config = $this->_getServiceConfig(false)) !== false) {
				$this->_adapter = $this->_newAdapter($config);
				$this->_adapter->debug($this->_debug);
			}
			else {
				throw new Exception("Unable to retrieve ".static::SERVICE_NAME." service '".$this->_id."' configuration", E_USER_ERROR);
			}
		}

		/**
		  * @return \PhpCliShell\Addon\Ipam\Common\Resolvers
		  */
		protected function _newResolvers()
		{
			return new Resolvers();
		}
	}