<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Adapter;

	abstract class Vlan extends AbstractApi
	{
		/**
		  * @var string
		  */
		const OBJECT_KEY = 'VLAN';

		/**
		  * @var string
		  */
		const OBJECT_TYPE = 'vlan';

		/**
		  * @var string
		  */
		const OBJECT_NAME = 'vlan';

		/**
		  * @var string
		  */
		const FIELD_ID = 'id';

		/**
		  * @var string
		  */
		const FIELD_VLAN = 'number';

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
		const RESOLVER_GETTERS_API_NAME = 'Vlans';

		/**
		  * @var array
		  */
		protected $_subnets = null;


		public function vlanIdIsValid($vlanId)
		{
			return $this->objectIdIsValid($vlanId);
		}

		public function hasVlanId()
		{
			return $this->hasObjectId();
		}

		public function getVlanId()
		{
			return $this->getObjectId();
		}

		public function vlanExists()
		{
			return $this->objectExists();
		}

		public function getVlanLabel()
		{
			return $this->getObjectLabel();
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_adapter->getVlan($this->getVlanId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		/**
		  * @return false|array Subnets
		  */
		public function getSubnets()
		{
			if($this->objectExists())
			{
				if($this->_subnets === null) {
					$this->_subnets = $this->_adapter->getSubnetsFromVlan($this->getVlanId());
				}

				return $this->_subnets;
			}
			else {
				return false;
			}
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
			$this->_subnets = null;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'number': {
					return $this->_getField($name, 'string&&!empty');
				}
				case 'description': {
					return $this->_getField(static::FIELD_DESC, 'string&&!empty');
				}
				default: {
					return parent::__get($name);
				}
			}
		}

		public function __call($method, $parameters = null)
		{
			if(substr($method, 0, 3) === 'get')
			{
				$name = substr($method, 3);
				$name = mb_strtolower($name);

				switch($name)
				{
					case 'number': {
						return $this->_getField($name, 'string&&!empty');
					}
					case 'description': {
						return $this->_getField(static::FIELD_DESC, 'string&&!empty');
					}
				}
			}

			return parent::__call($method, $parameters);
		}
	}