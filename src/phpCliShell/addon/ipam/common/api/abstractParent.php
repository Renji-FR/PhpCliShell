<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	abstract class AbstractParent extends AbstractApi
	{
		/**
		  * @var string
		  */
		const FIELD_PARENT_ID = 'parentId';

		/**
		  * @var false|\PhpCliShell\Addon\Ipam\Common\Api\AbstractParent
		  */
		protected $_parentApi = null;

		/**
		  * Path to self object
		  * @var array
		  */
		protected $_path = null;


		/**
		  * Gets parent
		  * Do not filter parent
		  *
		  * @return false|array Parent
		  */
		abstract public function getParent();

		/**
		  * Gets parent ID
		  *
		  * @return false|int Parent ID
		  */
		abstract public function getParentId();

		/**
		  * Gets parent API
		  *
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\AbstractParent Parent API
		  */
		public function getParentApi()
		{
			if($this->_parentApi === null)
			{
				$parentId = $this->getParentId();

				if($parentId !== false) {
					$this->_parentApi = static::factory($parentId, $this->_service);
				}
				else {
					$this->_parentApi = false;
				}
			}

			return $this->_parentApi;
		}

		/**
		  * @param bool $includeLabel
		  * @param false|string $pathSeparator
		  * @return false|string
		  */
		public function getPath($includeLabel = false, $pathSeparator = false)
		{
			$path = $this->getPaths($includeLabel);

			if($path !== false)
			{
				if($pathSeparator === false) {
					$pathSeparator = static::SEPARATOR_PATH;
				}

				return implode($pathSeparator, $path);
			}
			else {
				return false;
			}
		}

		/**
		  * @param bool $includeLabel
		  * @return false|array
		  */
		public function getPaths($includeLabel = false)
		{
			if($this->objectExists())
			{
				if($this->_path === null)
				{
					$parentApi = $this->getParentApi();

					if($parentApi !== false) {
						$this->_path = $parentApi->getPaths(true);
					}
					else {
						$this->_path = array();
					}
				}

				if($this->_path !== false)
				{
					$path = $this->_path;

					if($includeLabel && $this->hasObjectLabel()) {
						$path[] = $this->getObjectLabel();
					}

					return $path;
				}
			}
			elseif($includeLabel && $this->hasObjectLabel()) {
				return array($this->getObjectLabel());
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
			$this->_resetParent();
		}

		/**
		  * @return void
		  */
		protected function _resetAttributes()
		{
			$this->_path = null;
		}

		/**
		  * @return void
		  */
		protected function _resetParent()
		{
			$this->_parentApi = null;
		}

		/**
		  * @param string $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'parentApi': {
					return $this->getParentApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}