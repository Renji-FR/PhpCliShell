<?php
	namespace PhpCliShell\Core\Network;

	use PhpCliShell\Core as C;

	abstract class Tools
	{
		const PROTOCOLS = array(
			0 => 'HOPOPT',
			1 => 'ICMP',
			2 => 'IGMP',
			3 => 'GGP',
			4 => 'IPv4',
			5 => 'ST',
			6 => 'TCP',
			7 => 'CBT',
			8 => 'EGP',
			9 => 'IGP',
			10 => 'BBN-RCC-MON',
			11 => 'NVP-II',
			12 => 'PUP',
			14 => 'EMCON',
			15 => 'XNET',
			16 => 'CHAOS',
			17 => 'UDP',
			18 => 'MUX',
			19 => 'DCN-MEAS',
			20 => 'HMP',
			21 => 'PRM',
			22 => 'XNS-IDP',
			23 => 'TRUNK-1',
			24 => 'TRUNK-2',
			25 => 'LEAF-1',
			26 => 'LEAF-2',
			27 => 'RDP',
			28 => 'IRTP',
			29 => 'ISO-TP4',
			30 => 'NETBLT',
			31 => 'MFE-NSP',
			32 => 'MERIT-INP',
			33 => 'DCCP',
			34 => '3PC',
			35 => 'IDPR',
			36 => 'XTP',
			37 => 'DDP',
			38 => 'IDPR-CMTP',
			39 => 'TP++',
			40 => 'IL',
			41 => 'IPv6',
			42 => 'SDRP',
			43 => 'IPv6-Route',
			44 => 'IPv6-Frag',
			45 => 'IDRP',
			46 => 'RSVP',
			47 => 'GRE',
			48 => 'DSR',
			49 => 'BNA',
			50 => 'ESP',
			51 => 'AH',
			52 => 'I-NLSP',
			54 => 'NARP',
			55 => 'MOBILE',
			56 => 'TLSP',
			57 => 'SKIP',
			58 => 'IPv6-ICMP',
			59 => 'IPv6-NoNxt',
			60 => 'IPv6-Opts',
			62 => 'CFTP',
			64 => 'SAT-EXPAK',
			65 => 'KRYPTOLAN',
			66 => 'RVD',
			67 => 'IPPC',
			69 => 'SAT-MON',
			70 => 'VISA',
			71 => 'IPCV',
			72 => 'CPNX',
			73 => 'CPHB',
			74 => 'WSN',
			75 => 'PVP',
			76 => 'BR-SAT-MON',
			77 => 'SUN-ND',
			78 => 'WB-MON',
			79 => 'WB-EXPAK',
			80 => 'ISO-IP',
			81 => 'VMTP',
			82 => 'SECURE-VMTP',
			83 => 'VINES',
			84 => 'TTP',
			84 => 'IPTM',
			85 => 'NSFNET-IGP',
			86 => 'DGP',
			87 => 'TCF',
			88 => 'EIGRP',
			89 => 'OSPFIGP',
			90 => 'Sprite-RPC',
			91 => 'LARP',
			92 => 'MTP',
			93 => 'AX.25',
			94 => 'IPIP',
			96 => 'SCC-SP',
			97 => 'ETHERIP',
			98 => 'ENCAP',
			100 => 'GMTP',
			101 => 'IFMP',
			102 => 'PNNI',
			103 => 'PIM',
			104 => 'ARIS',
			105 => 'SCPS',
			106 => 'QNX',
			107 => 'A/N',
			108 => 'IPComp',
			109 => 'SNP',
			110 => 'Compaq-Peer',
			111 => 'IPX-in-IP',
			112 => 'VRRP',
			113 => 'PGM',
			115 => 'L2TP',
			116 => 'DDX',
			117 => 'IATP',
			118 => 'STP',
			119 => 'SRP',
			120 => 'UTI',
			121 => 'SMP',
			123 => 'PTP',
			124 => 'ISIS-over-IPv4',
			125 => 'FIRE',
			126 => 'CRTP',
			127 => 'CRUDP',
			128 => 'SSCOPMCE',
			129 => 'IPLT',
			130 => 'SPS',
			131 => 'PIPE',
			132 => 'SCTP',
			133 => 'FC',
			134 => 'RSVP-E2E-IGNORE',
			135 => 'Mobility-Header',
			136 => 'UDPLite',
			137 => 'MPLS-in-IP',
			138 => 'Manet',
			139 => 'HIP',
			140 => 'Shim6',
			141 => 'WESP',
			142 => 'ROHC',
		);


		public static function isIP($ip)
		{
			return (self::isIPv4($ip) || self::isIPv6($ip));
		}

		public static function isIPv($ip, $version)
		{
			if($version === 4) {
				return self::isIPv4($ip);
			}
			elseif($version === 6) {
				return self::isIPv6($ip);
			}
			else {
				return false;
			}
		}

		public static function isIPv4($ip)
		{
			return C\Tools::is('ipv4', $ip);
		}

		public static function isIPv6($ip)
		{
			return C\Tools::is('ipv6', $ip);
		}

		public static function isSubnet($subnet)
		{
			return (self::isSubnetV4($subnet) || self::isSubnetV6($subnet));
		}

		public static function isSubnetV($subnet, $version)
		{
			if($version === 4) {
				return self::isSubnetV4($subnet);
			}
			elseif($version === 6) {
				return self::isSubnetV6($subnet);
			}
			else {
				return false;
			}
		}

		public static function isSubnetV4($subnet)
		{
			if(C\Tools::is('string&&!empty', $subnet))
			{
				$subnetParts = explode('/', $subnet);

				// Be careful ::ffff:127.0.0.1 notation is valid
				//return (substr_count($subnet, '.') === 3 && strpos($subnet, ':') === false);
				return (count($subnetParts) === 2 && self::isIPv4($subnetParts[0]) && $subnetParts[1] >= 0 && $subnetParts[1] <= 32);
			}
			else {
				return false;
			}
		}

		public static function isSubnetV6($subnet)
		{
			if(C\Tools::is('string&&!empty', $subnet))
			{
				$subnetParts = explode('/', $subnet, 2);

				//return (strpos($subnet, ':') !== false);
				return (count($subnetParts) === 2 && self::isIPv6($subnetParts[0]) && $subnetParts[1] >= 0 && $subnetParts[1] <= 128);
			}
			else {
				return false;
			}
		}

		public static function isNetwork($network, $separator = '-')
		{
			return (self::isNetworkV4($network, $separator) || self::isNetworkV6($network, $separator));
		}

		public static function isNetworkV4($network, $separator = '-')
		{
			$networkParts = explode($separator, $network);

			if(count($networkParts) === 2)
			{
				return (
					self::isIPv4($networkParts[0]) &&
					self::isIPv4($networkParts[1]) &&
					strnatcasecmp($networkParts[0], $networkParts[1]) <= 0
				);
			}
			else {
				return false;
			}
		}

		public static function isNetworkV6($network, $separator = '-')
		{
			$networkParts = explode($separator, $network);

			if(count($networkParts) === 2)
			{
				return (
					self::isIPv6($networkParts[0]) &&
					self::isIPv6($networkParts[1]) &&
					strnatcasecmp($networkParts[0], $networkParts[1]) <= 0
				);
			}
			else {
				return false;
			}
		}

		public static function formatIPv6($IPv6)
		{
			if(C\Tools::is('ipv6', $IPv6))
			{
				if(defined('AF_INET6')) {
					/**
					  * To loweer case: Ff:: => ff::
					  * Format: 0:0:0:0:0:0:0:0: => ::
					  */
					return inet_ntop(inet_pton($IPv6));
				}
				else {
					return $IPv6;
				}
			}
			else {
				return false;
			}
		}

		public static function cidrMatch($ip, $subnet)
		{
			list($subnet, $mask) = explode('/', $subnet);

			if(($subnet === '0.0.0.0' || self::formatIPv6($subnet) === '::') && $mask === '0') {
				return true;
			}
			else {
				$ip = self::networkIp($ip, $mask);
				$subnet = self::networkIp($subnet, $mask);
				return ($ip !== false && $subnet !== false && $ip === $subnet);
			}
		}

		// /!\ is $a inside $b ?
		public static function subnetInSubnet($a, $b)
		{
			if($a === $b) {
				return true;
			}
			else
			{
				list($ip, $mask) = explode('/', $a);
				list($_ip, $_mask) = explode('/', $b);

				if($mask < $_mask) {
					return false;
				}
				else
				{
					$ip = self::networkIp($ip, $mask);

					if($ip !== false) {
						return self::cidrMatch($ip, $b);
					}
					else {
						return false;
					}
				}
			}
		}

		public static function IPv4ToLong($ip)
		{
			return ip2long($ip);
		}

		public static function longIpToIPv4($longIp)
		{
			return long2ip((float) $longIp);
		}

		public static function IpToBin($ip)
		{
			return (defined('AF_INET6')) ? (inet_pton($ip)) : (false);
		}

		public static function binToIp($ip)
		{
			return (defined('AF_INET6')) ? (inet_ntop($ip)) : (false);
		}

		/**
		  * @param int $cidrMask
		  * @param null|int $IPv IP version 4 or 6 (magic parameter, allow null to best IP version detection)
		  * @return false|null|string Return false if error occured, null if IPv6 detected else return net mask
		  */
		public static function cidrMaskToNetMask($cidrMask, $IPv = null)
		{
			if($IPv !== 6)
			{
				if($cidrMask >= 0 && $cidrMask <= 32) {
					return long2ip(-1 << (32 - (int) $cidrMask));
				}
				else {
					return false;
				}
			}
			else {
				return null;
			}
		}

		public static function cidrMaskToBinary($cidrMask, $IPv)
		{
			if($IPv === 4 && $cidrMask >= 0 && $cidrMask <= 32)
			{
				if(defined('AF_INET6')) {
					$netMask = self::cidrMaskToNetMask($cidrMask);
					return inet_pton($netMask);
				}
				else {
					return (~((1 << (32 - $cidrMask)) - 1));
				}
			}
			elseif($IPv === 6 && $cidrMask >= 0 && $cidrMask <= 128)
			{
				$netMask = str_repeat("f", $cidrMask / 4);

				switch($cidrMask % 4)
				{
					case 0:
						break;
					case 1:
						$netMask .= "8";
						break;
					case 2:
						$netMask .= "c";
						break;
					case 3:
						$netMask .= "e";
						break;
				}

				$netMask = str_pad($netMask, 32, '0');
				$binMask = pack("H*", $netMask);

				return $binMask;
			}

			return false;
		}

		public static function netMaskToCidr($netMask)
		{
			if(self::isIPv4($netMask)) {
				$longMask = ip2long($netMask);
				$longBase = ip2long('255.255.255.255');
				return 32 - log(($longMask ^ $longBase)+1, 2);
			}
			else {
				return false;
			}
		}

		public static function firstSubnetIp($ip, $mask)
		{
			if(($isIPv4 = self::isIPv4($ip)) === true || ($isIPv6 = self::isIPv6($ip)) === true)
			{
				if(C\Tools::is('int&&>=0', $mask)) {
					$IPv = ($isIPv4) ? (4) : (6);
					$mask = self::cidrMaskToBinary($mask, $IPv);
				}
				elseif(defined('AF_INET6') && self::isIPv4($mask)) {
					$mask = inet_pton($mask);
				}
				else {
					$mask = false;
				}

				if($mask !== false)
				{
					// IPv4 & IPv6 compatible
					if(defined('AF_INET6')) {
						$ip = inet_pton($ip);
						return inet_ntop($ip & $mask);
					}
					// IPv4 only
					elseif($isIPv4) {
						$netIp = (ip2long($ip) & $mask);
						return long2ip($netIp);
					}
				}
			}

			return false;
		}

		public static function lastSubnetIp($ip, $mask)
		{
			if(($isIPv4 = self::isIPv4($ip)) === true || ($isIPv6 = self::isIPv6($ip)) === true)
			{
				if(C\Tools::is('int&&>=0', $mask)) {
					$IPv = ($isIPv4) ? (4) : (6);
					$mask = self::cidrMaskToBinary($mask, $IPv);
				}
				elseif(defined('AF_INET6') && self::isIPv4($mask)) {
					$mask = inet_pton($mask);
				}
				else {
					return false;
				}

				// IPv4 et IPv6 compatible
				if(defined('AF_INET6')) {
					$ip = inet_pton($ip);
					return inet_ntop($ip | ~ $mask);
				}
				// IPv4 only
				elseif($isIPv4) {
					$bcIp = (ip2long($ip) | (~ $mask));
					return long2ip($bcIp);
				}
			}

			return false;
		}

		public static function networkIp($ip, $mask)
		{
			return self::firstSubnetIp($ip, $mask);
		}

		public static function broadcastIp($ip, $mask)
		{
			if(self::isIPv4($ip)) {
				return self::lastSubnetIp($ip, $mask);
			}
			elseif(self::isIPv6($ip)) {
				return 'ff02::1';
			}
			else {
				return false;
			}
		}

		public static function networkSubnet($cidrSubnet)
		{
			$subnetPart = explode('/', $cidrSubnet);

			if(count($subnetPart) === 2)
			{
				$networkIp = self::firstSubnetIp($subnetPart[0], $subnetPart[1]);

				if($networkIp !== false) {
					return $networkIp.'/'.$subnetPart[1];
				}
			}

			return false;
		}

		/**
		  * @param int $protocolNumber
		  * @return false|int|string
		  */
		public static function protocolToName($protocolNumber)
		{
			if(array_key_exists($protocolNumber, self::PROTOCOLS)) {
				return (string) self::PROTOCOLS[$protocolNumber];
			}
			elseif(self::isValidProtocolNumber($protocolNumber)) {
				return $protocolNumber;
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $protocolName
		  * @return false|int
		  */
		public static function protocolToNumber($protocolName)
		{
			return array_search($protocolName, self::PROTOCOLS, true);
		}

		/**
		  * @param int $protocolNumber
		  * @return bool
		  */
		public static function isValidProtocolNumber($protocolNumber)
		{
			return (C\Tools::is('int', $protocolNumber) && $protocolNumber >= 0 && $protocolNumber <= 255);
		}
	}