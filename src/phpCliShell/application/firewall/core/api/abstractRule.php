<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Application\Firewall\Core\Exception;

	abstract class AbstractRule extends AbstractApi implements InterfaceRule
	{
		/**
		  * @var int
		  */
		protected static $_TIMESTAMP = null;

		/**
		  * @var array
		  */
		protected $_datas = array(
			'_id_' => null,
			'name' => null,
			'description' => '',
			'tags' => array(),
			'timestamp' => null,
		);


		/**
		  * @param string $id ID
		  * @param string $name Name
		  * @return $this
		  */
		public function __construct($id = null, $name = null)
		{
			parent::__construct($id, $name);

			if(self::$_TIMESTAMP === null) {
				self::$_TIMESTAMP = time();
			}

			$this->touch();
		}

		/**
		  * Sets state
		  *
		  * @param bool $state
		  * @return bool
		  */
		public function state($state = false)
		{
			if(C\Tools::is('bool', $state)) {
				$this->_datas['state'] = $state;
				return true;
			}

			return false;
		}

		/**
		  * Adds description
		  *
		  * @param string $description
		  * @return bool
		  */
		public function description($description = '')
		{
			if(C\Tools::is('string', $description)) {
				$this->_datas['description'] = $description;
				return true;
			}

			return false;
		}

		/**
		  * Adds tag
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Tag $tagApi
		  * @return bool
		  */
		public function tag(Tag $tagApi)
		{
			if($tagApi->isValid())
			{
				$_id_ = $tagApi->_id_;

				foreach($this->_datas['tags'] as $Api_Tag)
				{
					if($_id_ === $Api_Tag->_id_) {
						return false;
					}
				}

				$this->_datas['tags'][] = $tagApi;
				$this->_refresh('tags');
				return true;
			}

			return false;
		}

		/**
		  * Configures this rule
		  *
		  * @param string $attrName
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @return bool
		  */
		public function configure($attrName, AbstractApi $objectApi)
		{
			if($objectApi instanceof Tag) {
				return $this->tag($objectApi);
			}
			else {
				return false;
			}
		}

		/**
		  * @param null|string $attribute
		  * @param null|string $type
		  * @param null|\PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @return bool
		  */
		public function reset($attribute = null, $type = null, AbstractApi $objectApi = null)
		{
			switch(true)
			{
				case ($attribute === 'tag'):
				{
					if($type !== null && $objectApi !== null) {
						return $this->_resetTag($this->_datas['tags'], $type, $objectApi);
					}
					else {
						return false;
					}
				}
				case ($attribute === 'tags'): {
					$this->_datas['tags'] = array();
					break;
				}
				default: {
					return false;
				}
			}

			return true;
		}

		protected function _resetTag(&$attributes, $type, Tag $tagApi)
		{
			return $this->_reset($attributes, $type, $tagApi, 'tag');
		}

		protected function _reset(&$attributes, $type, AbstractApi $objectApi, $attrField)
		{
			foreach($attributes as $index => $attribute)
			{
				if($attribute->type === $type && $attribute->{$attrField} === $objectApi->{$attrField}) {
					unset($attributes[$index]);
					return true;
				}
			}

			return false;
		}

		/**
		  * Sets timestamp
		  *
		  * @param int $timestamp
		  * @return bool
		  */
		public function timestamp($timestamp)
		{
			if(C\Tools::is('int&&>0', $timestamp)) {
				$this->_datas['timestamp'] = $timestamp;
				return true;
			}

			return false;
		}

		/**
		  * @return $this
		  */
		public function touch()
		{
			$this->_datas['timestamp'] = self::$_TIMESTAMP;
			return $this;
		}

		/**
		  * Seulement si le timestamp n'est pas global
		  * Voir __construct pour comprendre
		  */
		/*public function touch()
		{
			$this->_datas['timestamp'] = time();
			return $this;
		}*/

		/**
		  * @return $this
		  */
		public function refresh()
		{
			$this->_refresh('tags');
			return $this;
		}

		/**
		  * @param string $attribute
		  * @return void
		  */
		protected function _refresh($attribute)
		{
			switch($attribute)
			{
				case 'tags':
				{
					uasort($this->_datas[$attribute], function($a, $b) {
						return strnatcasecmp($a->name, $b->name);
					});
					break;
				}
			}
		}

		/**
		  * @param string $search
		  * @param bool $strict
		  * @return bool
		  */
		public function match($search, $strict = false)
		{
			/**
			  * Ne pas oublier name pour les règles avec un préfixe ou un suffixe
			  */
			$fieldAttrs = array(self::FIELD_NAME, 'description');
			$isMatched = $this->_match($search, $fieldAttrs, $strict);

			if(!$isMatched)
			{
				foreach($this->tags as $Api_Tag)
				{
					if($Api_Tag->match($search, $strict)) {
						$isMatched = true;
						break;
					}
				}
			}

			return $isMatched;
		}

		/**
		  * Checks the tag argument is present for this rule
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Tag $tagApi Tag object to test
		  * @return bool Tag is present for this rule
		  */
		public function tagIsPresent(Tag $tagApi)
		{
			return $this->_objectIsPresent('tags', $tagApi);
		}

		/**
		  * Checks the object argument is present for this rule
		  *
		  * Do not test object attributes because the test
		  * must be about object itself and not it attributes
		  *
		  * @param string $attributes Attributes field name
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi Object object to test
		  * @return bool Object is present for this rule
		  */
		protected function _objectIsPresent($attributes, AbstractApi $objectApi)
		{
			$type = $objectApi->type;
			$_id_ = $objectApi->_id_;

			foreach($this->_datas[$attributes] as $attribute)
			{
				if(($attribute->type === $type && $attribute->_id_ === $_id_)) {
					return true;
				}
			}

			return false;
		}

		/**
		  * Checks the tag argument is used for this rule
		  *
		  * Do not test name because the test must be
		  * about Tag attributes and not the object
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Tag $tagApi Tag object to test
		  * @return bool Tag is used for this rule
		  */
		public function tagIsInUse(Tag $tagApi, $strict = true)
		{
			$Api_Tag = $this->getTagIsInUse($tagApi, $strict);
			return ($Api_Tag !== false);
		}

		/**
		  * Gets the tag argument is used for this rule
		  *
		  * Do not test name because the test must be
		  * about Tag attributes and not the object
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Tag $tagApi Tag object to test
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\Tag Tag is used for this rule
		  */
		public function getTagIsInUse(Tag $tagApi, $strict = true)
		{
			$type = $tagApi->type;
			$tag = $tagApi->tag;

			foreach($this->_datas['tags'] as $Api_Tag)
			{
				if($Api_Tag->type === $type && $Api_Tag->tag === $tag) {
					return $Api_Tag;
				}
			}

			return false;
		}

		/**
		  * @param bool $returnInvalidAttributes
		  * @return bool
		  */
		public function isValid($returnInvalidAttributes = false)
		{
			$tests = array(
				array('state' => 'bool'),
				array(self::FIELD_NAME => 'string&&!empty'),
				array('tags' => 'array&&count>=0'),
			);

			return $this->_isValid($tests, $returnInvalidAttributes);
		}

		/**
		  * @param mixed $name
		  * @return mixed
		  */
		public function __get($name)
		{
			return parent::__get($name);
		}

		/**
		  * @return void
		  */
		public function __clone()
		{
			$this->touch();
		}

		/**
		  * @return array
		  */
		public function sleep()
		{
			$datas = $this->_datas;

			/*if(self::FIELD_ID !== 'id') {
				$datas['id'] = $datas[self::FIELD_ID];
				unset($datas[self::FIELD_ID]);
			}*/
			unset($datas[self::FIELD_ID]);

			if(self::FIELD_NAME !== 'name') {
				$datas['name'] = $datas[self::FIELD_NAME];
				unset($datas[self::FIELD_NAME]);
			}

			foreach($datas['tags'] as &$attrObject) {
				$attrObject = $attrObject->tag;
			}
			unset($attrObject);

			/**
			  * /!\ Important for json_encode
			  * Si des index sont manquants alors json_encode
			  * va indiquer explicitement les clés dans le tableau
			  */
			$datas['tags'] = array_values($datas['tags']);

			return $datas;
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

			if(!array_key_exists('state', $datas)) {
				$datas['state'] = true;
			}

			if(!array_key_exists('tags', $datas)) {
				$datas['tags'] = array();
			}
			// ------------------------------

			// /!\ Permets de s'assurer que les traitements spéciaux sont bien appliqués
			$this->id($datas['id']);
			$this->name($datas['name']);
			$this->state($datas['state']);
			$this->description($datas['description']);
			$this->timestamp($datas['timestamp']);

			foreach($datas['tags'] as $tag)
			{
				$Api_Tag = new Tag($tag, $tag);
				$status = $Api_Tag->tag($tag);

				if($status && $Api_Tag->isValid()) {
					$this->tag($Api_Tag);
				}
				else {
					throw new E\Message("Tag '".$tag."' is not valid", E_USER_ERROR);
				}
			}
			
			return true;
		}
	}