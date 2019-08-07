<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Tools;
	use PhpCliShell\Addon\Ipam\Common\Adapter;

	trait TraitSectionChild
	{
		/**
		  * @var false|\PhpCliShell\Addon\Ipam\Common\Api\Section
		  */
		protected $_sectionApi = null;


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
					$objectApi = $this->getParentApi();

					if($objectApi !== false) {
						$this->_path = $objectApi->getPaths(true);
					}
					elseif(($sectionApi = $this->getSectionApi()) !== false) {
						$this->_path = $sectionApi->getPaths(true);
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

				return $path;
			}
			elseif($includeLabel && $this->hasObjectLabel()) {
				return array($this->getObjectLabel());
			}

			return false;
		}

		/**
		  * @return false|int
		  */
		public function getSectionId()
		{
			if(static::FIELD_SECTION_ID !== null) {
				return $this->_getField(static::FIELD_SECTION_ID, 'int&&>0', 'int');
			}
			else {
				return false;
			}
		}

		/**
		  * @return false|\PhpCliShell\Addon\Ipam\Phpipam\Api\Section
		  */
		public function getSectionApi()
		{
			if($this->_sectionApi === null)
			{
				$sectionId = $this->getSectionId();

				if($sectionId !== false) {
					$sectionApi = $this->_resolver->resolve('Section');
					$this->_sectionApi = $sectionApi::factory($sectionId, $this->_service);
				}
				else {
					$this->_sectionApi = false;
				}
			}

			return $this->_sectionApi;
		}

		/**
		  * @param bool $resetObjectId
		  * @return void
		  */
		protected function _hardReset($resetObjectId = false)
		{
			parent::_hardReset($resetObjectId);
			$this->_resetSection();
		}

		/**
		  * @return void
		  */
		protected function _resetSection()
		{
			$this->_sectionApi = null;
		}

		/**
		  * @param string $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'sectionApi':
				case 'parentSectionApi': {
					return $this->getSectionApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}