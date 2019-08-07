<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Addon\Ipam\Common\Tools;
	use PhpCliShell\Addon\Ipam\Common\Adapter;

	abstract class Address extends AbstractApi
	{
		/**
		  * @var string
		  */
		const OBJECT_KEY = 'ADDRESS';

		/**
		  * @var string
		  */
		const OBJECT_TYPE = 'address';

		/**
		  * @var string
		  */
		const OBJECT_NAME = 'address';

		/**
		  * @var string
		  */
		const FIELD_ID = 'id';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'hostname';

		/**
		  * @var string
		  */
		const FIELD_DESC = 'description';

		/**
		  * @var string
		  */
		const FIELD_ADDRESS = 'ip';

		/**
		  * @var string
		  */
		const FIELD_STATE = 'state';

		/**
		  * @var string
		  */
		const FIELD_SUBNET_ID = 'subnetId';

		/**
		  * @var string
		  */
		const RESOLVER_GETTERS_API_NAME = 'Addresses';

		/**
		  * @var array
		  */
		const STATES = array();

		/**
		  * @var int
		  */
		protected $_subnetId = null;

		/**
		  * @var \PhpCliShell\Addon\Ipam\Common\Api\Subnet
		  */
		protected $_subnetApi = null;

		/**
		  * @var string
		  */
		protected $_address = null;

		/**
		  * @var string
		  */
		protected $_description = null;


		public function addressIdIsValid($addressId)
		{
			return $this->objectIdIsValid($addressId);
		}

		public function hasAddressId()
		{
			return $this->hasObjectId();
		}

		public function getAddressId()
		{
			return $this->getObjectId();
		}

		public function addressExists()
		{
			return $this->objectExists();
		}

		public function hasAddressLabel()
		{
			return $this->hasObjectLabel();
		}

		public function getAddressLabel()
		{
			return $this->getObjectLabel();
		}

		public function setAddressLabel($addressLabel)
		{
			return $this->_setObjectLabel($addressLabel);
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_adapter->getAddress($this->getAddressId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		/**
		  * @return bool
		  */
		public function hasDescription()
		{
			return ($this->getDescription() !== false);
		}

		/**
		  * @return false|string
		  */
		public function getDescription()
		{
			if($this->addressExists())
			{
				if($this->_description === null) {
					$this->_description = $this->_getField(static::FIELD_DESC, 'string');
				}

				return $this->_description;
			}
			elseif($this->_description !== null) {
				return $this->_description;
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $description
		  * @return bool
		  */
		public function setDescription($description)
		{
			if(!$this->addressExists() && C\Tools::is('string', $description)) {
				$this->_description = $description;
				return true;
			}
			else {
				return false;
			}
		}

		/**
		  * @param bool $returnLabel
		  * @return false|mixed State
		  */
		public function getState($returnLabel = false)
		{
			$state = $this->_getField(static::FIELD_STATE, 'int&&>0');

			if($state !== false) {
				return ($returnLabel) ? (static::STATES[$state]) : ($state);
			}
			else {
				return false;
			}
		}

		/**
		  * @param int $IPv IP version, 4 or 6
		  * @return bool
		  */
		public function isIPv($IPv)
		{
			switch($IPv)
			{
				case 4: {
					return $this->isIPv4();
				}
				case 6: {
					return $this->isIPv6();
				}
				default: {
					return false;
				}
			}
		}

		/**
		  * @return bool
		  */
		public function isIPv4()
		{
			$address = $this->getAddress();
			return Tools::isIPv4($address);
		}

		/**
		  * @return bool
		  */
		public function isIPv6()
		{
			$address = $this->getAddress();
			return Tools::isIPv6($address);
		}

		public function getIPv()
		{
			if($this->isIPv4()) {
				return 4;
			}
			elseif($this->isIPv6()) {
				return 6;
			}
			else {
				return false;
			}
		}

		/**
		  * @return bool
		  */
		public function hasSubnetId()
		{
			return ($this->getSubnetId() !== false);
		}

		public function setSubnetId($subnetId)
		{
			if(!$this->objectExists() && C\Tools::is('int&&>0', $subnetId)) {
				$this->_subnetId = $subnetId;
				return true;
			}
			else {
				return false;
			}
		}

		public function setSubnetApi(Subnet $subnetApi)
		{
			if($subnetApi->subnetExists())
			{
				$status = $this->setSubnetId($subnetApi->id);

				if($status) {
					$this->_subnetApi = $subnetApi;
				}

				return $status;
			}
			else {
				return false;
			}
		}

		public function getSubnet()
		{
			$subnetId = $this->getSubnetId();

			if($subnetId !== false) {
				return $this->_adapter->getSubnet($subnetId);
			}
			else {
				return false;
			}
		}

		public function getSubnetId()
		{
			if($this->addressExists())
			{
				if($this->_subnetId === null) {
					$this->_subnetId = $this->_getField(static::FIELD_SUBNET_ID, 'int&&>0');
				}

				return $this->_subnetId;
			}
			elseif($this->_subnetId !== null) {
				return $this->_subnetId;
			}
			else {
				return false;
			}
		}

		public function getSubnetApi()
		{
			if($this->_subnetApi === null)
			{
				$subnetId = $this->getSubnetId();

				if($subnetId !== false) {
					$subnetApi = $this->_resolver->resolve('Subnet');
					$this->_subnetApi = $subnetApi::factory($subnetId, $this->_service);
				}
				else {
					$this->_subnetApi = false;
				}
			}

			return $this->_subnetApi;
		}

		public function hasAddress()
		{
			return ($this->getAddress() !== false);
		}

		public function setAddress($address)
		{
			if(!$this->objectExists() && $this->hasSubnetId() && Tools::isIP($address))
			{
				if(($this->getSubnetApi()->isIPv4() && Tools::isIPv4($address) && Tools::cidrMatch($address, $this->getSubnetApi()->subnet)) ||
					($this->getSubnetApi()->isIPv6() && Tools::isIPv6($address) && Tools::cidrMatch($address, $this->getSubnetApi()->subnet)))
				{
					$this->_address = $address;
					return true;
				}
			}

			return false;
		}

		public function getAddress()
		{
			if($this->addressExists())
			{
				if($this->_address === null) {
					$this->_address = $this->_getField(static::FIELD_ADDRESS, 'string&&!empty');
				}

				return $this->_address;
			}
			elseif($this->_address !== null) {
				return $this->_address;
			}
			else {
				return false;
			}
		}

		public function create($state = null, $autoRegisterToStore = true)
		{
			$this->_errorMessage = null;

			if(!$this->addressExists())
			{
				if($this->hasSubnetId())
				{
					if($this->hasAddress())
					{
						if($this->hasAddressLabel())
						{
							if(!array_key_exists($state, static::STATES)) {
								$state = null;
							}

							try {
								$status = $this->_create($state);
							}
							catch(E\Message $e) {
								$this->_errorMessage = $e->getMessage();
								$status = false;
							}

							if($status)
							{
								$addresses = $this->findIpAddresses($this->getAddress(), $this->getSubnetId(), true);

								if($addresses !== false && count($addresses) === 1)
								{
									$addressId = $addresses[0][static::FIELD_ID];
									$this->_hardReset(false);
									$this->_setObjectId($addressId);

									if($autoRegisterToStore) {
										$this->_registerToStore();
									}
								}
								else {
									$status = false;
								}
							}

							if(!$status) {
								$this->_hardReset(false);
							}

							return $status;
						}
						else {
							$this->_errorMessage = "Address hostname is required";
						}
					}
					else {
						$this->_errorMessage = "Address IP is required";
					}
				}
				else {
					$this->_errorMessage = "Address subnet is required";
				}
			}
			else {
				$this->_errorMessage = "Address '".$this->label."' already exists";
			}

			return false;
		}

		/**
		  * @param mixed $state State
		  * @return bool
		  */
		abstract protected function _create($state = null);

		/*public function modify($description = '', $note = '', $port = '', $state = null)
		{
			// @todo a coder
		}*/

		/**
		  * @param string $label
		  * @return bool
		  */
		public function renameHostname($label)
		{
			return $this->_updateInfos($label, $this->description);
		}

		/**
		  * @param string $description
		  * @return bool
		  */
		public function changeDescription($description)
		{
			return $this->_updateInfos($this->label, $description);
		}

		/**
		  * @param string $label
		  * @param string $description
		  * @return bool
		  */
		public function updateInfos($label, $description)
		{
			return $this->_updateInfos($label, $description);
		}

		/**
		  * @param string $label
		  * @param string $description
		  * @return bool
		  */
		protected function _updateInfos($label, $description)
		{
			$this->_errorMessage = null;

			if($this->addressExists())
			{
				try {
					$status = $this->_adapter->modifyAddress($this->getAddressId(), $label, $description);
				}
				catch(E\Message $e) {
					$this->_errorMessage = $e->getMessage();
					$status = false;
				}

				$this->refresh();
				return $status;
			}
			else {
				$this->_errorMessage = "IPAM address does not exist";
				return false;
			}
		}

		public function remove()
		{
			$this->_errorMessage = null;

			if($this->addressExists())
			{
				try {
					$status = $this->_adapter->removeAddress($this->getAddressId());
				}
				catch(E\Message $e) {
					$this->_errorMessage = $e->getMessage();
					$status = false;
				}

				$this->_unregisterFromStore();
				$this->_hardReset();
				return $status;
			}
			else {
				$this->_errorMessage = "IPAM address does not exist";
			}

			return false;
		}

		/**
		  * @param bool $resetObjectId
		  * @return void
		  */
		protected function _softReset($resetObjectId = false)
		{
			parent::_softReset($resetObjectId);
			$this->_resetAttributes();
		}

		/**
		  * @param bool $resetObjectId
		  * @return void
		  */
		protected function _hardReset($resetObjectId = false)
		{
			parent::_hardReset($resetObjectId);
			$this->_resetSubnet();
		}

		protected function _resetAttributes()
		{
			$this->_address = null;
			$this->_description = null;
		}

		protected function _resetSubnet()
		{
			$this->_subnetId = null;
			$this->_subnetApi = null;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'ip':
				case 'address': {
					return $this->getAddress();
				}
				case 'hostname': {
					return $this->getAddressLabel();
				}
				case 'description': {
					return $this->getDescription();
				}
				case 'state': {
					return $this->getState();
				}
				case 'subnetApi': {
					return $this->getSubnetApi();
				}
				case 'vlanApi': {
					$subnetApi = $this->getSubnetApi();
					return ($subnetApi !== false) ? ($subnetApi->vlanApi) : (false);
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
					case 'ip':
					case 'address': {
						return $this->getAddress();
					}
					case 'hostname': {
						return $this->getAddressLabel();
					}
				}
			}

			return parent::__call($method, $parameters);
		}
	}