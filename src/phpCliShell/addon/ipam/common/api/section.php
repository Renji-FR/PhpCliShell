<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Adapter;

	abstract class Section extends AbstractParent
	{
		/**
		  * @var string
		  */
		const OBJECT_KEY = 'SECTION';

		/**
		  * @var string
		  */
		const OBJECT_TYPE = 'section';

		/**
		  * @var string
		  */
		const OBJECT_NAME = 'section';

		/**
		  * @var string
		  */
		const FIELD_ID = 'id';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var string
		  */
		const FIELD_DESC = 'description';

		/**
		  * @var string
		  */
		const RESOLVER_GETTERS_API_NAME = 'Sections';


		public function sectionIdIsValid($sectionId)
		{
			return $this->objectIdIsValid($sectionId);
		}

		public function hasSectionId()
		{
			return $this->hasObjectId();
		}

		public function getSectionId()
		{
			return $this->getObjectId();
		}

		public function sectionExists()
		{
			return $this->objectExists();
		}

		public function setSectionLabel($locationLabel)
		{
			return $this->_setObjectLabel($locationLabel);
		}

		public function hasSectionLabel()
		{
			return $this->hasObjectLabel();
		}

		public function getSectionLabel()
		{
			return $this->getObjectLabel();
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_adapter->getSection($this->getSectionId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		/**
		  * Gets parent section ID
		  *
		  * @return false|int Section ID
		  */
		public function getParentId()
		{
			return $this->_getField(static::FIELD_PARENT_ID, 'int&&>0');
		}

		/**
		  * Gets parent section
		  * Do not filter section
		  *
		  * @return false|array Section
		  */
		public function getParent()
		{
			$parentId = $this->getParentId();

			if($parentId !== false)
			{
				$cacheContainer = $this->_getCacheContainer();
				
				if($cacheContainer !== false) {
					return $cacheContainer->retrieve($parentId);
				}
				else {
					return $this->_adapter->getSection($parentId);
				}
			}

			return false;
		}

		/**
		  * @param bool $resetObjectId
		  * @return void
		  */
		protected function _hardReset($resetObjectId = false)
		{
			parent::_hardReset($resetObjectId);
			$this->_resetAttributes();
		}

		protected function _resetAttributes()
		{
			$this->_path = null;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'description': {
					return $this->_getField(static::FIELD_DESC, 'string&&!empty');
				}
				default: {
					return parent::__get($name);
				}
			}
		}

		public function __call($name, $parameters = null)
		{
			if(substr($name, 0, 3) === 'get')
			{
				$attribute = substr($name, 3);
				$attribute = mb_strtolower($attribute);

				switch($attribute)
				{
					case 'description': {
						return $this->_getField(static::FIELD_DESC, 'string&&!empty');
					}
				}
			}

			return parent::__call($name, $parameters);
		}
	}