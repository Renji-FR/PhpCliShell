<?php
	namespace PhpCliShell\Addon\Dcim\Api;

	use PhpCliShell\Core as C;

	abstract class AbstractLocality extends AbstractApi
	{
		/**
		  * @var int
		  */
		protected $_locationId = null;

		/**
		  * @var \PhpCliShell\Addon\Dcim\Api\Location
		  */
		protected $_locationApi = null;


		public function setLocationId($locationId)
		{
			if(!$this->objectExists() && C\Tools::is('int&&>0', $locationId)) {
				$this->_locationId = $locationId;
				return true;
			}
			else {
				return false;
			}
		}

		public function hasLocationId()
		{
			return ($this->getLocationId() !== false);
		}

		abstract public function getLocationId();

		public function getLocationApi()
		{
			if($this->_locationApi === null)
			{
				$locationId = $this->getLocationId();

				if($locationId !== false) {
					$this->_locationApi = Location::factory($locationId);
				}
				else {
					$this->_locationApi = false;
				}
			}

			return $this->_locationApi;
		}

		/**
		  * @return void
		  */
		protected function _resetLocation()
		{
			$this->_locationId = null;
			$this->_locationApi = null;
		}
	}