<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	use PhpCliShell\Application\Firewall\Core;
	use PhpCliShell\Application\Firewall\Core\Exception;

	class Host extends Address
	{
		/**
		  * @var string
		  */
		const API_TYPE = 'host';

		/**
		  * @var string
		  */
		const API_INDEX = 'host';

		/**
		  * @var string
		  */
		const API_LABEL = 'host';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var string
		  */
		const FIELD_ATTRv4 = 'addressV4';

		/**
		  * @var string
		  */
		const FIELD_ATTRv6 = 'addressV6';

		/**
		  * @var array
		  */
		const FIELD_ATTRS = array(
			4 => 'addressV4', 6 => 'addressV6'
		);

		/**
		  * @var string
		  */
		const FIELD_ATTR_FCT = 'address';

		/**
		  * @var string
		  */
		const FIELD_ATTRS_FCT = 'addresses';

		/**
		  * @var array
		  */
		protected $_datas = array(
			'_id_' => null,
			'name' => null,
			'addressV4' => null,
			'addressV6' => null,
		);


		/**
		  * @param string $id ID
		  * @param string $name Name
		  * @param string $addressV4 Adress IP v4
		  * @param string $addressV6 Adress IP v6
		  * @return $this
		  */
		public function __construct($id = null, $name = null, $addressV4 = null, $addressV6 = null)
		{
			$this->id($id);
			$this->name($name);
			$this->address($addressV4);
			$this->address($addressV6);
		}

		public function configure($address)
		{
			return $this->address($address);
		}

		public function address($address)
		{
			if(Core\Tools::isIPv4($address)) {
				$this->_datas['addressV4'] = $address;
				return true;
			}
			elseif(Core\Tools::isIPv6($address)) {
				$this->_datas['addressV6'] = Core\Tools::formatIPv6($address);
				return true;
			}

			return false;
		}

		public function addresses($addressV4, $addressV6)
		{
			$statusV4 = $this->address($addressV4);
			$statusV6 = $this->address($addressV6);
			return ($statusV4 && $statusV6);
		}

		/**
		  * @return bool
		  */
		public function isANYv4()
		{
			return ($this->_datas['addressV4'] === '0.0.0.0');
		}

		/**
		  * @return bool
		  */
		public function isANYv6()
		{
			if($this->isIPv6()) {
				return (Core\Tools::formatIPv6($this->_datas['addressV6']) === '::');
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
				if(Core\Tools::isIPv4($address)) {
					$status += ($this->_datas['addressV4'] === $address);
				}
				elseif(Core\Tools::isIPv6($address)) {
					$status += ($this->_datas['addressV6'] === Core\Tools::formatIPv6($address));
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
				case self::API_TYPE:
				{
					if($this->isIPv4() && $addressApi->isIPv4() && $this->attributeV4 === $addressApi->attributeV4) {
						return true;
					}
					elseif($this->isIPv6() && $addressApi->isIPv6() && $this->attributeV6 === $addressApi->attributeV6) {
						return true;
					}

					break;
				}

				case Subnet::API_TYPE:
				{
					// /!\ Qu'est-ce qui impacte $addressApi (subnet) ? Un host ($this) ne peut pas impacter un subnet ($addressApi)
					/*if($this->isIPv4() && $addressApi->isIPv4() && Core\Tools::cidrMatch($this->attributeV4, $addressApi->attributeV4)) {
						return true;
					}
					elseif($this->isIPv6() && $addressApi->isIPv6() && Core\Tools::cidrMatch($this->attributeV6, $addressApi->attributeV6)) {
						return true;
					}*/

					break;
				}

				case Network::API_TYPE:
				{
					// /!\ Qu'est-ce qui impacte $addressApi (network) ? Un host ($this) ne peut pas impacter un network ($addressApi)
					/*if($this->isIPv4() && $addressApi->isIPv4() && 
							Core\Tools::IpToBin($this->attributeV4) > Core\Tools::IpToBin($addressApi->beginV4) &&
							Core\Tools::IpToBin($this->attributeV4) < Core\Tools::IpToBin($addressApi->finishV4)
					) {
						return true;
					}
					elseif($this->isIPv6() && $addressApi->isIPv6() && 
							Core\Tools::IpToBin($this->attributeV6) > Core\Tools::IpToBin($addressApi->beginV6) &&
							Core\Tools::IpToBin($this->attributeV6) < Core\Tools::IpToBin($addressApi->finishV6)
					) {
						return true;
					}*/

					break;
				}
			}

			return false;
		}
	}