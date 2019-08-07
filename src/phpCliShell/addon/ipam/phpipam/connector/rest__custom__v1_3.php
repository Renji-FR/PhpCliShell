<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Connector;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Addon\Ipam\Phpipam\Api;
	use PhpCliShell\Addon\Ipam\Phpipam\Tools;
	use PhpCliShell\Addon\Ipam\Phpipam\Service;
	use PhpCliShell\Addon\Ipam\Phpipam\Exception;

	class Rest__custom__v1_3 extends AbstractRestConnector
	{
		/**
		  * @var string
		  */
		const LABEL = 'custom_v1.3';

		/**
		  * @var array
		  */
		const REST_URN = array(
			'user' => 'user',
			'sections' => 'sections',
			'cwSections' => 'cw_sections',
			'folders' => 'folders',
			'subnets' => 'subnets',
			'cwSubnets' => 'cw_subnets',
			'addresses' => 'addresses',
			'cwAddresses' => 'cw_addresses',
			'vlans' => 'vlans',
			'cwVlans' => 'cw_vlans',
			'l2domains' => 'l2domains',
			'vrfs' => 'vrfs',
			'tools' => 'tools',
			'prefix' => 'prefix',
		);

		/**
		  * @todo a décommenter après correction bug PHPIPAM (version <= 1.3.1)
		  * curl -vvv -H "token: [token]" -H "Content-Type: application/json; charset=utf-8" https://ipam.corp.cloudwatt.com/api/myAPP/folders/1185/
		  * Fatal error</b>:  Unsupported operand types in <b>/opt/phpipam/functions/classes/class.Tools.php</b> on line <b>1695</b>
		  *
		  * @var array
		  */
		protected $__workaround__allFolders = null;


		// =========== READER ============
		/**
		  * @param string $objectType
		  * @param int $objectId
		  * @return false|string
		  */
		public function resolvToLabel($objectType, $objectId)
		{
			switch($objectType)
			{
				case Api\Section::OBJECT_TYPE:
					$restAPI = $this->_restAPI->sections;
					$fieldName = Api\Section::FIELD_NAME;
					break;
				case Api\Folder::OBJECT_TYPE:
					$restAPI = $this->_restAPI->folders;
					$fieldName = Api\Folder::FIELD_NAME;
					break;
				case Api\Subnet::OBJECT_TYPE:
					$restAPI = $this->_restAPI->subnets;
					$fieldName = Api\Subnet::FIELD_NAME;
					break;
				case Api\Vlan::OBJECT_TYPE:
					$restAPI = $this->_restAPI->vlans;
					$fieldName = Api\Vlan::FIELD_NAME;
					break;
				case Api\Address::OBJECT_TYPE:
					$restAPI = $this->_restAPI->addresses;
					$fieldName = Api\Address::FIELD_NAME;
					break;
				default:
					throw new Exception("Object type '".$objectType."' is unknown", E_USER_ERROR);
			}

			$result = $restAPI->{$objectId}->get();
			$response = $this->_getCallResponse($result);

			return ($response !== false) ? ($response[$fieldName]) : (false);
		}

		/**
		  * @param int $subnetId Subnet ID
		  * @return false|string Gateway IP address
		  */
		public function getGatewayBySubnetId($subnetId)
		{
			if(C\Tools::is('int&&>0', $subnetId))
			{
				$subnet = $this->_restAPI->subnets->{$subnetId}->get();
				$subnet = $this->_getCallResponse($subnet);

				if(isset($subnet['gateway']['ip_addr'])) {
					return $subnet['gateway']['ip_addr'];
				}
			}

			return false;
		}

		/**
		  * @param array $vlanIds VLAN number IDs
		  * @return array VLAN names
		  */
		public function getVlanNamesByVlanIds(array $vlanIds)
		{
			$vlanNames = array();

			foreach($vlanIds as $vlanId)
			{
				$vlans = $this->_restAPI->vlans->search->{$vlanId}->get();
				$vlans = $this->_getCallResponse($vlans);

				if($vlans !== false)
				{
					foreach($vlans as $vlan) {
						$vlanNames[$vlan[Api\Vlan::FIELD_NAME]] = $vlanId;
					}
				}
			}

			return $vlanNames;
		}

		// ----------- SECTION -----------
		/**
		  * @return false|array Sections
		  */
		public function getAllSections()
		{
			$sections = $this->_restAPI->sections->get();
			$sections = $this->_getCallResponse($sections);

			// {"code":200,"success":0,"message":"No sections available","time":0.014}
			if(C\Tools::is('array', $sections)) {
				return $sections;
			}
			else {
				return array();
			}
		}

		/**
		  * @return false|array Root sections
		  */
		public function getRootSections()
		{
			return $this->_getSections(static::SECTION_ROOT_ID);
		}

		/**
		  * @param int Parent section ID
		  * @return false|array Sections
		  */
		public function getSections($sectionId)
		{
			if(C\Tools::is('int&&>=0', $sectionId)) {
				return $this->_getSections($sectionId);
			}
			else {
				return false;
			}
		}

		/**
		  * @param int $sectionId Section ID
		  * @return false|array Sections
		  */
		public function getSection($sectionId)
		{
			if(C\Tools::is('int&&>0', $sectionId))
			{
				try {
					$section = $this->_restAPI->sections->{$sectionId}->get();
				}
				catch(C\Exception $e)
				{
					if($this->_restAPI->sections->getHttpCode() === 404) {
						return false;
					}
					else {
						throw $e;
					}
				}

				return $this->_getCallResponse($section);
			}
			else {
				return false;
			}
		}

		/**
		  * @param int $sectionId Section ID
		  * @return false|array Sections
		  */
		protected function _getSections($sectionId)
		{
			if(C\Tools::is('int&&>=0', $sectionId))
			{
				$args = array();
				$args[Api\Section::FIELD_PARENT_ID] = $sectionId;

				$sections = $this->_restAPI->cwSections->search->get($args);
				return $this->_getCallResponse($sections);
			}
			else {
				return false;
			}
		}
		// -------------------------------

		// ----------- FOLDER ------------
		/**
		  * @return array Folders
		  */
		public function getAllFolders()
		{
			$folders = $this->_restAPI->folders->all->get();
			$folders = $this->_getCallResponse($folders);

			if($folders !== false)
			{
				foreach($folders as $index => $folder)
				{
					/**
					  * L'API phpIPAM peut retourner des subnets avec les folders
					  * @todo corriger l'API ou passer par un controleur custom
					  */
					if($folder['isFolder'] === static::SUBNET_IS_NOT_FOLDER) {
						unset($folders[$index]);
					}
				}

				return array_values($folders);
			}
			else {
				return array();
			}
		}

		/**
		  * Un folder est un subnet avec isFolder = true
		  * Un folder a toujours comme parent une section, obligatoire
		  * Un folder peut être rattaché à un autre folder, non obligatoire
		  * Un folder root est donc un folder non rattaché à un autre folder
		  *
		  * @param int $sectionId Parent section ID
		  * @return false|array Folders
		  */
		public function getRootFolders($sectionId)
		{
			return $this->_getSubnets(null, static::FOLDER_ROOT_ID, $sectionId, true);
		}

		/**
		  * @param null|int $folderId Parent folder ID
		  * @param null|int $sectionId Parent section ID
		  * @return false|array Folders
		  */
		public function getFolders($folderId, $sectionId = null)
		{
			return $this->_getSubnets(null, $folderId, $sectionId, true);
		}

		/**
		  * @param int $folderId Folder ID
		  * @return false|array Folder
		  */
		public function getFolder($folderId)
		{
			return $this->_getSubnet($folderId, true);
		}
		// -------------------------------

		// ----------- SUBNET ------------
		/**
		  * @return array Subnets
		  */
		public function getAllSubnets()
		{
			$subnets = $this->_restAPI->subnets->all->get();
			$subnets = $this->_getCallResponse($subnets);

			if($subnets !== false)
			{
				foreach($subnets as $index => $subnet)
				{
					/**
					  * L'API IPAM retourne des dossiers et possiblement des subnets invalides
					  * @todo corriger l'API ou passer par un controleur custom
					  *
					  * subnet: null, ''
					  * mask: null, ''
					  */
					if($subnet['isFolder'] === static::SUBNET_IS_FOLDER || empty($subnet['subnet']) ||
						!($subnet['mask'] >= 0 && $subnet['mask'] <= 128))
					{
						unset($subnets[$index]);
					}
				}

				return array_values($subnets);
			}
			else {
				return array();
			}
		}

		/**
		  * Un subnet possède l'attribut isFolder = false
		  * Un subnet a toujours comme parent une section, obligatoire
		  * Un subnet peut être rattaché à un autre subnet (ou folder), non obligatoire
		  * Un subnet root est donc un subnet non rattaché à un autre subnet (ou folder)
		  *
		  * @param null|int $folderId Parent folder ID
		  * @param null|int $sectionId Parent section ID
		  * @return false|array Subnets
		  */
		public function getRootSubnets($folderId, $sectionId = null)
		{
			if(!C\Tools::is('int&&>=0', $folderId)) {
				$subnetId = static::SUBNET_ROOT_ID;
			}
			else {
				$subnetId = null;
			}

			return $this->_getSubnets($subnetId, $folderId, $sectionId, false);
		}

		/**
		  * @param null|int $subnetId Parent subnet ID
		  * @param null|int $folderId Parent folder ID
		  * @param null|int $sectionId Parent section ID
		  * @return false|array Subnets
		  */
		public function getSubnets($subnetId, $folderId = null, $sectionId = null)
		{
			return $this->_getSubnets($subnetId, $folderId, $sectionId, false);
		}

		/**
		  * @param int $subnetId Subnet ID
		  * @return false|array Subnet
		  */
		public function getSubnet($subnetId)
		{
			return $this->_getSubnet($subnetId, false);
		}

		/**
		  * @param int $vlanId VLAN ID
		  * @param false|int $sectionId Section ID
		  * @return false|array Subnets
		  */
		public function getSubnetsFromVlan($vlanId, $sectionId = false)
		{
			if(C\Tools::is('int&&>0', $vlanId))
			{
				$restAPI = $this->_restAPI->vlans->{$vlanId}->subnets;

				if(C\Tools::is('int&&>0', $sectionId)) {
					$restAPI = $restAPI->{$sectionId};
				}

				$subnets = $restAPI->get();
				$subnets = $this->_getCallResponse($subnets);

				// {"code":200,"success":0,"message":"No subnets found","time":0.014}
				if(C\Tools::is('array', $subnets)) {
					return $subnets;
				}
				else {
					return array();
				}
			}
			else {
				return false;
			}
		}

		/**
		  * @param int $subnetId Subnet ID
		  * @return false|array Subnet usage
		  */
		public function getSubnetUsage($subnetId)
		{
			if(C\Tools::is('int&&>0', $subnetId))
			{
				$subnetUsage = $this->_restAPI->subnets->{$subnetId}->usage->get();
				return $this->_getCallResponse($subnetUsage);
			}
			else {
				return false;
			}
		}
		// -------------------------------

		// ------- FOLDER / SUBNET -------
		/**
		  * @param null|int $subnetId Parent subnet ID
		  * @param null|int $folderId Parent folder ID
		  * @param null|int $sectionId Parent section ID
		  * @param bool $isFolder Return folders or subnets
		  * @return false|array Folders/Subnets
		  */
		protected function _getSubnets($subnetId, $folderId, $sectionId, $isFolder)
		{
			$args = array();

			// Search in subnet or not
			if(C\Tools::is('int&&>=0', $subnetId)) {
				$args['subnetId'] = $subnetId;
			}
			// Search in folder or not
			elseif(C\Tools::is('int&&>=0', $folderId)) {
				$args['subnetId'] = $folderId;
			}

			// Search in section or not
			if(C\Tools::is('int&&>=0', $sectionId)) {
				$args['sectionId'] = $sectionId;
			}

			$args['isFolder'] = ($isFolder) ? (static::SUBNET_IS_FOLDER) : (static::SUBNET_IS_NOT_FOLDER);

			$subnets = $this->_restAPI->cwSubnets->search->get($args);
			return $this->_getCallResponse($subnets);
		}

		/**
		  * @param null|int $subnetId Subnet or folder ID
		  * @param bool $isFolder Return folder or subnet
		  * @return false|array Folders/Subnets
		  */
		protected function _getSubnet($subnetId, $isFolder = false)
		{
			if(C\Tools::is('int&&>0', $subnetId))
			{
				if($isFolder === true)
				{
					/**
					  * @todo a décommenter après correction bug PHPIPAM (version <= 1.3.1)
					  * curl -vvv -H "token: [token]" -H "Content-Type: application/json; charset=utf-8" https://ipam.corp.cloudwatt.com/api/myAPP/folders/1185/
					  * Fatal error</b>:  Unsupported operand types in <b>/opt/phpipam/functions/classes/class.Tools.php</b> on line <b>1695</b>
					  */
					if($this->__workaround__allFolders === null) {
						$this->__workaround__allFolders = $this->getAllFolders();
					}

					foreach($this->__workaround__allFolders as $folder)
					{
						if((int) $folder[Api\Folder::FIELD_ID] === $subnetId) {
							return $folder;
						}
					}

					return false;

					//$restAPI = $this->_restAPI->folders;
				}
				else {
					$restAPI = $this->_restAPI->subnets;
				}

				try {
					$subnet = $restAPI->{$subnetId}->get();
				}
				catch(C\Exception $e)
				{
					if($restAPI->getHttpCode() === 404) {
						return false;
					}
					else {
						throw $e;
					}
				}

				return $this->_getCallResponse($subnet);
			}
			else {
				return false;
			}
		}
		// -------------------------------

		// ------------ VLAN -------------
		/**
		  * @return false|array VLANs
		  */
		public function getAllVlans()
		{
			$vlans = $this->_restAPI->vlans->get();
			return $this->_getCallResponse($vlans);
		}

		/**
		  * @param int $vlanId VLAN ID
		  * @return false|array VLAN
		  */
		public function getVlan($vlanId)
		{
			if(C\Tools::is('int&&>0', $vlanId)) {
				$vlan = $this->_restAPI->vlans->{$vlanId}->get();
				return $this->_getCallResponse($vlan);
			}
			else {
				return false;
			}
		}
		// -------------------------------

		// ----------- ADDRESS -----------
		/**
		  * @param int $subnetId Parent subnet ID
		  * @return false|array Addresses
		  */
		public function getAddresses($subnetId)
		{
			if(C\Tools::is('int&&>0', $subnetId)) {
				$addresses = $this->_restAPI->subnets->{$subnetId}->addresses->get();
				return $this->_getCallResponse($addresses);
			}
			else {
				return false;
			}
		}

		/**
		  * @param int $id Subnet or address ID
		  * @param null|string $ip Address IP
		  * @return false|array Address
		  */
		public function getAddress($id, $ip = null)
		{
			if(C\Tools::is('int&&>0', $id))
			{
				if($ip === null) {
					$address = $this->_restAPI->addresses->{$id}->get();
					return $this->_getCallResponse($address);
				}
				elseif(Tools::isIP($ip))
				{
					$address = $this->_restAPI->subnets->{$id}->addresses->{$ip}->get();
					$address = $this->_getCallResponse($address);

					if($address != false)
					{
						if(count($address) === 1) {
							return current($address);
						}
						else {
							throw new Exception("Multiple addresses returned for IP '".$ip."'", E_USER_ERROR);
						}
					}
				}
			}

			return false;
		}
		// -------------------------------

		// ----------- SEARCH ------------
		/**
		  * @param string $sectionName Section name (* or % is allowed)
		  * @param null|int $sectionId Parent section ID
		  * @param bool $strict
		  * @return false|array Sections
		  */
		public function searchSectionName($sectionName, $sectionId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $sectionName))
			{
				$args = array();

				if($strict) {
					$args[Api\Section::FIELD_NAME] = $sectionName;
				}
				// SQL LIKE % does not match null or empty string
				elseif($sectionName !== Api\Sections::WILDCARD) {
					$sectionName = rtrim($sectionName, Api\Sections::WILDCARD.'%');
					$sectionName = str_replace(Api\Sections::WILDCARD, '%', $sectionName);
					$args[Api\Section::FIELD_NAME] = '##like##'.$sectionName.'%';
				}

				// Search in section or not
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$args[Api\Section::FIELD_PARENT_ID] = $sectionId;		// @todo passer en subnetId dans Cw_sections.php
				}

				$sections = $this->_restAPI->cwSections->search->get($args);
				return $this->_getCallResponse($sections);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $subnetCidr Subnet CIDR
		  * @return false|array Subnets
		  */
		public function searchSubnets($subnetCidr)
		{
			if(Tools::isSubnet($subnetCidr))
			{
				$subnetCidr = explode('/', $subnetCidr, 2);
				$subnetRestApi = $this->_restAPI->subnets->cidr;

				foreach($subnetCidr as $subnetPart) {
					$subnetRestApi->{$subnetPart};
				}

				$subnets = $subnetRestApi->get();
				return $this->_getCallResponse($subnets);
			}
			else {
				return false;
			}
		}

		/**
		  * $strict for future use
		  *
		  * @param string $subnetCidr
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param null|int $sectionId
		  * @param bool $strict
		  * @return false|array Subnets
		  */
		public function searchSubnetCidr($subnetCidr, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			if(Tools::isSubnet($subnetCidr))
			{
				$args = array();

				list($args['subnet'], $args['mask']) = explode('/', $subnetCidr, 2);

				// Search in subnet or not
				if(C\Tools::is('int&&>=0', $subnetId)) {
					$args['subnetId'] = $subnetId;
				}
				// Search in folder or not
				elseif(C\Tools::is('int&&>=0', $folderId)) {
					$args['subnetId'] = $folderId;
				}

				// Search in section or not
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$args['sectionId'] = $sectionId;
				}

				$subnets = $this->_restAPI->cwSubnets->search->get($args);
				return $this->_getCallResponse($subnets);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $folderName
		  * @param null|int $folderId
		  * @param null|int $sectionId
		  * @param bool $strict
		  * @return false|array Folders
		  */
		public function searchFolderName($folderName, $folderId = null, $sectionId = null, $strict = false)
		{
			return $this->_searchSubnetName($folderName, null, null, $folderId, $sectionId, $strict, true);
		}

		/**
		  * @param string $subnetName
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param null|int $sectionId
		  * @param bool $strict
		  * @return false|array Subnets
		  */
		public function searchSubnetName($subnetName, $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			return $this->_searchSubnetName($subnetName, $IPv, $subnetId, $folderId, $sectionId, $strict, false);
		}

		/**
		  * @param string $name Folder/Subnet name
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId
		  * @param null|int $folderId
		  * @param null|int $sectionId
		  * @param bool $strict
		  * @param bool $isFolder
		  * @return false|array Folders/Subnets
		  */
		protected function _searchSubnetName($name, $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false, $isFolder = false)
		{
			if(C\Tools::is('string&&!empty', $name))
			{
				$args = array();
				
				if($isFolder) {
					$fieldName = Api\Folder::FIELD_NAME;
					$wildcard = Api\Folder::WILDCARD;
					$args['isFolder'] = static::SUBNET_IS_FOLDER;
				}
				else
				{
					$fieldName = Api\Subnet::FIELD_NAME;
					$wildcard = Api\Subnets::WILDCARD;
					$args['isFolder'] = static::SUBNET_IS_NOT_FOLDER;

					if($IPv === 4 || $IPv === 6) {
						$args['ip_version'] = $IPv;
					}
				}

				if($strict) {
					$args[$fieldName] = $name;
				}
				// SQL LIKE % does not match null or empty string
				elseif($name !== $wildcard) {
					$name = rtrim($name, $wildcard.'%');
					$name = str_replace($wildcard, '%', $name);
					$args[$fieldName] = '##like##'.$name.'%';
				}

				// Search in subnet or not
				if(C\Tools::is('int&&>=0', $subnetId)) {
					$args['subnetId'] = $subnetId;
				}
				// Search in folder or not
				elseif(C\Tools::is('int&&>=0', $folderId)) {
					$args['subnetId'] = $folderId;
				}

				// Search in section or not
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$args['sectionId'] = $sectionId;
				}

				$subnets = $this->_restAPI->cwSubnets->search->get($args);
				return $this->_getCallResponse($subnets);
			}
			else {
				return false;
			}
		}

		/**
		  * @param int $vlanNumber VLAN number
		  * @return false|array VLANs
		  */
		public function searchVlans($vlanNumber)
		{
			if(C\Tools::is('int&&>0', $vlanNumber)) {
				$vlans = $this->_restAPI->vlans->search->{$vlanNumber}->get();
				return $this->_getCallResponse($vlans);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $vlanName VLAN name
		  * @param bool $strict
		  * @return false|array VLANs
		  */
		public function searchVlanName($vlanName, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $vlanName))
			{
				$args = array();

				if($strict) {
					$args[Api\Vlan::FIELD_NAME] = $vlanName;
				}
				// SQL LIKE % does not match null or empty string
				elseif($vlanName !== Api\Vlans::WILDCARD) {
					$vlanName = rtrim($vlanName, Api\Vlans::WILDCARD.'%');
					$vlanName = str_replace(Api\Vlans::WILDCARD, '%', $vlanName);
					$args[Api\Vlan::FIELD_NAME] = '##like##'.$vlanName.'%';
				}

				$vlans = $this->_restAPI->cwVlans->search->get($args);
				return $this->_getCallResponse($vlans);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $IP IP address
		  * @return false|array IP addresses
		  */
		public function searchAddresses($ip)
		{
			if(Tools::isIP($ip))
			{
				$addresses = $this->_restAPI->addresses->search->{$ip}->get();
				$addresses = $this->_getCallResponse($addresses);

				// {"code":200,"success":0,"message":"Address not found","time":0.014}
				if(C\Tools::is('array', $addresses)) {
					return $addresses;
				}
				else {
					return array();
				}
			}
			else {
				return false;
			}
		}

		/**
		  * $strict for future use
		  *
		  * @param string $addressIP Address IP or wildcard *
		  * @param null|int $subnetId Parent subnet ID
		  * @param bool $strict
		  * @return array IP addresses
		  */
		public function searchAddressIP($addressIP, $subnetId = null, $strict = false)
		{
			// Allow * or IP address
			if(C\Tools::is('string&&!empty', $addressIP))
			{
				$args = array();

				if(Tools::isIP($addressIP)) {
					$args['ip'] = $addressIP;
				}

				if(C\Tools::is('int&&>0', $subnetId)) {
					$args['subnetId'] = $subnetId;
				}

				$addresses = $this->_restAPI->cwAddresses->search->get($args);
				return $this->_getCallResponse($addresses);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $addHostname Address hostname
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId Parent subnet ID
		  * @param bool $strict
		  * @return array IP addresses
		  */
		public function searchAddHostname($addHostname, $IPv = null, $subnetId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $addHostname))
			{
				$args = array();

				if($strict) {
					$args[Api\Address::FIELD_NAME] = $addHostname;
				}
				// SQL LIKE % does not match null or empty string
				elseif($addHostname !== Api\Addresses::WILDCARD) {
					$addHostname = rtrim($addHostname, Api\Addresses::WILDCARD.'%');
					$addHostname = str_replace(Api\Addresses::WILDCARD, '%', $addHostname);
					$args[Api\Address::FIELD_NAME] = '##like##'.$addHostname.'%';
				}

				if($IPv === 4 || $IPv === 6) {
					$args['ip_version'] = $IPv;
				}

				if(C\Tools::is('int&&>0', $subnetId)) {
					$args['subnetId'] = $subnetId;
				}

				$addresses = $this->_restAPI->cwAddresses->search->get($args);
				return $this->_getCallResponse($addresses);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $addDescription Address description
		  * @param null|int $IPv IP version, 4 or 6
		  * @param null|int $subnetId Parent subnet ID
		  * @param bool $strict
		  * @return array IP addresses
		  */
		public function searchAddDescription($addDescription, $IPv = null, $subnetId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $addDescription))
			{
				$args = array();

				if($strict) {
					$args[Api\Address::FIELD_DESC] = $addDescription;
				}
				// SQL LIKE % does not match null or empty string
				elseif($addDescription !== Api\Addresses::WILDCARD) {
					$addDescription = rtrim($addDescription, Api\Addresses::WILDCARD.'%');
					$addDescription = str_replace(Api\Addresses::WILDCARD, '%', $addDescription);
					$args[Api\Address::FIELD_DESC] = '##like##'.$addDescription.'%';
				}

				if($IPv === 4 || $IPv === 6) {
					$args['ip_version'] = $IPv;
				}

				if(C\Tools::is('int&&>0', $subnetId)) {
					$args['subnetId'] = $subnetId;
				}

				$addresses = $this->_restAPI->cwAddresses->search->get($args);
				return $this->_getCallResponse($addresses);
			}
			else {
				return false;
			}
		}
		// -------------------------------
		// ===============================

		// =========== WRITER ============
		// ----------- Address -----------
		public function createAddress($subnetId, $address, $hostname, $description = '', $note = '', $port = '', $state = Api\Address::ONLINE)
		{
			if(!C\Tools::is('int&&>0', $subnetId) || !Tools::isIP($address)) {
				return false;
			}

			$args = array(
				'subnetId' => $subnetId,
				'ip' => $address,
				'hostname' => $hostname,
				'description' => $description,
				'note' => $note,
				'port' => $port,
			);

			if(array_key_exists($state, Api\Address::STATES)) {
				$args['tag'] = $state;
			}

			try {
				$result = $this->_restAPI->addresses->post($args);
			}
			catch(C\Exception $e)
			{
				$httpCode = $this->_restAPI->addresses->getHttpCode();

				switch($httpCode)
				{
					case 409: {
						throw new E\Message("Impossible de créer l'adresse IP, '".$address."' existe déjà", E_USER_ERROR);
					}
					default: {
						throw new E\Message("Impossible de créer l'adresse IP, HTTP code '".$httpCode."'", E_USER_ERROR);
					}
				}
			}

			$result = $this->_getCallResponse($result);
			return ($result !== false);
		}

		public function modifyAddress($addressId, $hostname = null, $description = null, $note = null, $port = null, $state = null)
		{
			if(!C\Tools::is('int&&>0', $addressId)) {
				return false;
			}

			$args = array(
				'id' => $addressId,
				'hostname' => $hostname,
				'description' => $description,
				'note' => $note,
				'port' => $port,
			);

			$args = array_filter($args, function($item) {
				return ($item !== null);
			});

			if(array_key_exists($state, Api\Address::STATES)) {
				$args['tag'] = $state;
			}

			try {
				$result = $this->_restAPI->addresses->patch($args);
			}
			catch(C\Exception $e)
			{
				$httpCode = $this->_restAPI->addresses->getHttpCode();

				switch($httpCode)
				{
					default: {
						throw new E\Message("Impossible de modifier l'adresse IP, HTTP code '".$httpCode."'", E_USER_ERROR);
					}
				}
			}

			$result = $this->_getCallResponse($result);
			return ($result !== false);
		}

		public function removeAddress($addressId)
		{
			if(!C\Tools::is('int&&>0', $addressId)) {
				return false;
			}

			try {
				$result = $this->_restAPI->addresses->delete(array('id' => $addressId));
			}
			catch(C\Exception $e)
			{
				$httpCode = $this->_restAPI->addresses->getHttpCode();

				switch($httpCode)
				{
					default: {
						throw new E\Message("Impossible de supprimer l'adresse IP, HTTP code '".$httpCode."'", E_USER_ERROR);
					}
				}
			}

			$result = $this->_getCallResponse($result);
			return ($result !== false);
		}
		// -------------------------------
		// ===============================
	}