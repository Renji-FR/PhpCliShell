<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	use ArrayObject;

	use PhpCliShell\Core as C;

	use PhpCliShell\Application\Firewall\Core\Exception;

	abstract class AbstractApi implements \IteratorAggregate, \ArrayAccess, \Countable, InterfaceApi
	{
		/**
		  * @var string
		  */
		const API_TYPE = 'unknown';

		/**
		  * @var string
		  */
		const API_INDEX = 'unknown';

		/**
		  * @var string
		  */
		const API_LABEL = 'unknown';

		/**
		  * @var string
		  */
		const FIELD_ID = '_id_';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var array
		  */
		const FIELD_ATTRS = array();

		/**
		  * @var array
		  */
		protected $_datas = array(
			'type' => null,			// /!\ Réservé pour le type de l'objet, voir toArray()
			'_id_' => null,			// /!\ Réservé pour l'identifiant de l'objet
			'name' => null,			// /!\ Réservé pour le nom de l'objet, voir toArray()
		);


		/**
		  * @param string $id ID
		  * @param string $name Name
		  * @return $this
		  */
		public function __construct($id = null, $name = null)
		{
			$this->id($id);
			$this->name($name);
		}

		/**
		  * Sets id
		  *
		  * @param string $name
		  * @return bool
		  */
		public function id($id)
		{
			if(C\Tools::is('string&&!empty', $id) || C\Tools::is('int&&>0', $id)) {
				$this->_datas[static::FIELD_ID] = mb_strtolower($id);		// auto cast to string
				return true;
			}

			return false;
		}

		/**
		  * Sets name
		  *
		  * @param string $name
		  * @return bool
		  */
		public function name($name)
		{
			if(C\Tools::is('string&&!empty', $name) || C\Tools::is('int&&>=0', $name)) {
				$this->_datas[static::FIELD_NAME] = (string) $name;
				return true;
			}

			return false;
		}

		/**
		  * @param string $search
		  * @param bool $strict
		  * @return bool
		  */
		public function match($search, $strict = false)
		{
			$fieldAttrs = static::FIELD_ATTRS;
			$fieldAttrs[] = static::FIELD_NAME;

			return $this->_match($search, $fieldAttrs, $strict);
		}

		/**
		  * @param string $search
		  * @param array $fieldAttrs
		  * @param bool $strict
		  * @return bool
		  */
		protected function _match($search, array $fieldAttrs, $strict)
		{
			$search = preg_quote($search, '#');
			$search = str_replace('\\*', '.*', $search);
			$search = ($strict) ? ('^('.$search.')$') : ('('.$search.')');

			foreach($fieldAttrs as $attribute)
			{
				$result = preg_match("#".$search."#i", $this->_datas[$attribute]);

				if($result > 0) {
					return true;
				}
			}

			return false;
		}

		public function check()
		{
			return $this->isValid();
		}

		protected function _isValid(array $tests, $returnInvalidAttributes = false)
		{
			$status = true;
			$invalidAttrs = array();

			foreach($tests as $_tests)
			{
				$orStatus = false;
				$orInvalidAttrs = array();

				foreach($_tests as $attribute => $test)
				{
					if(!C\Tools::is($test, $this->_datas[$attribute])) {
						$orInvalidAttrs[] = $attribute;
					}
					else {
						$orStatus = true;
					}
				}

				$status = $status && $orStatus;

				if(!$orStatus) {
					$invalidAttrs = array_merge($invalidAttrs, $orInvalidAttrs);
				}
			}

			if($returnInvalidAttributes) {
				return $invalidAttrs;
			}
			else {
				return $status;
			}
		}

		public function getIterator()
		{
			return new ArrayIterator($this->_datas);
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
				return $this->_datas[$offset];
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
			$datas = $this->_datas;

			// Permet de retrouver le type
			$datas['type'] = static::API_TYPE;

			// Attribut système, non utile
			unset($datas[static::FIELD_ID]);

			/**
			  * Permet de garder une cohérence
			  * En objet, la méthode __get s'en charge
			  */
			if(static::FIELD_NAME !== 'name' && !array_key_exists('name', $datas)) {
				$datas['name'] = $datas[static::FIELD_NAME];
			}

			foreach($datas as &$data)
			{
				if($data instanceof self) {
					$data = $data->toArray();
				}
			}
			unset($data);

			return $datas;
		}

		public function toObject()
		{		
			return new ArrayObject($this->toArray(), ArrayObject::ARRAY_AS_PROPS);
		}

		public function __isset($name)
		{
			return array_key_exists($name, $this->_datas);
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'type': {
					return static::API_TYPE;
				}
				case 'id':
				case '_id_': {
					return $this->_datas[static::FIELD_ID];
				}
				case 'name':
				case 'label': {
					return $this->_datas[static::FIELD_NAME];
				}
				default:
				{
					if(isset($this->{$name})) {
						return $this->_datas[$name];
					}
					else {
						throw new Exception("This attribute '".$name."' does not exist", E_USER_ERROR);
					}
				}
			}
		}

		public function __toString()
		{
			return $this->name;
		}

		/**
		  * @return array
		  */
		public function sleep()
		{
			return array(
				//'id' => $this->_datas[static::FIELD_ID],
				'name' => $this->_datas[static::FIELD_NAME]
			);
		}

		/**
		  * @param $datas array
		  * @return bool
		  */
		public function wakeup(array $datas)
		{
			// @todo temporaire/compatibilité
			// ------------------------------
			if(!array_key_exists('id', $datas)) {
				$datas['id'] = $datas['name'];
			}
			// ------------------------------

			$idStatus = $this->id($datas['id']);
			$nameStatus = $this->name($datas['name']);

			return ($idStatus && $nameStatus);
		}

		/**
		  * @return array
		  */
		public function __debugInfo()
		{
			return $this->sleep();
		}
	}