<?php
	namespace PhpCliShell\Core\Addon;

	class Resolvers implements \Iterator, \ArrayAccess, \Countable
	{
		/**
		  * @var array
		  */
		protected $_resolvers = array();

		/**
		  * @var bool
		  */
		protected $_debug = false;


		/**
		  * @param string $key
		  * @param string $namespace
		  * @return false|\PhpCliShell\Core\Addon\Resolver
		  */
		public function factory($key, $namespace)
		{
			if(!$this->key_exists($key)) {
				$resolver = $this->_newResolver($namespace);
				$this->_resolvers[$key] = $resolver;
				$resolver->debug($this->_debug);
				return $resolver;
			}
			else {
				return false;
			}
		}

		/**
		  * @param null|string $namespace Namespace
		  * @return \PhpCliShell\Core\Addon\Resolver
		  */
		protected function _newResolver($namespace = null)
		{
			$namespace = __NAMESPACE__ .'\\'.$namespace;
			return new Resolver(rtrim($namespace, '\\'));
		}

		/**
		  * @param string $key
		  * @return false|\PhpCliShell\Core\Addon\Resolver
		  */
		public function get($key)
		{
			return ($this->key_exists($key)) ? ($this->_resolvers[$key]) : (false);
		}

		public function keys()
		{
			return array_keys($this->_resolvers);
		}

		public function key_exists($key)
		{
			return array_key_exists($key, $this->_resolvers);
		}

		public function rewind()
		{
			return reset($this->_resolvers);
		}

		public function current()
		{
			return current($this->_resolvers);
		}

		public function key()
		{
			return key($this->_resolvers);
		}

		public function next()
		{
			return next($this->_resolvers);
		}

		public function valid()
		{
			return (key($this->_resolvers) !== null);
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
			unset($this->_resolvers[$offset]);
		}

		public function offsetGet($offset)
		{
			if($this->offsetExists($offset)) {
				return $this->_resolvers[$offset];
			}
			else {
				return null;
			}
		}

		public function count()
		{
			return count($this->_resolvers);
		}

		public function __isset($name)
		{
			return $this->key_exists($name);
		}

		public function __unset($name)
		{
			unset($this->_resolvers[$offset]);
		}

		public function __get($name)
		{
			if($this->key_exists($name)) {
				return $this->_resolvers[$name];
			}
			else {
				throw new Exception("Resolver name '".$name."' does not exist", E_USER_ERROR);
			}
		}

		/**
		  * @param bool $debug
		  * @return $this
		  */
		public function debug($debug = true)
		{
			$this->_debug = (bool) $debug;

			foreach($this->_resolvers as $resolver) {
				$resolver->debug($this->_debug);
			}

			return $this;
		}
	}