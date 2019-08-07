<?php
	namespace PhpCliShell\Core\Addon\Service;

	use PhpCliShell\Core as C;

	use PhpCliShell\Core\Addon;
	use PhpCliShell\Core\Addon\Exception;

	abstract class Plugin implements \Iterator, \ArrayAccess, \Countable
	{
		const PLUGIN_TYPE = 'unknown';
		const PLUGIN_NAME = 'noname';

		/**
		  * @var \PhpCliShell\Core\Addon\Service
		  */
		protected $_service = null;

		/**
		  * Plugin state (enable or disable)
		  * @var bool
		  */
		protected $_state = true;

		/**
		  * @var bool
		  */
		protected $_isReady = false;

		/**
		  * @var \PhpCliShell\Core\Addon\Service\Container[]
		  */
		protected $_containers = array();

		/**
		  * @var bool
		  */
		protected $_debug = false;


		/**
		  * @param null|\PhpCliShell\Core\Addon\Service $service
		  * @param bool $state Turn on or off plugin (default is enabled)
		  * @param bool $autoStart Initialization automatic or not (default is disabled)
		  * @return $this
		  */
		public function __construct(Addon\Service $service = null, $state = true, $autoStart = false)
		{
			$this->_service = $service;
			$this->_state($state);

			if($autoStart) {
				$this->initialization();
			}
		}

		/**
		  * @return \PhpCliShell\Core\Addon\Service
		  */
		public function hasService()
		{
			return ($this->_service !== null);
		}

		/**
		  * @param \PhpCliShell\Core\Addon\Service $service
		  * @return bool
		  */
		public function setService(Addon\Service $service)
		{
			if(!$this->_isReady) {
				$this->_service = $service;
				return true;
			}
			else {
				return false;
			}
		}

		/**
		  * @return \PhpCliShell\Core\Addon\Service
		  */
		public function getService()
		{
			return $this->_service;
		}

		/**
		  * @return void
		  */
		protected function _start()
		{
			if($this->isEnabled() && $this->hasService() && !$this->isReady())
			{
				$this->_isReady = $this->_initialization();

				if(!$this->_isReady) {
					$this->_stop();
				}
			}

			return $this->isReady();
		}

		/**
		  * @return bool Return initialization status
		  */
		abstract protected function _initialization();

		/**
		  * @return bool Return initialization status
		  */
		public function initialization()
		{
			return $this->_start();
		}

		/**
		  * @return void
		  */
		protected function _stop()
		{
			$this->_state = false;
			$this->_isReady = false;
			$this->_containers = array();
		}

		/**
		  * @param bool $state Turn on or off plugin
		  * @return bool Return plugin state (enabled or not)
		  */
		public function state($state = null)
		{
			$this->_state($state);
			return $this->isEnabled();
		}

		/**
		  * @param bool $state Turn on or off plugin
		  * @return bool Return plugin status (ready or not)
		  */
		public function setState($state)
		{
			$this->_state($state);
			return $this->isReady();
		}

		/**
		  * @param bool $state Turn on or off plugin
		  * @return void
		  */
		protected function _state($state)
		{
			if(C\Tools::is('bool', $state)) {
				$this->_state = $state;
			}

			($this->_state) ? ($this->_start()) : ($this->_stop());
		}

		/**
		  * @return bool
		  */
		public function enable()
		{
			return $this->state(true);
		}

		/**
		  * @return bool
		  */
		public function disable()
		{
			return $this->state(false);
		}

		/**
		  * @return bool
		  */
		public function getState()
		{
			return $this->_state;
		}

		/**
		  * @return bool
		  */
		public function isEnabled()
		{
			return ($this->_state === true);
		}

		/**
		  * @return bool
		  */
		public function isDisabled()
		{
			return ($this->_state === false);
		}

		/**
		  * @param null|string $type
		  * @return bool Return status
		  */
		public function isReady($type = null)
		{
			if($type === null) {
				return $this->_isReady;
			}
			else {
				return $this->getContainerState($type);
			}
		}

		/**
		  * @return bool
		  */
		public function getPluginState()
		{
			return $this->getState();
		}

		/**
		  * @param string $type
		  * @return bool
		  */
		public function getContainerState($type)
		{
			return ($this->getPluginState() && $this->hasContainer($type));
		}

		/**
		  * @param string $type
		  * @return bool
		  */
		public function hasContainer($type)
		{
			return $this->key_exists($type);
		}

		/**
		  * @param string $type
		  * @return false|\PhpCliShell\Core\Addon\Service\Container
		  */
		public function newContainer($type)
		{
			if($this->isEnabled() && $this->hasService())
			{
				if(!$this->hasContainer($type)) {
					$container = $this->_newContainer($type);
					$this->_containers[$type] = $container;
					$container->debug($this->_debug);
				}

				return $this->_containers[$type];
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $type
		  * @return \PhpCliShell\Core\Addon\Service\Container
		  */
		abstract protected function _newContainer($type);

		/**
		  * @param string $type
		  * @param bool $autoCreate
		  * @return false|\PhpCliShell\Core\Addon\Service\Container
		  */
		public function getContainer($type, $autoCreate = false)
		{
			if($autoCreate) {
				return $this->newContainer($type);
			}
			elseif($this->hasContainer($type)) {
				return $this->_containers[$type];
			}
			else {
				return false;
			}
		}

		public function keys()
		{
			return array_keys($this->_containers);
		}

		public function key_exists($key)
		{
			return array_key_exists($key, $this->_containers);
		}

		public function rewind()
		{
			return reset($this->_containers);
		}

		public function current()
		{
			return current($this->_containers);
		}

		public function key()
		{
			return key($this->_containers);
		}

		public function next()
		{
			return next($this->_containers);
		}

		public function valid()
		{
			return (key($this->_containers) !== null);
		}

		public function offsetSet($offset, $value)
		{
		}

		public function offsetExists($offset)
		{
			return $this->key_exists($offset);
		}

		public function offsetUnset($offset)
		{
		}

		public function offsetGet($offset)
		{
			if($this->offsetExists($offset)) {
				return $this->_containers[$offset];
			}
			else {
				return null;
			}
		}

		public function count()
		{
			return count($this->_containers);
		}

		/**
		  * @param string $type
		  * @return bool
		  */
		public function cleaner($type)
		{
			if($this->hasContainer($type)) {
				$this->_containers[$type]->reset();
				return true;
			}
			else {
				return false;
			}
		}
	
		/**
		  * @param string $type
		  * @return bool
		  */
		public function erase($type)
		{
			unset($this->_containers[$type]);
			return true;
		}
	
		/**
		  * @return bool
		  */
		public function reset()
		{
			$this->_isReady = false;
			$this->_containers = array();
			return $this->initialization();
		}

		/**
		  * @param string $name
		  * @return bool
		  */
		public function __isset($name)
		{
			return $this->key_exists($name);
		}

		/**
		  * @param string $name
		  * @return void
		  */
		public function __unset($name)
		{
			$this->erase($name);
		}

		/**
		  * @param mixed $name
		  * @return mixed
		  */
		public function __get($name)
		{
			if($name === 'service') {
				return $this->getService();
			}
			else
			{
				$container = $this->getContainer($name);

				if($container !== false) {
					return $container;
				}
				else {
					throw new Exception("Unable to retrieve ".static::PLUGIN_NAME." container '".$name."'", E_USER_ERROR);
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

			foreach($this->_containers as $container) {
				$container->debug($this->_debug);
			}

			return $this;
		}
	}