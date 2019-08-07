<?php
	namespace PhpCliShell\Core;

	class ArrayCollection implements \Iterator, \ArrayAccess, \Countable
	{
		/**
		  * @var string
		  */
		protected static $_itemClassName = ArrayItem::class;

		/**
		  * @var \PhpCliShell\Core\ArrayObject
		  */
		protected $_arrayObject = null;

		/**
		  * @var \PhpCliShell\Core\ArrayItem[]
		  */
		protected $_arrayItems = array();


		/**
		  * @param \PhpCliShell\Core\ArrayObject $objects
		  * @return $this
		  */
		public function __construct(ArrayObject $objects)
		{
			$this->_arrayObject = $objects;
			$this->_format($this->_arrayObject);
		}

		protected function _format(ArrayObject $objects)
		{
			foreach($objects as $name => $object)
			{
				if(is_object($object)) {
					$this->_arrayItems[$name] = new static::$_itemClassName($name, $object);
				}
				else {
					$this->_arrayItems[$name] = $object;
				}
			}
		}

		public function keys()
		{
			return array_keys($this->_arrayItems);
		}

		public function key_exists($key)
		{
			return array_key_exists($key, $this->_arrayItems);
		}

		public function rewind()
		{
			return reset($this->_arrayItems);
		}

		public function current()
		{
			return current($this->_arrayItems);
		}

		public function key()
		{
			return key($this->_arrayItems);
		}

		public function next()
		{
			return next($this->_arrayItems);
		}

		public function valid()
		{
			return (key($this->_arrayItems) !== null);
		}

		public function offsetSet($offset, $value)
		{
		}

		public function offsetExists($offset)
		{
			return isset($this->{$offset});
		}

		public function offsetUnset($offset)
		{
		}

		public function offsetGet($offset)
		{
			if($this->offsetExists($offset)) {
				return $this->{$offset};
			}
			else {
				return null;
			}
		}

		public function count()
		{
			return count($this->_arrayItems);
		}

		public function __isset($name)
		{
			return $this->key_exists($name);
		}

		public function __get($name)
		{
			if(isset($this->{$name})) {
				return $this->_arrayItems[$name];
			}
			else {
				throw new Exception("Attribute name '".$name."' does not exist", E_USER_ERROR);
			}
		}
	}