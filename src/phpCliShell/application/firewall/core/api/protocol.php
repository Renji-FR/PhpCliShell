<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Application\Firewall\Core;
	use PhpCliShell\Application\Firewall\Core\Exception;

	class Protocol extends AbstractApi
	{
		/**
		  * @var string
		  */
		const API_TYPE = 'protocol';

		/**
		  * @var string
		  */
		const API_INDEX = 'Firewall_Api_Protocol';

		/**
		  * @var string
		  */
		const API_LABEL = 'protocol';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var array
		  */
		const FIELD_ATTRS = array(
			'protocol'
		);

		/**
		  * @var string
		  */
		const PROTO_NAME_TCP_UDP_ICMP = 'ip';

		/**
		  * @var array
		  */
		const PROTO_ALIASES = array(
				'ip' => 'ip',
				6 => 'tcp',
				17 => 'udp',
				'icmp' => 'icmp',
				1 => 'icmp4',
				58 => 'icmp6',
				50 => 'esp',
				47 => 'gre',
				8 => 'egp',
				51 => 'ah',
		);

		/**
		  * @var string
		  */
		const PROTO_SEPARATOR = '/';

		/**
		  * @var string
		  */
		const PROTO_RANGE_SEPARATOR = '-';

		/**
		  * @var string
		  */
		const PROTO_OPTIONS_SEPARATOR = ':';

		/**
		  * @var string
		  */
		const PROTO_REGEX_VALIDATOR = '^([a-z0-9]+(/[0-9]{1,5}((-[0-9]{1,5})|(:[0-9]{1,3}))?)?)$';

		/**
		  * @var array
		  */
		protected $_datas = array(
			'_id_' => null,
			'name' => null,
			'protocol' => null,
		);


		/**
		  * @param string $id ID
		  * @param string $name Name
		  * @param string $protocol Protocol
		  * @return $this
		  */
		public function __construct($id = null, $name = null, $protocol = null)
		{
			$this->id($id);
			$this->name($name);
			$this->protocol($protocol);
		}

		/**
		  * Sets identity (id and name ) equal to protocol
		  *
		  * @return bool
		  */
		public function syncIdentity()
		{
			if(($protocol = $this->protocol) !== null) {
				$this->id($protocol);
				$this->name($protocol);
				return true;
			}

			return false;
		}

		/**
		  * Sets protocol name and options
		  *
		  * @param string $protocol Protocol name with or not protocol options
		  * @param string $options Protocol options
		  * @return bool
		  */
		public function protocol($protocol, $options = null)
		{
			if(preg_match('#'.self::PROTO_REGEX_VALIDATOR.'#i', $protocol))
			{
				$status = true;
				$separator = self::PROTO_SEPARATOR;
				$parts = explode($separator, $protocol, 2);
				$protocol = mb_strtolower($parts[0]);

				if(count($parts) === 2) {
					$options = $parts[1];
				}

				return $this->_protocol($protocol, $options);
			}

			return false;
		}

		/**
		  * Sets protocol options
		  *
		  * @param string $options Protocol options
		  * @return bool
		  */
		public function options($options)
		{
			$separator = self::PROTO_SEPARATOR;
			$protocol = $this->_datas['protocol'];
			$parts = explode($separator, $protocol, 2);
			return $this->_protocol($parts[0], $options);
		}

		/**
		  * Sets protocol name and options
		  *
		  * @param int|string $protocol
		  * @param string $options
		  * @return bool
		  */
		protected function _protocol($protocol, $options = null)
		{
			if(array_key_exists($protocol, self::PROTO_ALIASES)) {
				$this->_datas['protocol'] = self::PROTO_ALIASES[$protocol];
			}
			elseif(in_array($protocol, self::PROTO_ALIASES, true)) {
				$this->_datas['protocol'] = (string) $protocol;
			}
			elseif(Core\Tools::isValidProtocolNumber($protocol)) {
				$this->_datas['protocol'] = (int) $protocol;
			}
			else {
				return false;
			}

			if($options === null) {
				return true;
			}
			else
			{
				switch($this->_datas['protocol'])
				{
					case 'tcp':
					case 'udp':
					{
						if($this->_isValidTcpUdpPorts($options)) {
							$this->_datas['protocol'] .= self::PROTO_SEPARATOR.$options;
							return true;
						}
						break;
					}
					case 'icmp':
					case 'icmp4':
					case 'icmp6':
					{
						$icmpVersion = (substr($this->_datas['protocol'], -1, 1) === '6') ? (6) : (4);

						if($this->_isValidIcmpOptions($icmpVersion, $options)) {
							$this->_datas['protocol'] .= self::PROTO_SEPARATOR.$options;
							return true;
						}
						break;
					}
				}
			}

			return false;
		}

		protected function _isValidTcpUdpPorts($ports)
		{
			$ports = explode(self::PROTO_RANGE_SEPARATOR, $ports, 2);

			foreach($ports as $port)
			{
				if(!(C\Tools::is('int&&>0', $port) && $port < 65535)) {
					return false;
				}
			}

			return (!isset($ports[1]) || $ports[0] < $ports[1]);
		}

		protected function _isValidIcmpOptions($icmpVersion, $options)
		{
			if($options !== null)
			{
				switch($icmpVersion)
				{
					case 4: {
						$lastType = 255;
						$lastCode = 254;
						break;
					}
					case 6: {
						$lastType = 255;
						$lastCode = 161;
						break;
					}
					default: {
						return false;
					}
				}

				$typeCode = explode(self::PROTO_OPTIONS_SEPARATOR, $options, 2);

				return (
					C\Tools::is('int&&>=0', $typeCode[0]) && $typeCode[0] <= $lastType &&
					(!isset($typeCode[1]) || (C\Tools::is('int&&>=0', $typeCode[1]) && $typeCode[1] <= $lastCode))
				);
			}
			else {
				return true;
			}
		}

		public function isValid($returnInvalidAttributes = false)
		{		
			$tests = array(
				array(self::FIELD_NAME => 'string&&!empty'),
				array('protocol' => 'string&&!empty'),
			);

			return $this->_isValid($tests, $returnInvalidAttributes);
		}

		/**
		  * Gets protocol name only
		  *
		  * @return null|string
		  */
		public function getProtocolName()
		{
			$parts = explode(self::PROTO_OPTIONS_SEPARATOR, $this->_datas['protocol'], 2);
			return $parts[0];
		}

		/**
		  * Gets protocol options only
		  *
		  * @return null|string
		  */
		public function getProtocolOptions()
		{
			$parts = explode(self::PROTO_OPTIONS_SEPARATOR, $this->_datas['protocol'], 2);
			return (count($parts) === 2) ? ($parts[1]) : (null);
		}

		public function includes(Protocol $protocolApi)
		{
			return $this->_includes($protocolApi, false);
		}

		public function overlap(Protocol $protocolApi)
		{
			return $this->_includes($protocolApi, true);
		}

		protected function _includes(Protocol $protocolApi, $overlap = false)
		{
			$selfProtoName = $this->protocolName;
			$otherProtoName = $protocolApi->protocolName;

			if($selfProtoName === self::PROTO_NAME_TCP_UDP_ICMP || $otherProtoName === self::PROTO_NAME_TCP_UDP_ICMP)
			{
				$protocols = array('tcp', 'udp', 'icmp', 'icmp4', 'icmp6');

				return (
					$selfProtoName === $otherProtoName ||
					in_array($selfProtoName, $protocols) ||
					in_array($otherProtoName, $protocols)
				);
			}
			elseif($selfProtoName === $otherProtoName)
			{
				$selfProtoOptions = $this->protocolOptions;
				$otherProtoOptions = $protocolApi->protocolOptions;

				if($selfProtoOptions === $otherProtoOptions) {
					return true;
				}
				else
				{
					$selfProtoOptions = explode(self::PROTO_RANGE_SEPARATOR, $selfProtoOptions, 2);
					$otherProtoOptions = explode(self::PROTO_RANGE_SEPARATOR, $otherProtoOptions, 2);

					$selfProtoOptions = array_pad($selfProtoOptions, 2, $selfProtoOptions[0]);
					$otherProtoOptions = array_pad($otherProtoOptions, 2, $otherProtoOptions[0]);

					if(!$overlap) {
						return ($selfProtoOptions[0] <= $otherProtoOptions[0] && $selfProtoOptions[1] >= $otherProtoOptions[1]);
					}
					else
					{
						return (
							($selfProtoOptions[0] <= $otherProtoOptions[0] && $selfProtoOptions[1] >= $otherProtoOptions[1]) ||
							($selfProtoOptions[0] >= $otherProtoOptions[0] && $selfProtoOptions[1] <= $otherProtoOptions[1]) ||
							($selfProtoOptions[0] <= $otherProtoOptions[0] && $selfProtoOptions[1] <= $otherProtoOptions[1] && $selfProtoOptions[1] >= $otherProtoOptions[0]) ||
							($selfProtoOptions[0] >= $otherProtoOptions[0] && $selfProtoOptions[1] >= $otherProtoOptions[1] && $selfProtoOptions[0] <= $otherProtoOptions[1])
						);
					}
				}
			}

			return false;
		}

		/**
		  * @param $name string
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'proto':
				case 'protocolName': {
					return $this->getProtocolName();
				}
				case 'options':
				case 'protocolOptions': {
					return $this->getProtocolOptions();
				}
				default: {
					return parent::__get($name);
				}
			}
		}

		/**
		  * @return array
		  */
		public function sleep()
		{
			$datas = parent::sleep();
			$datas['protocol'] = $this->protocol;

			return $datas;
		}

		/**
		  * @param $datas array
		  * @return bool
		  */
		public function wakeup(array $datas)
		{
			$parentStatus = parent::wakeup($datas);
			$protocolStatus = $this->protocol($datas['protocol']);

			return ($parentStatus && $protocolStatus);
		}
	}