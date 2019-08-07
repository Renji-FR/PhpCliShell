<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	use PhpCliShell\Application\Firewall\Core;
	use PhpCliShell\Application\Firewall\Core\Exception;

	class Network extends Address
	{
		/**
		  * @var string
		  */
		const API_TYPE = 'network';

		/**
		  * @var string
		  */
		const API_INDEX = 'network';

		/**
		  * @var string
		  */
		const API_LABEL = 'network';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var string
		  */
		const FIELD_ATTRv4 = 'networkV4';

		/**
		  * @var string
		  */
		const FIELD_ATTRv6 = 'networkV6';

		/**
		  * @var array
		  */
		const FIELD_ATTRS = array(
			4 => 'networkV4', 6 => 'networkV6'
		);

		/**
		  * @var string
		  */
		const FIELD_ATTR_FCT = 'network';

		/**
		  * @var string
		  */
		const FIELD_ATTRS_FCT = 'networks';

		/**
		  * @var string
		  */
		const SEPARATOR = '-';

		/**
		  * @var array
		  */
		protected $_datas = array(
			'_id_' => null,
			'name' => null,
			'networkV4' => null,
			'networkV6' => null,
		);


		/**
		  * @param string $id ID
		  * @param string $name Name
		  * @param string $networkV4 Network v4
		  * @param string $networkV6 Network v6
		  * @return $this
		  */
		public function __construct($id = null, $name = null, $networkV4 = null, $networkV6 = null)
		{
			$this->id($id);
			$this->name($name);
			$this->network($networkV4);
			$this->network($networkV6);
		}

		public function configure($address)
		{
			return $this->network($address);
		}

		public function network($network)
		{
			$networkParts = explode(self::SEPARATOR, $network, 2);

			if(count($networkParts) === 2)
			{
				if(Core\Tools::isIPv4($networkParts[0]) && Core\Tools::isIPv4($networkParts[1]))
				{
					if(strnatcasecmp($networkParts[0], $networkParts[1]) <= 0) {
						$this->_datas['networkV4'] = $network;
						return true;
					}
				}
				elseif(Core\Tools::isIPv6($networkParts[0]) && Core\Tools::isIPv6($networkParts[1]))
				{
					if(strnatcasecmp($networkParts[0], $networkParts[1]) <= 0)
					{
						$this->_datas['networkV6'] = 
							Core\Tools::formatIPv6($networkParts[0])
							.self::SEPARATOR.
							Core\Tools::formatIPv6($networkParts[1])
						;

						return true;
					}
				}
			}

			return false;
		}

		public function networks($networkV4, $networkV6)
		{
			$statusV4 = $this->network($networkV4);
			$statusV6 = $this->network($networkV6);
			return ($statusV4 && $statusV6);
		}

		/**
		  * @return bool
		  */
		public function isANYv4()
		{
			return ($this->_datas['networkV4'] === '0.0.0.0'.self::SEPARATOR.'255.255.255.255');
		}

		/**
		  * @return bool
		  */
		public function isANYv6()
		{
			if($this->isIPv6()) {
				$networkParts = explode(self::SEPARATOR, $this->_datas['networkV6']);
				$IPv6_first = Core\Tools::formatIPv6($networkParts[0]);
				$IPv6_second = Core\Tools::formatIPv6($networkParts[1]);
				return ($IPv6_first === '::' && $IPv6_second === 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff');
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
				$networkParts = explode(self::SEPARATOR, $address, 2);

				if(count($networkParts) === 2)
				{
					if(Core\Tools::isIPv4($networkParts[0]) && Core\Tools::isIPv4($networkParts[1])) {
						$status += ($this->_datas['networkV4'] === $address);
					}
					elseif(Core\Tools::isIPv6($networkParts[0]) && Core\Tools::isIPv6($networkParts[1]))
					{
						$address = 
							Core\Tools::formatIPv6($networkParts[0])
							.self::SEPARATOR.
							Core\Tools::formatIPv6($networkParts[1])
						;

						$status += ($this->_datas['networkV6'] === $address);
					}
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
					if($this->isIPv4() && $addressApi->isIPv4() && 
							Core\Tools::IpToBin($addressApi->attributeV4) >= Core\Tools::IpToBin($this->beginV4) &&
							Core\Tools::IpToBin($addressApi->attributeV4) <= Core\Tools::IpToBin($this->finishV4)
					) {
						return true;
					}
					elseif($this->isIPv6() && $addressApi->isIPv6() && 
							Core\Tools::IpToBin($addressApi->attributeV6) >= Core\Tools::IpToBin($this->beginV6) &&
							Core\Tools::IpToBin($addressApi->attributeV6) <= Core\Tools::IpToBin($this->finishV6)
					) {
						return true;
					}

					break;
				}

				case Subnet::API_TYPE:
				{
					if($this->isIPv4() && $addressApi->isIPv4())
					{
						$firstIPv4 = Core\Tools::firstSubnetIp($addressApi->attributeV4);
						$lastIPv4 = Core\Tools::lastSubnetIp($addressApi->attributeV4);

						if(Core\Tools::IpToBin($firstIPv4) >= Core\Tools::IpToBin($this->beginV4) &&
							Core\Tools::IpToBin($lastIPv4) <= Core\Tools::IpToBin($this->finishV4)
						) {
							return true;
						}
					}

					if($this->isIPv6() && $addressApi->isIPv6())
					{
						$firstIPv6 = Core\Tools::firstSubnetIp($addressApi->attributeV6);
						$lastIPv6 = Core\Tools::lastSubnetIp($addressApi->attributeV6);

						if(Core\Tools::IpToBin($firstIPv6) >= Core\Tools::IpToBin($this->beginV6) &&
							Core\Tools::IpToBin($lastIPv6) <= Core\Tools::IpToBin($this->finishV6)
						) {
							return true;
						}
					}

					break;
				}

				case self::API_TYPE:
				{
					if($this->isIPv4() && $addressApi->isIPv4() && 
							Core\Tools::IpToBin($addressApi->beginV4) >= Core\Tools::IpToBin($this->beginV4) &&
							Core\Tools::IpToBin($addressApi->finishV4) <= Core\Tools::IpToBin($this->finishV4)
					) {
						return true;
					}
					elseif($this->isIPv6() && $addressApi->isIPv6() && 
							Core\Tools::IpToBin($addressApi->beginV6) >= Core\Tools::IpToBin($this->beginV6) &&
							Core\Tools::IpToBin($addressApi->finishV6) <= Core\Tools::IpToBin($this->finishV6)
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
				case 'firstV4':
				case 'beginV4': {
					$parts = explode(self::SEPARATOR, $this->_datas['networkV4'], 2);
					return $parts[0];
				}
				case 'secondV4':
				case 'finishV4': {
					$parts = explode(self::SEPARATOR, $this->_datas['networkV4'], 2);
					return $parts[1];
				}
				case 'firstV6':
				case 'beginV6': {
					$parts = explode(self::SEPARATOR, $this->_datas['networkV6'], 2);
					return $parts[0];
				}
				case 'secondV6':
				case 'finishV6': {
					$parts = explode(self::SEPARATOR, $this->_datas['networkV6'], 2);
					return $parts[1];
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}