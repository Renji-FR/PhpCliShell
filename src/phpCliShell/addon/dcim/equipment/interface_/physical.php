<?php
	namespace PhpCliShell\Addon\Dcim\Equipment\Interface_;

	abstract class Physical extends AbstractInterface
	{
		const INTERFACE_SEPARATOR = ':';


		/**
		  * @return int
		  */
		abstract protected function _getInterfaceId();

		/**
		  * @return \PhpCliShell\Addon\Dcim\Api\AbstractInterface
		  */
		abstract protected function _getInterfaceApi();

		/**
		  * @return string
		  */
		public function getHostName()
		{
			if(!array_key_exists('hostName', $this->_datas)) {
				$this->_datas['hostName'] = $this->_equipment->getHostName();
			}

			return $this->_datas['hostName'];
		}

		/**
		  * @param null|int $connectorId
		  * @return false|int
		  */
		abstract public function getEquipmentId($connectorId = null);

		/**
		  * @param null|int $connectorId
		  * @return false|int
		  */
		abstract public function getTopModuleId($connectorId = null);

		/**
		  * @param null|int $connectorId
		  * @return false|int
		  */
		abstract public function getModuleId($connectorId = null);

		public function __get($name)
		{
			switch($name)
			{
				case 'id': {
					return $this->_getInterfaceId();
				}
				case 'api': {
					return $this->_getInterfaceApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}