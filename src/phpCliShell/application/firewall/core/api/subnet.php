<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	use PhpCliShell\Application\Firewall\Core;
	use PhpCliShell\Application\Firewall\Core\Exception;

	class Subnet extends Address
	{
		/**
		  * @var string
		  */
		const API_TYPE = 'subnet';

		/**
		  * @var string
		  */
		const API_INDEX = 'subnet';

		/**
		  * @var string
		  */
		const API_LABEL = 'subnet';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var string
		  */
		const FIELD_ATTRv4 = 'subnetV4';

		/**
		  * @var string
		  */
		const FIELD_ATTRv6 = 'subnetV6';

		/**
		  * @var array
		  */
		const FIELD_ATTRS = array(
			4 => 'subnetV4', 6 => 'subnetV6'
		);

		/**
		  * @var string
		  */
		const FIELD_ATTR_FCT = 'subnet';

		/**
		  * @var string
		  */
		const FIELD_ATTRS_FCT = 'subnets';

		/**
		  * @var string
		  */
		const SEPARATOR = '/';

		/**
		  * @var array
		  */
		protected $_datas = array(
			'_id_' => null,
			'name' => null,
			'subnetV4' => null,
			'subnetV6' => null,
		);


		/**
		  * @param string $id ID
		  * @param string $name Name
		  * @param string $subnetV4 Subnet v4
		  * @param string $subnetV6 Subnet v6
		  * @return $this
		  */
		public function __construct($id = null, $name = null, $subnetV4 = null, $subnetV6 = null)
		{
			$this->id($id);
			$this->name($name);
			$this->subnet($subnetV4);
			$this->subnet($subnetV6);
		}

		public function configure($address)
		{
			return $this->subnet($address);
		}

		public function subnet($subnet)
		{
			$subnetParts = explode('/', $subnet);

			if(count($subnetParts) === 2)
			{
				$networkIp = Core\Tools::networkIp($subnetParts[0], $subnetParts[1]);

				if($networkIp !== false)
				{
					$subnet = $networkIp.'/'.$subnetParts[1];

					switch(true)
					{
						case Core\Tools::isIPv4($networkIp):
						{
							// Autoriser 0.0.0.0/0 mais pas un /32
							if($subnetParts[1] >= 0 && $subnetParts[1] <= 31) {
								$this->_datas['subnetV4'] = $subnet;
								return true;
							}
							break;
						}

						case Core\Tools::isIPv6($networkIp):
						{
							// Autoriser ::/0 mais pas un /128
							if($subnetParts[1] >= 0 && $subnetParts[1] <= 127) {
								$this->_datas['subnetV6'] = mb_strtolower($subnet);
								return true;
							}
							break;
						}
						default: {
							throw new Exception("Unable to know the version of this subnet '".$subnet."'", E_USER_ERROR);
						}
					}
				}
			}

			return false;
		}

		public function subnets($subnetV4, $subnetV6)
		{
			$statusV4 = $this->subnet($subnetV4);
			$statusV6 = $this->subnet($subnetV6);
			return ($statusV4 && $statusV6);
		}

		/**
		  * @return bool
		  */
		public function isANYv4()
		{
			return ($this->_datas['subnetV4'] === '0.0.0.0/0');
		}

		/**
		  * @return bool
		  */
		public function isANYv6()
		{
			if($this->isIPv6()) {
				$subnetParts = explode('/', $this->_datas['subnetV6']);
				$IPv6 = Core\Tools::formatIPv6($subnetParts[0]);
				return ($IPv6 === '::' && $subnetParts[1] === '0');
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $addressA Address version 4 or 6
		  * @param null|string $addressB Address version 4 or 6
		  * @return bool
		  */
		public function equal($addressA, $addressB = null)
		{
			$status = 0;

			foreach(array($addressA, $addressB) as $address)
			{
				if(Core\Tools::isSubnetV4($address)) {
					$status += ($this->_datas['subnetV4'] === $address);
				}
				elseif(Core\Tools::isSubnetV6($address)) {
					$subnetParts = explode('/', $address);
					$IPv6 = Core\Tools::formatIPv6($subnetParts[0]);
					$status += ($this->_datas['subnetV6'] === $IPv6.'/'.$subnetParts[1]);
				}
			}

			return ($status > 0);
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi
		  * @return bool
		  */
		public function includes(Address $addressApi)
		{
			switch($addressApi::API_TYPE)
			{
				case Host::API_TYPE:
				{
					if($this->isIPv4() && $addressApi->isIPv4() && Core\Tools::cidrMatch($addressApi->attributeV4, $this->attributeV4)) {
						return true;
					}
					elseif($this->isIPv6() && $addressApi->isIPv6() && Core\Tools::cidrMatch($addressApi->attributeV6, $this->attributeV6)) {
						return true;
					}

					break;
				}

				case self::API_TYPE:
				{
					if($this->isIPv4() && $addressApi->isIPv4() && Core\Tools::subnetInSubnet($addressApi->attributeV4, $this->attributeV4)) {
						return true;
					}
					elseif($this->isIPv6() && $addressApi->isIPv6() && Core\Tools::subnetInSubnet($addressApi->attributeV6, $this->attributeV6)) {
						return true;
					}

					break;
				}

				case Network::API_TYPE:
				{
					if($this->isIPv4() && $addressApi->isIPv4() &&
							Core\Tools::cidrMatch($addressApi->beginV4, $this->attributeV4) &&
							Core\Tools::cidrMatch($addressApi->finishV4, $this->attributeV4)
					) {
						return true;
					}
					elseif($this->isIPv6() && $addressApi->isIPv6() && 
							Core\Tools::cidrMatch($addressApi->beginV6, $this->attributeV6) &&
							Core\Tools::cidrMatch($addressApi->finishV6, $this->attributeV6)
					) {
						return true;
					}

					break;
				}
			}

			return false;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'networkV4':
				case 'networkIpV4':
				{
					if($this->isIPv4()) {
						$subnetParts = explode('/', $this->subnetV4);
						return $subnetParts[0];
					}
					else {
						return false;
					}
				}
				case 'maskV4':
				{
					if($this->isIPv4()) {
						$subnetParts = explode('/', $this->subnetV4);
						return $subnetParts[1];
					}
					else {
						return false;
					}
				}
				case 'netMaskV4':
				{
					if($this->isIPv4()) {
						$subnetParts = explode('/', $this->subnetV4);
						return Core\Tools::cidrMaskToNetMask($subnetParts[1]);
					}
					else {
						return false;
					}
				}
				case 'broadcastIpV4':
				{
					if($this->isIPv4()) {
						$subnetParts = explode('/', $this->subnetV4);
						return Core\Tools::broadcastIp($subnetParts[0], $subnetParts[1]);
					}
					else {
						return false;
					}
				}
				case 'networkV6':
				case 'networkIpV6':
				{
					if($this->isIPv6()) {
						$subnetParts = explode('/', $this->subnetV6);
						return $subnetParts[0];
					}
					else {
						return false;
					}
				}
				case 'maskV6':
				{
					if($this->isIPv6()) {
						$subnetParts = explode('/', $this->subnetV6);
						return $subnetParts[1];
					}
					else {
						return false;
					}
				}
				case 'broadcastIpV6':
				{
					if($this->isIPv6()) {
						$subnetParts = explode('/', $this->subnetV6);
						return Core\Tools::broadcastIp($subnetParts[0], $subnetParts[1]);
					}
					else {
						return false;
					}
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}