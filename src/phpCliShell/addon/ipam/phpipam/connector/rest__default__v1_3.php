<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Connector;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Addon\Ipam\Phpipam\Api;
	use PhpCliShell\Addon\Ipam\Phpipam\Tools;
	use PhpCliShell\Addon\Ipam\Phpipam\Service;
	use PhpCliShell\Addon\Ipam\Phpipam\Exception;

	class Rest__default__v1_3 extends AbstractRestConnector
	{
		/**
		  * @var string
		  */
		const LABEL = 'default_v1.3';

		/**
		  * @var array
		  */
		const REST_URN = array(
			'user' => 'user',
			'sections' => 'sections',
			'folders' => 'folders',
			'subnets' => 'subnets',
			'addresses' => 'addresses',
			'vlans' => 'vlans',
			'l2domains' => 'l2domains',
			'vrfs' => 'vrfs',
			'tools' => 'tools',
			'prefix' => 'prefix',
		);


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
				/*$items = $this->_restAPI->subnets->{$subnetId}->addresses->get();
				$items = $this->_getCallResponse($items);

				foreach($items as $item)
				{
					// /!\ Return string, no int
					if($item['is_gateway'] === "1") {
						return $item['ip'];
					}
				}*/

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
			return $this->_getSections($sectionId);
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
				$args = array(
					'filter_by' => Api\Section::FIELD_PARENT_ID,
					'filter_value' => $sectionId
				);

				try {
					$sections = $this->_restAPI->sections->get($args);
				}
				catch(C\Exception $e)
				{
					if($this->_restAPI->sections->getHttpCode() === 404) {
						return array();
					}
					else {
						throw $e;
					}
				}
				
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
			return $this->_getFolders(static::FOLDER_ROOT_ID, $sectionId);
		}

		/**
		  * @param null|int $folderId Parent folder ID
		  * @param null|int $sectionId Parent section ID
		  * @return false|array Folders
		  */
		public function getFolders($folderId, $sectionId = null)
		{
			return $this->_getFolders($folderId, $sectionId);
		}

		/**
		  * @param int $folderId Folder ID
		  * @return false|array Folder
		  */
		public function getFolder($folderId)
		{
			return $this->_getFolder($folderId);
		}

		/**
		  * @param null|int $folderId Parent folder ID
		  * @param null|int $sectionId Parent section ID
		  * @return false|array Folders
		  */
		protected function _getFolders($folderId, $sectionId)
		{
			if(!C\Tools::is('int&&>=0', $folderId)) {
				$folderId = null;
			}

			if(C\Tools::is('int&&>=0', $sectionId))
			{
				$args = array(
					'filter_by' => Api\Folder::FIELD_SECTION_ID,
					'filter_value' => $sectionId
				);

				try {
					// /!\ For folders apply filter on 'all' method
					$folders = $this->_restAPI->folders->all->get($args);
				}
				catch(C\Exception $e)
				{
					if($this->_restAPI->folders->getHttpCode() === 404) {
						return array();
					}
					else {
						throw $e;
					}
				}

				$folders = $this->_getCallResponse($folders);

				/**
				  * L'API phpIPAM peut retourner des subnets avec les folders
				  */
				if($folders !== false)
				{
					foreach($folders as $index => $folder)
					{
						if(($folderId !== null && $folder[Api\Folder::FIELD_PARENT_ID] !== (string) $folderId) ||
							($folder['isFolder'] !== static::SUBNET_IS_FOLDER))
						{
							unset($folders[$index]);
						}
					}

					$folders = array_values($folders);
				}

				return $folders;
			}
			elseif($folderId !== null)
			{
				$args = array(
					'filter_by' => Api\Folder::FIELD_PARENT_ID,
					'filter_value' => $folderId
				);

				try {
					// /!\ For folders apply filter on 'all' method
					$folders = $this->_restAPI->folders->all->get($args);
				}
				catch(C\Exception $e)
				{
					if($this->_restAPI->folders->getHttpCode() === 404) {
						return array();
					}
					else {
						throw $e;
					}
				}

				$folders = $this->_getCallResponse($folders);

				/**
				  * L'API phpIPAM peut retourner des subnets avec les folders
				  */
				if($folders !== false)
				{
					foreach($folders as $index => $folder)
					{
						if($folder['isFolder'] !== static::SUBNET_IS_FOLDER) {
							unset($folders[$index]);
						}
					}

					$folders = array_values($folders);
				}

				return $folders;
			}
			else {
				return $this->getAllFolders();
			}
		}

		/**
		  * @param int $folderId Folder ID
		  * @return false|array Folder
		  */
		protected function _getFolder($folderId)
		{
			if(C\Tools::is('int&&>0', $folderId))
			{
				try {
					$subnet = $this->_restAPI->folders->{$folderId}->get();
				}
				catch(C\Exception $e)
				{
					if($this->_restAPI->folders->getHttpCode() === 404) {
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
					  * L'API IPAM peut retourner des dossiers avec les subnets et possiblement des subnets invalides
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

			return $this->_getSubnets($subnetId, $folderId, $sectionId);
		}

		/**
		  * @param null|int $subnetId Parent subnet ID
		  * @param null|int $folderId Parent folder ID
		  * @param null|int $sectionId Parent section ID
		  * @return false|array Subnets
		  */
		public function getSubnets($subnetId, $folderId = null, $sectionId = null)
		{
			return $this->_getSubnets($subnetId, $folderId, $sectionId);
		}

		/**
		  * @param int $subnetId Subnet ID
		  * @return false|array Subnet
		  */
		public function getSubnet($subnetId)
		{
			return $this->_getSubnet($subnetId);
		}

		/**
		  * @param null|int $subnetId Parent subnet ID
		  * @param null|int $folderId Parent folder ID
		  * @param null|int $sectionId Parent section ID
		  * @return false|array Subnets
		  */
		protected function _getSubnets($subnetId, $folderId, $sectionId)
		{
			if(!C\Tools::is('int&&>=0', $subnetId))
			{
				if(C\Tools::is('int&&>=0', $folderId)) {
					$subnetId = $folderId;
				}
				else {
					$subnetId = null;
				}
			}

			if(C\Tools::is('int&&>0', $sectionId))
			{
				try {
					$subnets = $this->_restAPI->sections->{$sectionId}->subnets->get();
				}
				catch(C\Exception $e)
				{
					if($this->_restAPI->sections->getHttpCode() === 404) {
						return array();
					}
					else {
						throw $e;
					}
				}

				$subnets = $this->_getCallResponse($subnets);

				/**
				  * L'API phpIPAM peut retourner des folders avec les subnets
				  */
				if($subnets !== false)
				{
					foreach($subnets as $index => $subnet)
					{
						if(($subnetId !== null && $subnet[Api\Subnet::FIELD_PARENT_ID] !== (string) $subnetId) ||
							($subnet['isFolder'] !== static::SUBNET_IS_NOT_FOLDER))
						{
							unset($subnets[$index]);
						}
					}

					$subnets = array_values($subnets);
				}

				return $subnets;
			}
			elseif($subnetId !== null)
			{
				$args = array(
					'filter_by' => Api\Subnet::FIELD_PARENT_ID,
					'filter_value' => $subnetId
				);

				try {
					// /!\ For subnets apply filter on 'all' method
					$subnets = $this->_restAPI->subnets->all->get($args);
				}
				catch(C\Exception $e)
				{
					if($this->_restAPI->subnets->getHttpCode() === 404) {
						return array();
					}
					else {
						throw $e;
					}
				}

				$subnets = $this->_getCallResponse($subnets);

				/**
				  * L'API phpIPAM peut retourner des folders avec les subnets
				  */
				if($subnets !== false)
				{
					foreach($subnets as $index => $subnet)
					{
						if($subnet['isFolder'] !== static::SUBNET_IS_NOT_FOLDER) {
							unset($subnets[$index]);
						}
					}

					$subnets = array_values($subnets);
				}

				return $subnets;
			}
			else {
				return $this->getAllSubnets();
			}
		}

		/**
		  * @param int $subnetId Subnet ID
		  * @return false|array Subnet
		  */
		protected function _getSubnet($subnetId)
		{
			if(C\Tools::is('int&&>0', $subnetId))
			{
				try {
					$subnet = $this->_restAPI->subnets->{$subnetId}->get();
				}
				catch(C\Exception $e)
				{
					if($this->_restAPI->subnets->getHttpCode() === 404) {
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
				// Search in section or not
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$sections = $this->getSections($sectionId);
				}
				elseif($strict)
				{
					try {
						$sections = $this->_restAPI->sections->{$sectionName}->get();
					}
					catch(C\Exception $e)
					{
						if($this->_restAPI->sections->getHttpCode() === 404) {
							return array();
						}
						else {
							throw $e;
						}
					}

					return $this->_getCallResponse($sections);
				}
				else {
					$sections = $this->getAllSections();
				}

				$sections = $this->_filterResponse($sections, Api\Section::FIELD_NAME, $sectionName, $strict);
				return array_values($sections);
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
			if(C\Tools::is('string&&!empty', $folderName))
			{
				if(!C\Tools::is('int&&>=0', $folderId)) {
					$folderId = null;
				}

				if(!C\Tools::is('int&&>=0', $sectionId)) {
					$sectionId = null;
				}

				if($strict)
				{
					$args = array(
						'filter_by' => Api\Folder::FIELD_NAME,
						'filter_value' => $folderName
					);

					try {
						// /!\ For folders apply filter on 'all' method
						$folders = $this->_restAPI->folders->all->get($args);
					}
					catch(C\Exception $e)
					{
						if($this->_restAPI->folders->getHttpCode() === 404) {
							return array();
						}
						else {
							throw $e;
						}
					}

					$folders = $this->_getCallResponse($folders);

					/**
					  * L'API phpIPAM peut retourner des subnets avec les folders
					  */
					if($folders !== false)
					{
						foreach($folders as $index => $folder)
						{
							if(($folderId !== null && $folder[Api\Folder::FIELD_PARENT_ID] !== (string) $folderId) ||
								($sectionId !== null && $folder[Api\Folder::FIELD_SECTION_ID] !== (string) $sectionId) ||
								$folder['isFolder'] !== static::SUBNET_IS_FOLDER)
							{
								unset($folders[$index]);
							}
						}

						$folders = array_values($folders);
					}

					return $folders;
				}
				elseif($folderId !== null || $sectionId !== null) {
					$folders = $this->getFolders($sectionId, $folderId);
				}
				else {
					$folders = $this->getAllFolders();
				}

				$folders = $this->_filterResponse($folders, Api\Folder::FIELD_NAME, $folderName, $strict);
				return array_values($folders);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $subnetCidr Subnet CIDR
		  * @return false|array Subnets
		  */
		public function searchSubnets($cidrSubnet)
		{
			if(Tools::isSubnet($cidrSubnet))
			{
				$cidrSubnet = explode('/', $cidrSubnet, 2);
				$subnetRestApi = $this->_restAPI->subnets->cidr;

				foreach($cidrSubnet as $subnetPart) {
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
				if(!C\Tools::is('int&&>=0', $subnetId))
				{
					if(C\Tools::is('int&&>=0', $folderId)) {
						$subnetId = $folderId;
					}
					else {
						$subnetId = null;
					}
				}

				if(!C\Tools::is('int&&>=0', $sectionId)) {
					$sectionId = null;
				}

				$subnets = $this->_restAPI->subnets->cidr->{$subnetCidr}->get();
				$subnets = $this->_getCallResponse($subnets);

				// {"code":200,"success":0,"message":"No subnets found","time":0.014}
				if(C\Tools::is('array', $subnets))
				{
					/**
					  * Search in subnet or not (permit root)
					  * Search in section or not (permit root)
					  */
					if($subnetId !== null || $sectionId !== null)
					{
						foreach($subnets as $index => $subnet)
						{
							if(($subnetId !== null && $subnet[Api\Subnet::FIELD_PARENT_ID] !== (string) $subnetId) ||
								($sectionId !== null && $subnet[Api\Subnet::FIELD_SECTION_ID] !== (string) $sectionId))
							{
								unset($subnets[$index]);
							}
						}

						$subnets = array_values($subnets);
					}

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
			if(C\Tools::is('string&&!empty', $subnetName))
			{
				if($IPv !== 4 && $IPv !== 6) {
					$IPv = null;
				}

				if(!C\Tools::is('int&&>=0', $subnetId))
				{
					if(C\Tools::is('int&&>=0', $folderId)) {
						$subnetId = $folderId;
					}
					else {
						$subnetId = null;
					}
				}

				if(!C\Tools::is('int&&>=0', $sectionId)) {
					$sectionId = null;
				}

				if($strict)
				{
					$args = array(
						'filter_by' => Api\Subnet::FIELD_NAME,
						'filter_value' => $subnetName
					);

					try {
						// /!\ For subnets apply filter on 'all' method
						$subnets = $this->_restAPI->subnets->all->get($args);
					}
					catch(C\Exception $e)
					{
						if($this->_restAPI->subnets->getHttpCode() === 404) {
							return array();
						}
						else {
							throw $e;
						}
					}

					$subnets = $this->_getCallResponse($subnets);

					/**
					  * L'API phpIPAM peut retourner des folders avec les subnets
					  */
					if($subnets !== false)
					{
						foreach($subnets as $index => $subnet)
						{
							if(($subnetId !== null && $subnet[Api\Subnet::FIELD_PARENT_ID] !== (string) $subnetId) ||
								($sectionId !== null && $subnet[Api\Subnet::FIELD_SECTION_ID] !== (string) $sectionId) ||
								!Tools::isSubnetV($subnet['subnet'].'/'.$subnet['mask'], $IPv) ||
								$subnet['isFolder'] !== static::SUBNET_IS_NOT_FOLDER)
							{
								unset($subnets[$index]);
							}
						}

						$subnets = array_values($subnets);
					}

					return $subnets;
				}
				elseif($subnetId !== null || $sectionId !== null) {
					$subnets = $this->getSubnets($sectionId, $subnetId);
				}
				else {
					$subnets = $this->getAllSubnets();
				}

				if($IPv !== null)
				{
					foreach($subnets as $index => $subnet)
					{
						if(!Tools::isSubnetV($subnet['subnet'].'/'.$subnet['mask'], $IPv)) {
							unset($subnets[$index]);
						}
					}
				}

				$subnets = $this->_filterResponse($subnets, Api\Subnet::FIELD_NAME, $subnetName, $strict);
				return array_values($subnets);
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
				if($strict)
				{
					$args = array(
						'filter_by' => Api\Vlan::FIELD_NAME,
						'filter_value' => $vlanName
					);

					try {
						$vlans = $this->_restAPI->vlans->get($args);
					}
					catch(C\Exception $e)
					{
						if($this->_restAPI->vlans->getHttpCode() === 404) {
							return array();
						}
						else {
							throw $e;
						}
					}

					return $this->_getCallResponse($vlans);
				}
				else {
					$vlans = $this->getAllVlans();
					$vlans = $this->_filterResponse($vlans, Api\Vlan::FIELD_NAME, $vlanName, $strict);
					return array_values($vlans);
				}
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
				if(Tools::isIP($addressIP))
				{
					$addresses = $this->_restAPI->addresses->search->{$addressIP}->get();
					$addresses = $this->_getCallResponse($addresses);

					// {"code":200,"success":0,"message":"Address not found","time":0.014}
					if(!C\Tools::is('array', $addresses)) {
						return array();
					}

					if($addresses !== false && C\Tools::is('int&&>0', $subnetId))
					{
						foreach($addresses as $index => $address)
						{
							if($address[Api\Address::FIELD_SUBNET_ID] !== (string) $subnetId) {
								unset($addresses[$index]);
							}
						}

						$addresses = array_values($addresses);
					}

					return $addresses;
				}
				elseif(C\Tools::is('int&&>0', $subnetId)) {
					return $this->getAddresses($subnetId);
				}
				else
				{
					/**
					  * Do not permit to load all addresses !
					  * phpIPAM has not description search method compatible with wildcard *
					  */
					return false;
				}
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
				if($IPv !== 4 && $IPv !== 6) {
					$IPv = null;
				}

				if(!C\Tools::is('int&&>0', $subnetId)) {
					$subnetId = null;
				}

				if($strict)
				{
					$args = array(
						'filter_by' => Api\Address::FIELD_NAME,
						'filter_value' => $addHostname
					);

					try {
						$addresses = $this->_restAPI->addresses->get($args);
					}
					catch(C\Exception $e)
					{
						if($this->_restAPI->addresses->getHttpCode() === 404) {
							$addresses = array();
						}
						else {
							throw $e;
						}
					}

					if(!isset($e)) {
						$addresses = $this->_getCallResponse($addresses);
					}
				}
				else
				{
					$addHostname = rtrim($addHostname, '*%');
					$addHostname = str_replace('\*', '%', $addHostname);

					/**
					  * phpIPAM auto add % at the end of search
					  *
					  * ../addresses/search_hostbase/prefix%25suffix
					  * SQL: dns_name LIKE 'prefix%suffix%'
					  */
					$addresses = $this->_restAPI->addresses->search_hostbase->{$addHostname}->get();
					$addresses = $this->_getCallResponse($addresses);

					// {"code":200,"success":0,"message":"Host name not found","time":0.014}
					if(!C\Tools::is('array', $addresses)) {
						return array();
					}
				}

				if($addresses !== false && $IPv !== null && $subnetId !== null)
				{
					foreach($addresses as $index => $address)
					{
						if(($subnetId !== null && $address[Api\Address::FIELD_SUBNET_ID] !== (string) $subnetId) ||
							($IPv !== null && !Tools::isIPv($address['ip'], $IPv)))
						{
							unset($addresses[$index]);
						}
					}

					$addresses = array_values($addresses);
				}

				return $addresses;
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
			/**
			  * phpIPAM do not permit to use filter (filter_by / filter_value) with addresses API controller
			  */
			return array();

			if(C\Tools::is('string&&!empty', $addDescription))
			{
				if($IPv !== 4 && $IPv !== 6) {
					$IPv = null;
				}

				if(!C\Tools::is('int&&>0', $subnetId)) {
					$subnetId = null;
				}

				if($strict)
				{
					$args = array(
						'filter_by' => Api\Address::FIELD_DESC,
						'filter_value' => $addDescription
					);

					try {
						$addresses = $this->_restAPI->addresses->get($args);
					}
					catch(C\Exception $e)
					{
						if($this->_restAPI->addresses->getHttpCode() === 404) {
							$addresses = array();
						}
						else {
							throw $e;
						}
					}

					if(!isset($e)) {
						$addresses = $this->_getCallResponse($addresses);
					}

					if($addresses !== false && $subnetId !== null)
					{
						foreach($addresses as $index => $address)
						{
							if($address[Api\Address::FIELD_SUBNET_ID] !== (string) $subnetId) {
								unset($addresses[$index]);
							}
						}
					}
				}
				elseif($subnetId !== null)
				{
					$addresses = $this->_restAPI->subnets->{$subnetId}->addresses->get();
					$addresses = $this->_getCallResponse($addresses);

					$addresses = $this->_filterResponse($addresses, Api\Address::FIELD_DESC, $addDescription, $strict);
				}
				else
				{
					/**
					  * Do not permit to load all addresses !
					  * phpIPAM has not description search method compatible with wildcard *
					  */
					$addDescription = str_ireplace('*', '', $addDescription);
					return $this->searchAddDescription($addDescription, null, $subnetId, true);
				}

				if($addresses !== false && $IPv !== null)
				{
					foreach($addresses as $index => $address)
					{
						if(!Tools::isIPv($address['ip'], $IPv)) {
							unset($addresses[$index]);
						}
					}
				}

				return array_values($addresses);
			}
			else {
				return false;
			}
		}
		// -------------------------------
		// ===============================

		// =========== WRITER ============
		// ----------- Address -----------
		public function createAddress($subnetId, $address, $hostname, $description = '', $note = '', $port = '', $tag = Api\Address::ONLINE)
		{
			if(!C\Tools::is('int&&>0', $subnetId) || !Tools::isIP($address)) {
				return false;
			}

			$args = array(
				Api\Address::FIELD_SUBNET_ID => $subnetId,
				'hostname' => $hostname,
				'description' => $description,
				'ip' => $address,
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

		// ============ TOOL =============
		protected function _filterResponse($items, $field, $value, $strict)
		{
			if($items !== false)
			{
				if(!$strict) {
					$value = rtrim($value, '*%');
					$value = preg_quote($value);
					$value = str_replace('\*', '.*', $value);
				}

				foreach($items as $index => $item)
				{
					if($strict)
					{
						if(!$item[$field] !== $value) {
							unset($items[$index]);
						}
					}
					elseif(!preg_match('#'.$value.'#i', $item[$field])) {
						unset($items[$index]);
					}
				}
			}

			return $items;
		}
		// ===============================
	}