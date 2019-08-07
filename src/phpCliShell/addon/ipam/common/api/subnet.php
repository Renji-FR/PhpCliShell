<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Tools;
	use PhpCliShell\Addon\Ipam\Common\Adapter;

	abstract class Subnet extends AbstractParent
	{
		use TraitFolderChild;

		/**
		  * @var string
		  */
		const OBJECT_KEY = 'SUBNET';

		/**
		  * @var string
		  */
		const OBJECT_TYPE = 'subnet';

		/**
		  * @var string
		  */
		const OBJECT_NAME = 'subnet';

		/**
		  * @var string
		  */
		const FIELD_ID = 'id';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'description';

		/**
		  * @var string
		  */
		const FIELD_SUBNET = 'subnet';

		/**
		  * @var string
		  */
		const FIELD_FOLDER_ID = 'folderId';

		/**
		  * @var string
		  */
		const FIELD_VLAN_ID = 'vlanId';

		/**
		  * @var string
		  */
		const RESOLVER_GETTERS_API_NAME = 'Subnets';

		/**
		  * @var \PhpCliShell\Addon\Ipam\Common\Api\Vlan
		  */
		protected $_vlanApi = null;


		public function subnetIdIsValid($subnetId)
		{
			return $this->objectIdIsValid($subnetId);
		}

		public function hasSubnetId()
		{
			return $this->hasObjectId();
		}

		public function getSubnetId()
		{
			return $this->getObjectId();
		}

		public function subnetExists()
		{
			return $this->objectExists();
		}

		public function getSubnetLabel()
		{
			return $this->getObjectLabel();
		}

		/**
		  * @param array $subnet Subnet
		  * @return array Subnet
		  */
		protected function _formatName(array $subnet)
		{
			if(!C\Tools::is('string&&!empty', $subnet[static::FIELD_NAME])) {
				$subnet[static::FIELD_NAME] = $subnet[static::FIELD_SUBNET];
			}

			return $subnet;
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null)
				{
					$this->_objectDatas = $this->_adapter->getSubnet($this->getSubnetId());

					if($this->_objectDatas !== false) {
						$this->_objectDatas = $this->_formatName($this->_objectDatas);
					}
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		protected function _setObject(array $datas)
		{
			if(static::objectIdIsValid($datas[static::FIELD_ID]))
			{
				$datas = $this->_formatName($datas);

				$this->_objectId = $datas[static::FIELD_ID];
				$this->_objectLabel = $datas[static::FIELD_NAME];
				$this->_objectDatas = $datas;
				$this->_objectExists = true;
				return true;
			}
			else {
				return false;
			}
		}

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

		public function isIPv4()
		{
			$subnet = $this->getCidrSubnet();
			return Tools::isSubnetV4($subnet);
		}

		public function isIPv6()
		{
			$subnet = $this->getCidrSubnet();
			return Tools::isSubnetV6($subnet);
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

		public function getCidrSubnet()
		{
			return $this->getNetwork().'/'.$this->getCidrMask();
		}

		public function getNetSubnet()
		{
			return $this->getNetwork().'/'.$this->getNetMask();
		}

		public function getNetwork()
		{
			$subnet = $this->_getField(static::FIELD_SUBNET, 'string&&!empty');
			return substr($subnet, 0, strpos($subnet, '/'));
		}

		public function getCidrMask()
		{
			$subnet = $this->_getField(static::FIELD_SUBNET, 'string&&!empty');
			return substr($subnet, (strpos($subnet, '/')+1));
		}

		public function getNetMask()
		{
			$cidrMask = $this->getCidrMask();

			if($cidrMask !== false)
			{
				if($this->isIPv4()) {
					return Tools::cidrMaskToNetMask($cidrMask);
				}
				elseif($this->isIPv6()) {
					return $cidrMask;
				}
			}

			return false;
		}

		public function getFirstIp()
		{
			return Tools::firstSubnetIp($this->getNetwork(), $this->getNetMask());
		}

		public function getLastIp()
		{
			return Tools::lastSubnetIp($this->getNetwork(), $this->getNetMask());
		}

		public function getNetworkIp()
		{
			return Tools::networkIp($this->getNetwork(), $this->getNetMask());
		}

		public function getBroadcastIp()
		{
			return Tools::broadcastIp($this->getNetwork(), $this->getNetMask());
		}

		public function getGateway()
		{
			if($this->objectExists()) {
				return $this->_adapter->getGatewayBySubnetId($this->getSubnetId());
			}
			else {
				return false;
			}
		}

		/**
		  * @param bool $includeLabel
		  * @return false|array
		  */
		public function getSubnetPaths($includeLabel = false)
		{
			if($this->objectExists())
			{			
				$objectApi = $this->getParentApi();

				if($objectApi instanceof self) {
					$path = $objectApi->getSubnetPaths(true);
				}
				else {
					$path = array();
				}

				if($includeLabel) {
					$path[] = $this->label;
				}

				return $path;
			}
			else {
				return false;
			}
		}

		/**
		  * Gets parent subnet ID
		  *
		  * @return false|int Subnet ID
		  */
		public function getParentId()
		{
			return $this->_getField(static::FIELD_PARENT_ID, 'int&&>0');
		}

		/**
		  * Gets parent subnet
		  * Do not filter subnet
		  *
		  * @return false|array Subnet
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
					return $this->_adapter->getSubnet($parentId);
				}
			}

			return false;
		}

		/**
		  * @return false|int VLAN ID
		  */
		public function getVlanId()
		{
			return $this->_getField(static::FIELD_VLAN_ID, 'int&&>0', 'int');
		}

		/**
		  * @return false|\PhpCliShell\Addon\Ipam\Common\Api\Vlan VLAN API
		  */
		public function getVlanApi()
		{
			if($this->_vlanApi === null)
			{
				$vlanId = $this->getVlanId();

				if($vlanId !== false) {
					$vlanApi = $this->_resolver->resolve('Vlan');
					$this->_vlanApi = $vlanApi::factory($vlanId, $this->_service);
				}
				else {
					$this->_vlanApi = false;
				}
			}

			return $this->_vlanApi;
		}

		/**
		  * /!\ Import trait TraitFolderChild::_hardReset
		  *
		  * @param bool $resetObjectId
		  * @return void
		  */
		protected function _hardReset($resetObjectId = false)
		{
			parent::_hardReset($resetObjectId);
			$this->_resetFolder();
			$this->_resetVlan();
		}

		/**
		  * @return void
		  */
		protected function _resetVlan()
		{
			$this->_vlanApi = null;
		}

		/**
		  * /!\ Import trait TraitFolderChild::__get
		  *
		  * @param string $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'cidr':
				case 'subnet':
				case 'cidrSubnet': {
					return $this->getCidrSubnet();
				}
				case 'folderApi':
				case 'parentFolderApi': {
					return $this->getFolderApi();
				}
				case 'parentApi':
				case 'subnetApi':
				case 'parentSubnetApi': {
					return $this->getParentApi();
				}
				case 'vlanApi': {
					return $this->getVlanApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}