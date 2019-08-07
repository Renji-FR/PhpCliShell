<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Api;

	use PhpCliShell\Addon\Ipam\Common;

	class Address extends Common\Api\Address implements InterfaceApi
	{
		use TraitApi;

		/**
		  * @var string
		  */
		const FIELD_ID = 'id';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'dns_name';

		/**
		  * @var string
		  */
		const FIELD_DESC = 'description';

		/**
		  * @var string
		  */
		const FIELD_ADDRESS = 'address';

		/**
		  * @var string
		  */
		const FIELD_STATE = 'status_value';

		/**
		  * @var string
		  */
		const FIELD_SUBNET_ID = 'prefixId';

		/**
		  * @var int
		  */
		const ACTIVE = 1;

		/**
		  * @var int
		  */
		const RESERVED = 2;

		/**
		  * @var int
		  */
		const DEPRECATED = 3;

		/**
		  * @var int
		  */
		const DHCP = 4;

		/**
		  * @var array
		  */
		const STATES = array(
			self::ACTIVE => 'active',
			self::RESERVED => 'reserved',
			self::DEPRECATED => 'deprecated',
			self::DHCP => 'DHCP',
		);


		/**
		  * @param bool $returnLabel
		  * @return false|mixed State
		  */
		public function getState($returnLabel = false)
		{
			if($returnLabel) {
				$state = $this->_getField('status_label', 'string&&!empty');
			}
			else {
				$state = $this->_getField(static::FIELD_STATE, 'int&&>0');
			}
		}

		/**
		  * @return false|array Tags
		  */
		public function getTags()
		{
			return $this->_getField('tags', 'string&&!empty');
		}

		/**
		  * @todo a coder
		  *
		  * @param mixed $state State
		  * @return bool
		  */
		protected function _create($state = null)
		{
			if($state === null) {
				$state = self::ACTIVE;
			}

			return false;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'tags': {
					return $this->_getField('tags', 'string&&!empty');
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}