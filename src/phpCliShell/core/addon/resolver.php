<?php
	namespace PhpCliShell\Core\Addon;

	use PhpCliShell\Core as C;

	abstract class Resolver implements \Iterator, \ArrayAccess, \Countable
	{
		/**
		  * @var array
		  */
		protected $_namespaces = array();

		/**
		  * @var array
		  */
		protected $_zone = array();

		/**
		  * @var bool
		  */
		protected $_debug = false;


		/**
		  * @param null|string $namespace
		  * @return $this
		  */
		public function __construct($namespace = null)
		{
			if($namespace === null) {
				$namespace = $this->_getNamespace();
			}
			
			$this->addNamespace($namespace);
		}

		/**
		  * @return string
		  */
		abstract protected function _getNamespace();

		/**
		  * @param string $namespace
		  * @return $this
		  */
		public function setNamespace($namespace)
		{
			$this->_namespaces = array();
			$this->addNamespace($namespace);
			return $this;
		}

		/**
		  * @param array $namespaces
		  * @return $this
		  */
		public function setNamespaces(array $namespaces)
		{
			$this->_namespaces = array();
			$this->addNamespaces($namespaces);
			return $this;
		}

		/**
		  * @param string $namespace
		  * @param bool $unshift
		  * @return $this
		  */
		public function addNamespace($namespace, $unshift = false)
		{
			if(C\Tools::is('string&&!empty', $namespace) && !in_array($namespace, $this->_namespaces, true))
			{
				if($unshift) {
					array_unshift($this->_namespaces, $namespace);
				}
				else {
					$this->_namespaces[] = $namespace;
				}

				$this->_zone = array();
			}

			return $this;
		}

		/**
		  * @param array $namespaces
		  * @param bool $unshift
		  * @return $this
		  */
		public function addNamespaces(array $namespaces, $unshift = false)
		{
			$namespaces = array_diff($namespaces, $this->_namespaces);

			$namespaces = array_filter($namespaces, function($namespace) {
				return C\Tools::is('string&&!empty', $namespace);
			});

			if($unshift) {
				$this->_namespaces = array_merge($namespaces, $this->_namespaces);
			}
			else {
				$this->_namespaces = array_merge($this->_namespaces, $namespaces);
			}

			$this->_zone = array();
			return $this;
		}

		/**
		  * @param string $class
		  * @return false|string
		  */
		public function resolve($class)
		{
			if(!array_key_exists($class, $this->_zone))
			{
				if(substr($class, 0, 1) !== '\\')
				{
					foreach($this->_namespaces as $namespace)
					{
						$nsClass = $namespace.'\\'.$class;

						if(class_exists($nsClass, true)) {
							$this->_zone[$class] = $nsClass;
							break;
						}
					}

					if(!array_key_exists($class, $this->_zone)) {
						$this->_zone[$class] = false;
					}
				}
				else {
					$this->_zone[$class] = $class;
				}
			}

			return $this->_zone[$class];
		}

		public function rewind()
		{
			return reset($this->_namespaces);
		}

		public function current()
		{
			return current($this->_namespaces);
		}

		public function key()
		{
			return key($this->_namespaces);
		}

		public function next()
		{
			return next($this->_namespaces);
		}

		public function valid()
		{
			return (key($this->_namespaces) !== null);
		}

		public function offsetSet($offset, $value)
		{
		}

		public function offsetExists($offset)
		{
			return array_key_exists($offset, $this->_namespaces);
		}

		public function offsetUnset($offset)
		{
		}

		public function offsetGet($offset)
		{
			if($this->offsetExists($offset)) {
				return $this->_namespaces[$offset];
			}
			else {
				return null;
			}
		}

		public function count()
		{
			return count($this->_namespaces);
		}

		/**
		  * @param string $name
		  * @return string
		  */
		public function __get($name)
		{
			return $this->resolve($name);
		}

		/**
		  * @param bool $debug
		  * @return $this
		  */
		public function debug($debug = true)
		{
			$this->_debug = (bool) $debug;
			return $this;
		}
	}