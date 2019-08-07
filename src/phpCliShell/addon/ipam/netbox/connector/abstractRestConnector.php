<?php
	namespace PhpCliShell\Addon\Ipam\Netbox\Connector;

	use ArrayObject;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Netbox\Api;
	use PhpCliShell\Addon\Ipam\Netbox\Tools;
	use PhpCliShell\Addon\Ipam\Netbox\Service;
	use PhpCliShell\Addon\Ipam\Netbox\Exception;

	abstract class AbstractRestConnector extends AbstractConnector implements InterfaceRestConnector
	{
		/**
		  * @var string
		  */
		const LABEL = 'UNKNOWN';

		/**
		  * @var string
		  */
		const METHOD = 'REST';

		/**
		  * @var array
		  */
		const REST_URN = array();

		/**
		  * @var string
		  */
		const SECTION_ROOT_ID = 0;

		/**
		  * @var string
		  */
		const FOLDER_ROOT_ID = 0;

		/**
		  * @var string
		  */
		const SUBNET_ROOT_ID = 0;

		/**
		  * IPAM server URL
		  * @var string
		  */
		protected $_server;

		/**
		  * Core\Rest API
		  * @var \ArrayObject
		  */
		protected $_restAPI;


		/**
		  * @param \PhpCliShell\Addon\Ipam\Netbox\Service $service
		  * @param \PhpCliShell\Core\Config $config
		  * @param string $server
		  * @param string $token
		  * @param bool $debug
		  * @return $this
		  */
		public function __construct(Service $service, C\Config $config, $server, $token, $debug = false)
		{
			parent::__construct($service, $config, $debug);

			$this->_server = rtrim($server, '/');
			$this->_restAPI = new ArrayObject();

			$httpProxy = getenv('http_proxy');
			$httpsProxy = getenv('https_proxy');

			foreach(static::REST_URN as $key => $urn) {
				$this->_initRestAPI($key, $this->_server, $urn, $httpProxy, $httpsProxy);
				$this->_restAPI->{$key}->addHeader('Authorization: Token '.$token);
			}
		}

		protected function _initRestAPI($key, $server, $urn, $httpProxy, $httpsProxy)
		{
			$server = $server.'/api/'.$urn;
			$this->_restAPI->{$key} = new C\Rest($server, 'NETBOX_'.$key, $this->_debug);

			$this->_restAPI->{$key}
					->forceSlashEndUrr(true)
					->setOpt(CURLOPT_HEADER, false)
					->setOpt(CURLOPT_RETURNTRANSFER, true)
					->setOpt(CURLOPT_FOLLOWLOCATION, true)
					->addHeader('Content-Type: application/json');

			switch(substr($server, 0, 6))
			{
				case 'http:/':
				{
					if(C\Tools::is('string&&!empty', $httpProxy))
					{
						$this->_restAPI->{$key}
								//->setHttpAuthMethods(true)	// NE PAS UTILISER EN HTTP!
								->setOpt(CURLOPT_HTTPPROXYTUNNEL, true)
								->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_HTTP)
								->setOpt(CURLOPT_PROXY, $httpProxy);
					}
					break;
				}
				case 'https:':
				{
					if(C\Tools::is('string&&!empty', $httpsProxy))
					{
						$this->_restAPI->{$key}
								->setHttpAuthMethods(true)
								->setOpt(CURLOPT_HTTPPROXYTUNNEL, true)
								->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_HTTP)
								->setOpt(CURLOPT_PROXY, $httpsProxy);
					}
					break;
				}
				default: {
					throw new Exception("L'adresse du serveur NetBox doit commencer par http ou https", E_USER_ERROR);
				}
			}
		}

		/**
		  * @param int|string $subnet Subnet ID or prefix
		  * @param null|int $vrfId Subnet VRF ID
		  * @param null|int $folderId Folder ID
		  * @return false|null|int Subnet ID
		  */
		abstract protected function _getParentSubnetId($subnet, $vrfId = null, $folderId = null);

		/**
		  * @param int|string $address Address ID or IP (with mask)
		  * @param null|int $vrfId Address VRF ID
		  * @return false|null|int Address ID
		  */
		abstract protected function _getAddressSubnetId($address, $vrfId = null);

		// ============ TOOL =============
		/**
		  * @param \PhpCliShell\Core\Rest $restAPI
		  * @param null|array $args
		  * @return false|array
		  */
		protected function _get(C\Rest $restAPI, $args = null)
		{
			if(is_array($args) && !array_key_exists('limit', $args)) {
				$args['limit'] = 100;
			}

			$result = $restAPI->get($args);

			if(($httpCode = $restAPI->getHttpCode()) === 200)
			{
				$response = json_decode($result, true);

				if($this->_isValidResponse($response))
				{
					if(array_key_exists('count', $response) && array_key_exists('results', $response))
					{
						if($response['next'] !== null)
						{
							$query = parse_url($response['next'], PHP_URL_QUERY);

							if($query !== false)
							{
								$results = $this->_getCallResponse($restAPI, $query);

								if($results !== false) {
									$response = array_merge($response['results'], $results);
								}
								else {
									return false;
								}
							}
							else {
								return false;
							}
						}
						else {
							$response = $response['results'];
						}

						return $this->_formatResponseItems($response);
					}
					else {
						return $this->_formatResponseItem($response);
					}
				}
			}
			elseif($this->_debug) {
				throw new Exception("REST API '".$restAPI->getService()."' error http '".$httpCode."': ".PHP_EOL.$result, E_USER_ERROR);
			}

			return false;
		}

		protected function _isValidResponse($response)
		{
			return (!$this->_isEmptyResponse($response) && !$this->_isErrorResponse($response));
		}

		protected function _isEmptyResponse($response)
		{
			return (C\Tools::is('string&&empty', $response) || C\Tools::is('array&&count==0', $response));
		}

		protected function _isErrorResponse($response)
		{
			return (
				!is_array($response)
			);
		}

		protected function _formatResponseItems(array $items)
		{
			foreach($items as &$item) {
				$item = $this->_formatResponseItem($item);
			}
			unset($item);

			return $items;
		}

		protected function _formatResponseItem(array $item)
		{
			foreach($item as $key => $data)
			{
				if(is_array($data))
				{
					foreach($data as $index => $value) {
						$item[$key.'_'.$index] = $value;
					}

					/**
					  * Ne pas supprimer pour:
					  * - Pour JQ, l'utilisateur
					  * - Pour les tests, le système
					  */					  
					//unset($item[$key]);
				}
			}

			return $item;
		}

		// ----------- FILTER ------------
		/**
		  * @param false|array $folders Folders
		  * @param null|string $folderName Folder name
		  * @param int $sectionId Section ID
		  * @param bool $strict
		  * @return false|array Folders
		  */
		protected function _filterFolders($folders, $folderName, $sectionId, $strict = false)
		{
			if(C\Tools::is('array&&count>0', $folders))
			{
				$folders = $this->_nameFilter($folders, Api\Folder::FIELD_NAME, $folderName, Api\Folders::WILDCARD, $strict);

				if(C\Tools::is('int&&>0', $sectionId))
				{
					foreach($folders as $index => $folder)
					{
						$folderSectionId = $folder[Api\Folder::FIELD_SECTION_ID];

						/**
						  * Si des folders sans section sont retournés alors il faut les garder
						  *
						  * Ici on ne filtre que le "défaut" de Netbox qui retourne
						  * les dossiers des sections enfants de la section souhaitée
						  */
						if($folderSectionId !== null && $folderSectionId !== $sectionId) {
							unset($folders[$index]);
						}
					}
				}

				$folders = array_values($folders);
			}

			return $folders;
		}

		/**
		  * @param false|array $subnets Subnets
		  * @param null|string $subnetName Subnet name
		  * @param bool $strict
		  * @return false|array Subnets
		  */
		protected function _filterSubnets($subnets, $subnetName, $strict = false)
		{
			if(C\Tools::is('array&&count>0', $subnets)) {
				$subnets = $this->_nameFilter($subnets, Api\Subnet::FIELD_NAME, $subnetName, Api\Subnets::WILDCARD, $strict);
			}

			return $subnets;
		}

		/**
		  * @param false|array $subnets Subnets
		  * @param int $subnetId Subnet ID
		  * @return false|array Subnets
		  */
		protected function _filterSubnetsWithParent($subnets, $subnetId)
		{
			if(C\Tools::is('array&&count>0', $subnets) && C\Tools::is('int&&>0', $subnetId))
			{
				foreach($subnets as $index => $subnet)
				{
					if($subnet[Api\Subnet::FIELD_ID] === (int) $subnetId) {
						unset($subnets[$index]);
					}
				}

				$subnets = array_values($subnets);
			}

			return $subnets;
		}

		/**
		  * @param false|array $subnets Subnets
		  * @param int $subnetId Subnet ID
		  * @param int $folderId Folder ID
		  * @return false|array Subnets
		  */
		protected function _filterSubnetsWithParents($subnets, $subnetId, $folderId)
		{
			if(C\Tools::is('array&&count>0', $subnets) && (C\Tools::is('int&&>=0', $subnetId) || C\Tools::is('int&&>=0', $folderId)))
			{
				$subnetsA = $subnetsB = $subnets;

				foreach($subnetsA as $indexA => $subnetA)
				{
					foreach($subnetsB as $indexB => $subnetB)
					{
						$subnetB['vrf_id'] = ($subnetB['vrf'] === null) ? (null) : ($subnetB['vrf']['id']);

						if($indexA !== $indexB && $subnetA['vrf_id'] === $subnetB['vrf_id'] &&
							Tools::subnetInSubnet($subnetB['prefix'], $subnetA['prefix']))
						{
							// B inside A so we unset B in both subnets (A and B)
							unset($subnetsA[$indexB], $subnetsB[$indexB]);
						}
					}
				}

				$subnets = array_values($subnetsB);
			}

			return $subnets;
		}

		/**
		  * @param false|array $addresses Addresses
		  * @param null|string $addressName Address hostname
		  * @param null|string $addressDesc Address description
		  * @param bool $strict
		  * @return false|array Addresses
		  */
		protected function _filterAddresses($addresses, $addressName, $addressDesc, $strict = false)
		{
			if(C\Tools::is('array&&count>0', $addresses)) {
				$addresses = $this->_nameFilter($addresses, Api\Address::FIELD_NAME, $addressName, Api\Addresses::WILDCARD, $strict);
				$addresses = $this->_nameFilter($addresses, Api\Address::FIELD_DESC, $addressDesc, Api\Addresses::WILDCARD, $strict);
			}

			return $addresses;
		}

		/**
		  * @param false|array $addresses Addresses
		  * @param string $cidrSubnet Parent subnet CIDR
		  * @return false|array Addresses
		  */
		protected function _filterAddressesWithMask($addresses, $cidrSubnet)
		{
			if(C\Tools::is('array&&count>0', $addresses) && Tools::isSubnet($cidrSubnet))
			{
				$subnetParts = explode('/', $cidrSubnet, 2);
				$sMask = '/'.$subnetParts[1];
				$lMask = strlen($sMask);

				foreach($addresses as $index => $address)
				{
					if(substr($address['address'], -$lMask) !== $sMask) {
						unset($address[$index]);
					}
				}

				$addresses = array_values($addresses);
			}

			return $addresses;
		}

		/**
		  * @param array $items Items to filter
		  * @param string $field Field to match
		  * @param string $name Name to match
		  * @param string $wildcard
		  * @param bool $strict
		  * @return array Items
		  */
		protected function _nameFilter($items, $field, $name, $wildcard = '*', $strict = false)
		{
			if(!$strict && $name !== null && $name !== $wildcard)
			{
				$wc = preg_quote($wildcard, '#');
				$regex = preg_quote($name, '#');
				$regex = str_replace($wc, '.*', $name);

				foreach($items as $index => $item)
				{
					if(!preg_match('#^('.$regex.')#i', $item[$field])) {
						unset($items[$index]);
					}
				}

				$items = array_values($items);
			}

			return $items;
		}
		// -------------------------------

		// ----------- FORMAT ------------
		/**
		  * @param false|array $sections Sections
		  * @return false|array Sections
		  */
		protected function _formatSections($sections)
		{
			if(is_array($sections))
			{
				foreach($sections as &$section) {
					$section = $this->_formatSection($section);
				}
				unset($section);
			}

			return $sections;
		}

		/**
		  * @param false|array $section Section
		  * @return false|array Section
		  */
		protected function _formatSection($section)
		{
			if($section !== false)
			{
				if($section['parent'] === null) {
					$section['parent'] = array('id' => self::SECTION_ROOT_ID);
					$section[Api\Section::FIELD_PARENT_ID] = self::SECTION_ROOT_ID;
				}
			}

			return $section;
		}

		/**
		  * @param false|array $folders Folders
		  * @return false|array Folders
		  */
		protected function _formatFolders($folders)
		{
			if(is_array($folders))
			{
				foreach($folders as &$folder) {
					$folder = $this->_formatFolder($folder);
				}
				unset($folder);
			}

			return $folders;
		}

		/**
		  * @param false|array $folder Folder
		  * @return false|array Folder
		  */
		protected function _formatFolder($folder)
		{
			if($folder !== false)
			{
				$folder['parent'] = array('id' => self::FOLDER_ROOT_ID);
				$folder[Api\Folder::FIELD_PARENT_ID] = self::FOLDER_ROOT_ID;

				if($folder['region'] === null) {
					$folder[Api\Folder::FIELD_SECTION_ID] = null;
				}
			}

			return $folder;
		}

		/**
		  * @param false|array $subnets Subnets
		  * @param null|int $parentSubnetId Parent subnet ID
		  * @return false|array Subnets
		  */
		protected function _formatSubnets($subnets, $parentSubnetId = null)
		{
			if(is_array($subnets))
			{
				foreach($subnets as &$subnet) {
					$subnet = $this->_formatSubnet($subnet, $parentSubnetId);
				}
				unset($subnet);
			}

			return $subnets;
		}

		/**
		  * @param false|array $subnet Subnet
		  * @param null|int $parentSubnetId Parent subnet ID
		  * @return false|array Subnet
		  */
		protected function _formatSubnet($subnet, $parentSubnetId = null)
		{
			if($subnet !== false)
			{
				if($subnet['site'] === null) {
					$subnet[Api\Subnet::FIELD_FOLDER_ID] = null;
				}

				if($subnet['vrf'] === null) {
					$subnet['vrf_id'] = null;
					$subnet['vrf_name'] = null;
				}

				if(!C\Tools::is('int&&>=0', $parentSubnetId))
				{
					$parentSubnetId = $this->_getParentSubnetId($subnet['prefix'], $subnet['vrf_id'], $subnet[Api\Subnet::FIELD_FOLDER_ID]);

					if($parentSubnetId === false) {
						$eMessage = "'".$subnet[Api\Subnet::FIELD_NAME]."' VRF '".$subnet['vrf_name']."' prefix '".$subnet['prefix']."'";
						throw new Exception("Unable to retrieve the parent subnet ID for subnet ".$eMessage, E_USER_ERROR);
					}
					elseif($parentSubnetId === null) {
						$parentSubnetId = self::SUBNET_ROOT_ID;
					}
				}

				$subnet['parent'] = array('id' => $parentSubnetId);
				$subnet[Api\Subnet::FIELD_PARENT_ID] = $parentSubnetId;
			}

			return $subnet;
		}

		/**
		  * @param false|array $vlans Vlans
		  * @return false|array Vlans
		  */
		protected function _formatVlans($vlans)
		{
			return $vlans;
		}

		/**
		  * @param false|array $vlan Vlan
		  * @return false|array Vlan
		  */
		protected function _formatVlan($vlan)
		{
			return $vlan;
		}

		/**
		  * @param false|array $addresses Addresses
		  * @param null|int $subnetId Subnet ID
		  * @return false|array Addresses
		  */
		protected function _formatAddresses($addresses, $subnetId = null)
		{
			if(is_array($addresses))
			{
				foreach($addresses as &$address) {
					$address = $this->_formatAddress($address, $subnetId);
				}
				unset($address);
			}

			return $addresses;
		}

		/**
		  * @param false|array $address Address
		  * @param null|int $subnetId Subnet ID
		  * @return false|array Address
		  */
		protected function _formatAddress($address, $subnetId = null)
		{
			if($address !== false)
			{
				if($address['vrf'] === null) {
					$address['vrf_id'] = null;
					$address['vrf_name'] = null;
				}

				if(!C\Tools::is('int&&>0', $subnetId))
				{
					$subnetId = $this->_getAddressSubnetId($address['address'], $address['vrf_id']);

					if($subnetId === false) {
						$eMessage = "'".$address[Api\Address::FIELD_NAME]."' VRF '".$subnet['vrf_name']."' IP '".$address['address']."'";
						throw new Exception("Unable to retrieve the subnet ID for address ".$eMessage, E_USER_ERROR);
					}
					elseif($subnetId === null) {
						$eMessage = "'".$address[Api\Address::FIELD_NAME]."' VRF '".$subnet['vrf_name']."' IP '".$address['address']."'";
						throw new Exception("Unable to retrieve the subnet ID for address ".$eMessage, E_USER_ERROR);
					}
				}

				/**
				  * /!\ $subnetId can be equal to null
				  * Netbox allow to create address without subnet
				  */
				$address[Api\Address::FIELD_SUBNET_ID] = $subnetId;
				$address['address'] = $this->_formatIP($address['address']);
			}

			return $address;
		}

		/**
		  * @param string $IP IP address with CIDR mask
		  * @return string IP address
		  */
		protected function _formatIP($IP)
		{
			return current(explode('/', $IP, 2));
		}
		// -------------------------------
		// ===============================

		/**
		 * @param bool $debug
		 * @return $this
		 */
		public function debug($debug = true)
		{
			$this->_debug = (bool) $debug;

			foreach(static::REST_URN as $key => $urn)
			{
				if(isset($this->_restAPI->{$key})) {
					$this->_restAPI->{$key}->debug($this->_debug);
				}
			}

			return $this;
		}

		public function close()
		{
			foreach(static::REST_URN as $key => $urn)
			{
				if(isset($this->_restAPI->{$key})) {
					$this->_restAPI->{$key}->close();
					unset($this->_restAPI->{$key});
				}
			}
			return $this;
		}

		public function __destruct()
		{
			$this->close();
		}
	}