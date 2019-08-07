<?php
	namespace PhpCliShell\Core;

	use ArrayObject as PhpArrayObject;

	class ArrayObject implements \Iterator, \ArrayAccess, \Countable
	{
		/**
		  * @var array
		  */
		protected $_datas = null;

		/**
		  * @var array
		  */
		protected $_items = array();


		/**
		  * @param mixed $input
		  * @return $this
		  */
		public function __construct($input = array())
		{
			$this->_setDatas((array) $input);
		}

		/**
		  * @param array $datas
		  * @return $this
		  */
		protected function _setDatas(array $datas)
		{
			$this->_items = array();
			$this->_datas = $datas;
			return $this;
		}

		public function merge($input)
		{
			$input = $this->_toArray($input);
			$input = array_merge($this->_datas, $input);
			$this->_setDatas($input);
			return $this;
		}

		public function merge_recursive($input)
		{
			$input = $this->_toArray($input);
			$input = array_merge_recursive($this->_datas, $input);
			$this->_setDatas($input);
			return $this;
		}

		public function replace($input)
		{
			$input = $this->_toArray($input);
			$input = array_replace($this->_datas, $input);
			$this->_setDatas($input);
			return $this;
		}

		public function replace_recursive($input)
		{
			$input = $this->_toArray($input);
			$input = array_replace_recursive($this->_datas, $input);
			$this->_setDatas($input);
			return $this;
		}

		/**
		  * @param mixed $columnKey
		  * @param null|mixed $indexKey
		  * @param bool $preserveParentKey
		  * @return array
		  */
		public function column($columnKey, $indexKey = null, $preserveParentKey = false)
		{
			$result = array_column($this->_datas, $columnKey, $indexKey);

			if($indexKey === null && $preserveParentKey) {	
				return array_combine(array_keys($this->_datas), $result);
			}
			else {
				return $result;
			}
		}

		public function implode($glue)
		{
			return implode($glue, $this->_datas);
		}

		public function explode($delimiter, $limit = null)
		{
			return explode($delimiter, $this->_datas, $limit);
		}

		public function keys()
		{
			return array_keys($this->_datas);
		}

		public function key_exists($key)
		{
			return array_key_exists($key, $this->_datas);
		}

		public function rewind()
		{
			reset($this->_datas);
			return $this->_format($this->key());
		}

		public function current()
		{
			return $this->_format($this->key());
		}

		public function next()
		{
			next($this->_datas);
			return $this->_format($this->key());
		}

		/**
		  * @return null|int|string
		  */
		public function key()
		{
			return key($this->_datas);
		}

		/**
		  * @return bool
		  */
		public function valid()
		{
			return ($this->key() !== null);
		}

		public function offsetSet($offset, $value)
		{
			if(is_null($offset)) {
				$this->_datas[] = $value;
			} else {
				$this->{$offset} = $value;
			}
		}

		public function offsetExists($offset)
		{
			return isset($this->{$offset});
		}

		public function offsetUnset($offset)
		{
			unset($this->{$offset});
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
			return count($this->_datas);
		}

		/**
		  * @return array
		  */
		public function toArray()
		{
			return $this->_toArray($this->_datas, true);
		}

		/**
		  * @return \ArrayObject
		  */
		public function toObject()
		{
			$array = $this->_toArray($this->_datas, true);
			return new PhpArrayObject($array, PhpArrayObject::ARRAY_AS_PROPS);
		}

		/**
		  * @return array
		  */
		public function getArrayCopy()
		{
			return $this->_datas;
		}

		/**
		  * @param array|\ArrayObject|\PhpCliShell\Core\ArrayObject $input
		  * @param bool $recursive
		  * @return array
		  */
		protected function _toArray($input, $recursive = false)
		{
			if($recursive)
			{
				/**
				  * Ne pas utiliser de référence foreach($input as &$array) sinon:
				  * Uncaught Error: An iterator cannot be used with foreach by reference
				  */
				foreach($input as $key => $array)
				{
					if(is_array($array) || $array instanceof PhpArrayObject || $array instanceof ArrayObject) {
						$input[$key] = $this->_toArray($array, true);
					}
				}
			}

			if(is_array($input)) {
				return $input;
			}
			elseif($input instanceof self) {
				return $input->getArrayCopy();
			}
			elseif($input instanceof PhpArrayObject) {
				return $input->getArrayCopy();
			}

			throw new Exception('Argument 1 passed must be of the type array or ArrayObject', E_USER_ERROR);
		}

		/**
		  * @param array $datas
		  * @return \PhpCliShell\Core\ArrayObject
		  */
		protected function _prepare(array $datas)
		{
			$className = static::class;
			$datas = new ArrayObject();

			foreach($datas as $key => $value)
			{
				if(is_array($value)) {
					$datas[$key] = new $className($value);
				}
				else {
					$datas[$key] = $value;
				}
			}

			return $datas;
		}

		/**
		  * @param null|int|string $key
		  * @return null|mixed|\PhpCliShell\Core\ArrayObject
		  */
		protected function _format($key)
		{
			if($key === null) {
				return null;
			}
			elseif(array_key_exists($key, $this->_items)) {
				return $this->_items[$key];
			}
			else {
				$item = $this->_datas[$key];
			}

			if(is_array($item)) {
				$className = static::class;
				$item = new $className($item);
			}

			$this->_items[$key] = $item;
			return $item;
		}

		public function __isset($name)
		{
			return $this->key_exists($name);
		}

		public function __unset($name)
		{
			unset($this->_datas[$name]);
			unset($this->_items[$name]);
		}

		public function get($name, $default = false)
		{
			if(isset($this->{$name})) {
				return $this->_format($name);
			}
			else {
				return $default;
			}
		}

		public function __get($name)
		{
			if(isset($this->{$name})) {
				return $this->_format($name);
			}
			else {
				throw new Exception("Attribute name '".$name."' does not exist", E_USER_ERROR);
			}
		}

		public function __set($name, $value)
		{
			$this->_datas[$name] = $value;
			unset($this->_items[$name]);
		}

		public function dump()
		{
			return $this->toArray();
		}
	}