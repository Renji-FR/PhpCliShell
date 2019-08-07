<?php
	namespace PhpCliShell\Addon\Ipam\Netbox;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Netbox\Service;
	use PhpCliShell\Addon\Ipam\Netbox\Connector;

	class Service extends Common\Service
	{
		/**
		  * @var string
		  */
		const SERVICE_TYPE = 'netbox';

		/**
		  * @var string
		  */
		const SERVICE_NAME = 'NetBox';


		/**
		  * @return false|string
		  */
		public function getAdapterClass()
		{
			if(($adapter = $this->getAdapter()) !== false) {
				return get_class($adapter);
			}
			elseif(($config = $this->_getServiceConfig(false)) !== false) {
				return $this->_getAppConnector($config, $this->_id, false);
			}
			else {
				return false;
			}
		}

		/**
		  * @return \PhpCliShell\Addon\Ipam\Netbox\Service\Cache
		  */
		protected function _newCache()
		{
			return new Service\Cache($this, false);
		}

		/**
		  * @return \PhpCliShell\Addon\Ipam\Netbox\Service\Store
		  */
		protected function _newStore()
		{
			return new Service\Store($this, true);
		}

		/**
		  * @param null|\PhpCliShell\Core\Config $config
		  * @return \PhpCliShell\Addon\Ipam\Netbox\Service\Store
		  */
		protected function _newAdapter(C\Config $config = null)
		{
			$serverUrl = $this->_getUrl($config, $this->_id);
			$tokenCredential = $this->_getToken($config, $this->_id);
			$appConnector = $this->_getAppConnector($config, $this->_id);

			if($appConnector !== false) {
				return new $appConnector($this, $this->_config, $serverUrl, $tokenCredential, $this->_debug);
			}
			else {
				throw new Exception("Your ".static::SERVICE_NAME." configuration must be upgraded for service '".$this->_id."', please use wizard to generate new configuration", E_USER_ERROR);
			}
		}

		/**
		  * @param \PhpCliShell\Core\Config $serviceConfig
		  * @param string $id
		  * @param bool $throwException
		  * @return false|string Application connector classname
		  */
		protected function _getAppConnector(C\Config $serviceConfig, $id, $throwException = true)
		{
			if(isset($serviceConfig['appConnector']))
			{
				switch($serviceConfig['appConnector'])
				{
					case 'default_v2.6': {
						return Connector\Rest__default__v2_6::class;
					}
					default: {
						throw new Exception("Configuration 'appConnector' is not valid for ".static::SERVICE_NAME." service '".$id."'", E_USER_ERROR);
					}
				}
			}
			elseif($throwException) {
				throw new Exception("Unable to retrieve 'appConnector' configuration for ".static::SERVICE_NAME." service '".$id."'", E_USER_ERROR);
			}
			else {
				return false;
			}
		}

		/**
		  * @return \PhpCliShell\Addon\Ipam\Netbox\Resolvers
		  */
		protected function _newResolvers()
		{
			return new Resolvers();
		}
	}