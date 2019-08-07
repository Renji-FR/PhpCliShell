<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Addon\Ipam\Phpipam\Adapter;

	class Address extends Common\Api\Address implements InterfaceApi
	{
		use TraitApi;

		/**
		  * @var string
		  */
		const FIELD_STATE = 'tag';

		/**
		  * @var int
		  */
		const OFFLINE = 1;

		/**
		  * @var int
		  */
		const ONLINE = 2;

		/**
		  * @var int
		  */
		const RESERVED = 3;

		/**
		  * @var int
		  */
		const DHCP = 4;

		/**
		  * @var array
		  */
		const STATES = array(
			self::OFFLINE => 'offline',
			self::ONLINE => 'online',
			self::RESERVED => 'reserved',
			self::DHCP => 'DHCP',
		);


		public function getNote()
		{
			return $this->_getField('note', 'string&&!empty');
		}

		/**
		  * @param mixed $state State
		  * @return bool
		  */
		protected function _create($state = null)
		{
			if($state === null) {
				$state = self::ONLINE;
			}

			return $this->_adapter->createAddress($this->getSubnetId(), $this->getAddress(), $this->getHostname(), $this->getDescription, '', '', $state);
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'note': {
					return $this->_getField('note', 'string');
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}