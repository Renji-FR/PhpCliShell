<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Application\Firewall\Core\Exception;

	class AclRule extends AbstractRule
	{
		/**
		  * @var string
		  */
		const API_TYPE = 'aclRule';

		/**
		  * @var string
		  */
		const API_INDEX = 'aclRule';

		/**
		  * @var string
		  */
		const API_LABEL = 'ACL rule';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var string
		  */
		const CATEGORY_MONOSITE = 'monosite';

		/**
		  * @var string
		  */
		const CATEGORY_FAILOVER = 'failover';

		/**
		  * @var string
		  */
		const SEPARATOR_TYPE = '::';

		/**
		  * @var array
		  */
		protected $_datas = array(
			'_id_' => null,
			'name' => null,
			'category' => null,
			'fullmesh' => false,
			'state' => false,
			'action' => false,
			'sources' => array(),
			'destinations' => array(),
			'protocols' => array(),
			'description' => '',
			'tags' => array(),
			'timestamp' => null,
		);


		/**
		  * @param string $id ID
		  * @param string $name Name
		  * @param string $category Category
		  * @param string $description Description
		  * @return $this
		  */
		public function __construct($id = null, $name = null, $category = null, $description = null)
		{
			parent::__construct($id, $name);

			$this->category($category);
			$this->description($description);
		}

		/**
		  * @param string $category Category
		  * @return bool
		  */
		public function category($category = self::CATEGORY_MONOSITE)
		{
			if($category === null) {
				$category = self::CATEGORY_MONOSITE;
			}

			switch($category)
			{
				case self::CATEGORY_MONOSITE:
					$this->_datas['fullmesh'] = false;
					$this->_datas['category'] = $category;
					break;
				case self::CATEGORY_FAILOVER:
					$this->_datas['category'] = $category;
					break;
				default:
					throw new Exception("ACL rule category '".$category."' does not exist", E_USER_ERROR);
			}

			return true;
		}

		public function fullmesh($state = true)
		{
			$category = $this->category;

			if($this->category === self::CATEGORY_MONOSITE) {
				$this->_datas['fullmesh'] = false;
			}
			elseif($this->category === self::CATEGORY_FAILOVER)
			{
				if(C\Tools::is('bool', $state)) {
					$this->_datas['fullmesh'] = $state;
				}
				else
				{
					if($this->_datas['fullmesh'] === null) {
						$this->_datas['fullmesh'] = true;
					}
					else {
						$this->_datas['fullmesh'] = !$this->_datas['fullmesh'];
					}
				}
			}
			else {
				throw new Exception("ACL rule category '".$category."' is unknow", E_USER_ERROR);
			}

			return true;
		}

		/**
		  * @param bool $action
		  * @return bool
		  */
		public function action($action = false)
		{
			if(C\Tools::is('bool', $action)) {
				$this->_datas['action'] = $action;
				return true;
			}

			return false;
		}

		public function src(Address $addressApi)
		{
			return $this->addSource($addressApi);
		}

		public function source(Address $addressApi)
		{
			return $this->addSource($addressApi);
		}

		public function addSource(Address $addressApi)
		{
			return $this->_addSrcDst('sources', $addressApi);
		}

		public function sources(array $sources)
		{
			return $this->setSources($sources);
		}

		public function setSources(array $sources)
		{
			$this->reset('sources');

			foreach($sources as $source)
			{
				$status = $this->source($source);

				if(!$status) {
					$this->reset('sources');
					return false;
				}
			}

			return true;
		}

		public function dst(Address $addressApi)
		{
			return $this->addDestination($addressApi);
		}

		public function destination(Address $addressApi)
		{
			return $this->addDestination($addressApi);
		}

		public function addDestination(Address $addressApi)
		{
			return $this->_addSrcDst('destinations', $addressApi);
		}

		public function destinations(array $destinations)
		{
			return $this->setDestinations($destinations);
		}

		public function setDestinations(array $destinations)
		{
			$this->reset('destinations');

			foreach($destinations as $destination)
			{
				$status = $this->destination($destination);

				if(!$status) {
					$this->reset('destinations');
					return false;
				}
			}

			return true;
		}

		protected function _addSrcDst($attribute, Address $addressApi)
		{
			if($addressApi->isValid())
			{
				/**
				  * /!\ On interdit des doublons d'host car cela n'a pas de sens mais on l'autorise pour les subnet et les network
				  * /!\ Les doublons ne sont autorisés que entre les attributs source et destination et non au sein du même attribut
				  */
				if($addressApi::API_TYPE === Host::API_TYPE) {
					$attributes = array('sources', 'destinations');
				}
				else {
					$attributes = array($attribute);
				}

				$type = $addressApi->type;
				$_id_ = $addressApi->_id_;

				foreach($attributes as $_attributes)
				{
					foreach($this->_datas[$_attributes] as $Api_Address)
					{
						/**
						  * Des objets Api_Address de type différents peuvent avoir le même nom
						  *
						  * Le contrôle de l'overlap d'adresses doit se faire en dehors de la cette classe
						  * afin de laisser le choix de l'autoriser ou de l'interdire et d'afficher un avertissement
						  */
						if($type === $Api_Address->type && $_id_ === $Api_Address->_id_) {
							return false;
						}
					}
				}

				$this->_datas[$attribute][] = $addressApi;
				$this->_refresh($attribute);
				return true;
			}
			else {
				return false;
			}
		}

		/**
		  * @return void
		  */
		public function checkOverlapAddress()
		{
			$attributes = array(
				'sources' => array('sources', 'destinations'),
				'destinations' =>  array('destinations')
			);

			$testsDone = array();

			foreach($attributes as $attribute_A => $attributes_B)
			{
				foreach($attributes_B as $attribute_B)
				{
					foreach($this->_datas[$attribute_A] as $index_A => $addressApi_A)
					{
						foreach($this->_datas[$attribute_B] as $index_B => $addressApi_B)
						{
							if($index_A === $index_B || (isset($testsDone[$index_B]) && in_array($index_A, $testsDone[$index_B], true))) {
								continue;
							}
							/**
							  * /!\ On interdit des doublons d'host car cela n'a pas de sens mais on l'autorise pour les subnet et les network
							  * /!\ Les doublons ne sont autorisés que entre les attributs source et destination et non au sein du même attribut
							  */
							elseif($attribute_A !== $attribute_B && ($addressApi_A::API_TYPE !== Host::API_TYPE || $addressApi_B::API_TYPE !== Host::API_TYPE)) {
								continue;
							}

							$this->_addressesOverlap($addressApi_A, $addressApi_B);
							$testsDone[$index_B][] = $index_A;
						}
					}
				}
			}
		}

		/**
		  * @param string $srcDst
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi
		  * @return void
		  */
		public function testAddressOverlap($srcDst, Address $addressApi)
		{
			/**
			  * /!\ On interdit des doublons d'host car cela n'a pas de sens mais on l'autorise pour les subnet et les network
			  * /!\ Les doublons ne sont autorisés que entre les attributs source et destination et non au sein du même attribut
			  */
			if($addressApi::API_TYPE === Host::API_TYPE) {
				$attributes = array('sources', 'destinations');
			}
			else
			{
				switch($srcDst)
				{
					case 'source':
					case 'sources': {
						$attributes = array('sources');
						break;
					}
					case 'destination':
					case 'destinations': {
						$attributes = array('destinations');
						break;
					}
					default: {
						throw new Exception("ACL rule attribute '".$srcDst."' is not valid", E_USER_ERROR);
					}
				}
			}

			$type = $addressApi->type;
			$_id_ = $addressApi->_id_;

			foreach($attributes as $_attributes)
			{
				foreach($this->_datas[$_attributes] as $Api_Address)
				{
					/**
					  * Lorsque l'utilisateur souhaite modifier une addresse
					  * alors il ne faut pas tester l'overlap sur elle même
					  */
					if($type !== $Api_Address->type || $_id_ !== $Api_Address->_id_) {
						$this->_addressesOverlap($addressApi, $Api_Address);
					}
				}
			}
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi_A
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi_B
		  * @return void
		  */
		protected function _addressesOverlap(Address $addressApi_A, Address $addressApi_B)
		{
			$addressName_A = ucfirst($addressApi_A::API_LABEL);
			$addressName_B = ucfirst($addressApi_B::API_LABEL);

			switch($addressApi_A::API_TYPE)
			{
				case Host::API_TYPE:
				{
					if($addressApi_B->includes($addressApi_A)) {
						throw new E\Message($addressName_B." '".$addressApi_B->name."' includes or overlaps the ".$addressName_A." '".$addressApi_A->name."'", E_USER_WARNING);
					}
					break;
				}
				case Subnet::API_TYPE:
				case Network::API_TYPE:
				{
					switch($addressApi_B::API_TYPE)
					{
						case Host::API_TYPE:
						{
							if($addressApi_A->includes($addressApi_B)) {
								throw new E\Message($addressName_A." '".$addressApi_A->name."' includes the ".$addressName_B." '".$addressApi_B->name."'", E_USER_WARNING);
							}
							break;
						}
						case Subnet::API_TYPE:
						case Network::API_TYPE:
						{
							if($addressApi_A->includes($addressApi_B) || $addressApi_B->includes($addressApi_A)) {
								throw new E\Message($addressName_B." '".$addressApi_B->name."' includes or is included by the ".$addressName_A." '".$addressApi_A->name."'", E_USER_WARNING);
							}
							break;
						}
					}
					break;
				}
			}
		}

		/**
		  * Adds protocol
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi
		  * @return bool
		  */
		public function proto(Protocol $protocolApi)
		{
			return $this->addProtocol($protocolApi);
		}

		/**
		  * Adds protocol
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi
		  * @return bool
		  */
		public function protocol(Protocol $protocolApi)
		{
			return $this->addProtocol($protocolApi);
		}

		/**
		  * Adds protocol
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi
		  * @return bool
		  */
		public function addProtocol(Protocol $protocolApi)
		{
			if($protocolApi->isValid())
			{
				$_id_ = $protocolApi->_id_;

				foreach($this->_datas['protocols'] as $Api_Protocol)
				{
					if($_id_ === $Api_Protocol->_id_) {
						return false;
					}
				}

				$this->_datas['protocols'][] = $protocolApi;
				$this->_refresh('protocols');
				return true;
			}

			return false;
		}

		/**
		  * Adds protocols
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol[] $protocols
		  * @return bool
		  */
		public function protocols(array $protocols)
		{
			return $this->setProtocols($protocols);
		}

		/**
		  * Adds protocols
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol[] $protocols
		  * @return bool
		  */
		public function setProtocols(array $protocols)
		{
			$this->reset('protocols');

			foreach($protocols as $protocol)
			{
				$status = $this->protocol($protocol);

				if(!$status) {
					$this->reset('protocols');
					return false;
				}
			}

			return true;
		}

		/**
		  * Configures this rule
		  *
		  * @param string $attrName For addresses must be source(s) or destination(s)
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @return bool
		  */
		public function configure($attrName, AbstractApi $objectApi)
		{
			if($objectApi instanceof Address)
			{
				switch($attrName)
				{
					case 'source':
					case 'sources': {
						$attrName = 'sources';
						break;
					}
					case 'destination':
					case 'destinations': {
						$attrName = 'destinations';
						break;
					}
					default: {
						return false;
					}
				}

				return $this->_addSrcDst($attrName, $objectApi);
			}
			elseif($objectApi instanceof Protocol) {
				return $this->protocol($objectApi);
			}
			else {
				return parent::configure($attrName, $objectApi);
			}
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $badAddressApi
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $newAddressApi
		  * @param null|int $counter
		  * @return int
		  */
		public function replace(Address $badAddressApi, Address $newAddressApi, &$counter = null)
		{
			/**
			  * Dans certains cas l'utilisateur peut passer une variable counter qui n'est pas égale à 0
			  * Cela permet à l'utilisateur d'avoir un compteur total, donc ne pas tenter de le réinitialiser à 0
			  */
			if(!C\Tools::is('int', $counter)) {
				$counter = 0;
			}

			$localCounter = 0;

			foreach(array('source', 'destination') as $attribute)
			{
				/**
				  * Dans un 1er temps, on supprime l'ancien objet adresse
				  *
				  * Si et seulement si on a réussi à le supprimer et donc à le trouver
				  * alors on peut essayer de configurer le nouvel objet adresse mais
				  * si celui-ci est déjà présent alors il ne sera pas ajouté
				  *
				  * Le compteur doit par contre s'incrémenter afin d'indiquer un changement
				  */
				$resetStatus = $this->reset($attribute, $badAddressApi->type, $badAddressApi);

				if($resetStatus) {
					$configureStatus = $this->configure($attribute, $newAddressApi);
					$localCounter++;
				}
			}

			$counter += $localCounter;
			return ($localCounter > 0);
		}

		/**
		  * @param null|string $attribute
		  * @param null|string $type
		  * @param null|\PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @return bool
		  */
		public function reset($attribute = null, $type = null, AbstractApi $objectApi = null)
		{
			switch(true)
			{
				case ($attribute === 'source'):
				{
					if($type !== null && $objectApi !== null) {
						return $this->_resetAddress($this->_datas['sources'], $type, $objectApi);
					}
					else {
						return false;
					}
					break;
				}
				case ($attribute === 'sources'): {
					$this->_datas['sources'] = array();
					break;
				}
				case ($attribute === 'destination'):
				{
					if($type !== null && $objectApi !== null) {
						return $this->_resetAddress($this->_datas['destinations'], $type, $objectApi);
					}
					else {
						return false;
					}
					break;
				}
				case ($attribute === 'destinations'): {
					$this->_datas['destinations'] = array();
					break;
				}
				case ($attribute === 'protocol'):
				{
					if($type !== null && $objectApi !== null) {
						return $this->_resetProtocol($this->_datas['protocols'], $type, $objectApi);
					}
					else {
						return false;
					}
					break;
				}
				case ($attribute === 'protocols'): {
					$this->_datas['protocols'] = array();
					break;
				}
				case ($attribute === null):
				case ($attribute === true): {
					$this->_datas['sources'] = array();
					$this->_datas['destinations'] = array();
					$this->_datas['protocols'] = array();
					break;
				}
				default: {
					return parent::reset($attribute, $type, $objectApi);
				}
			}

			return true;
		}

		protected function _resetAddress(&$attributes, $type, Address $addressApi)
		{
			switch($type)
			{
				case Host::API_TYPE:
				case Subnet::API_TYPE:
				case Network::API_TYPE: {
					return $this->_reset($attributes, $type, $addressApi, 'name');
				}
			}

			return false;
		}

		protected function _resetProtocol(&$attributes, $type, Protocol $protocolApi)
		{
			return $this->_reset($attributes, $type, $protocolApi, 'protocol');
		}

		/**
		  * @return $this
		  */
		public function refresh()
		{
			$this->_refresh('sources');
			$this->_refresh('destinations');
			$this->_refresh('protocols');
			return parent::refresh();
		}

		/**
		  * @param string $attribute
		  * @return void
		  */
		protected function _refresh($attribute)
		{
			switch($attribute)
			{
				case 'sources':
				case 'destinations':
				case 'protocols':
				{
					uasort($this->_datas[$attribute], function($a, $b) {
						return strnatcasecmp($a->name, $b->name);
					});
					break;
				}
				default: {
					parent::_refresh($attribute);
				}
			}
		}

		/**
		  * Checks the address argument is present for this rule
		  *
		  * Do not test attributeV4 or attributeV6 because
		  * the test must be about Address object and not it attributes
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi Address object to test
		  * @return bool Address is present for this rule
		  */
		public function addressIsPresent(Address $addressApi)
		{
			foreach(array('sources', 'destinations') as $attributes)
			{
				$isPresent = $this->_objectIsPresent($attributes, $addressApi);

				if($isPresent) {
					return true;
				}
			}

			return false;
		}

		/**
		  * Checks the protocol argument is present for this rule
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi Protocol object to test
		  * @return bool Protocol is present for this rule
		  */
		public function protocolIsPresent(Protocol $protocolApi)
		{
			return $this->_objectIsPresent('protocols', $protocolApi);
		}

		/**
		  * Checks the address argument is present for this rule
		  *
		  * Do not test attributeV4 or attributeV6 because
		  * the test must be about Address object and not it attributes
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi Address object to test
		  * @return bool Address is present for this rule
		  */
		public function isPresent(Address $addressApi)
		{
			return $this->addressIsPresent($addressApi);
		}

		/**
		  * Checks the address argument is used for this rule
		  *
		  * Do not test name because the test must be
		  * about Address attributes and not the object
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi Address object to test
		  * @param bool $strict True to test equality between addresse attributes, false to test addresse attributes inclusion
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $ignoreAddressApi Address object to ignore, use type and ID to compare address object
		  * @return bool Address is used for this rule
		  */
		public function addressIsInUse(Address $addressApi, $strict = true, Address $ignoreAddressApi = null)
		{
			$Api_Address = $this->getAddressIsInUse($addressApi, $strict, $ignoreAddressApi);
			return ($Api_Address !== false);
		}

		/**
		  * Gets the address argument is used for this rule
		  *
		  * Do not test name because the test must be
		  * about Address attributes and not the object
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi Address object to test
		  * @param bool $strict True to test equality between addresse attributes, false to test addresse attributes inclusion
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $ignoreAddressApi Address object to ignore, use type and ID to compare address object
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\Address Address is used for this rule
		  */
		public function getAddressIsInUse(Address $addressApi, $strict = true, Address $ignoreAddressApi = null)
		{
			$addressType = $addressApi->type;
			$isIPv4 = $addressApi->isIPv4();
			$isIPv6 = $addressApi->isIPv6();
			$attributeV4 = $addressApi->attributeV4;
			$attributeV6 = $addressApi->attributeV6;

			if($ignoreAddressApi !== null) {
				$ignoreType = $ignoreAddressApi->type;
				$ignoreId = $ignoreAddressApi->_id_;
			}

			foreach(array('sources', 'destinations') as $attributes)
			{
				foreach($this->_datas[$attributes] as $Api_Address)
				{
					$currentType = $Api_Address->type;

					if($ignoreAddressApi !== null && $ignoreType === $currentType && $ignoreId === $Api_Address->_id_) {
						continue;
					}

					if($strict)
					{
						if($currentType === $addressType && (
							($isIPv4 && $Api_Address->attributeV4 === $attributeV4) ||
							($isIPv6 && $Api_Address->attributeV6 === $attributeV6)))
						{
							return $Api_Address;
						}
					}
					else
					{
						if($Api_Address->includes($addressApi)) {
							return $Api_Address;
						}
					}
				}
			}

			return false;
		}

		/**
		  * Checks the protocol argument is used for this rule
		  *
		  * Do not test name because the test must be
		  * about Protocol attributes and not the object
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi Protocol object to test
		  * @return bool Protocol is used for this rule
		  */
		public function protocolIsInUse(Protocol $protocolApi, $strict = true)
		{
			$Api_Protocol = $this->getProtocolIsInUse($protocolApi, $strict);
			return ($Api_Protocol !== false);
		}

		/**
		  * Gets the protocol argument is used for this rule
		  *
		  * Do not test name because the test must be
		  * about Protocol attributes and not the object
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi Protocol object to test
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\Protocol Protocol is used for this rule
		  */
		public function getProtocolIsInUse(Protocol $protocolApi, $strict = true)
		{
			$type = $protocolApi->type;
			$protocol = $protocolApi->protocol;

			foreach($this->_datas['protocols'] as $Api_Protocol)
			{
				if($strict)
				{
					if($Api_Protocol->type === $type && $Api_Protocol->protocol === $protocol) {
						return $Api_Protocol;
					}
				}
				else
				{
					if($Api_Protocol->includes($protocolApi)) {
						return $Api_Protocol;
					}
				}
			}

			return false;
		}

		/**
		  * Checks the address argument is used for this rule
		  *
		  * Do not test name because the test must be
		  * about Address attributes and not the object
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi Address object to test
		  * @return bool Address is used for this rule
		  */
		public function isInUse(Address $addressApi, $strict = true)
		{
			return $this->addressIsInUse($addressApi, $strict);
		}

		/**
		  * @param bool $returnInvalidAttributes
		  * @return bool
		  */
		public function isValid($returnInvalidAttributes = false)
		{
			$parentIsValid = parent::isValid($returnInvalidAttributes);

			$tests = array(
				array('fullmesh' => 'bool'),
				array('action' => 'bool'),
				array('category' => 'string&&!empty'),
				array('sources' => 'array&&count>0'),
				array('destinations' => 'array&&count>0'),
				array('protocols' => 'array&&count>0'),
			);

			$selfIsValid = $this->_isValid($tests, $returnInvalidAttributes);
			
			if($returnInvalidAttributes) {
				return array_merge($parentIsValid, $selfIsValid);
			}
			else {
				return ($parentIsValid && $selfIsValid);
			}
		}

		/**
		  * @param mixed $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'monosite': {
					return ($this->_datas['category'] === self::CATEGORY_MONOSITE);
				}
				case 'failover': {
					return ($this->_datas['category'] === self::CATEGORY_FAILOVER);
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

			foreach(array('sources', 'destinations') as $attributes)
			{
				foreach($datas[$attributes] as &$attrObject) {
					$attrObject = $attrObject::API_TYPE.self::SEPARATOR_TYPE.$attrObject->name;
				}
				unset($attrObject);

				/**
				  * /!\ Important for json_encode
				  * Si des index sont manquants alors json_encode
				  * va indiquer explicitement les clés dans le tableau
				  */
				$datas[$attributes] = array_values($datas[$attributes]);
			}

			foreach($datas['protocols'] as &$attrObject) {
				$attrObject = $attrObject->protocol;
			}
			unset($attrObject);

			/**
			  * /!\ Important for json_encode
			  * Si des index sont manquants alors json_encode
			  * va indiquer explicitement les clés dans le tableau
			  */
			$datas['protocols'] = array_values($datas['protocols']);

			return $datas;
		}

		/**
		  * @param $datas array
		  * @return bool
		  */
		public function wakeup(array $datas)
		{
			parent::wakeup($datas);

			// /!\ Permets de s'assurer que les traitements spéciaux sont bien appliqués
			$this->category($datas['category']);
			$this->fullmesh($datas['fullmesh']);
			$this->action($datas['action']);

			foreach($datas['protocols'] as $protocol)
			{
				$Api_Protocol = new Protocol($protocol, $protocol);
				$status = $Api_Protocol->protocol($protocol);

				if($status && $Api_Protocol->isValid()) {
					$this->protocol($Api_Protocol);
				}
				else {
					throw new E\Message("Protocol '".$protocol."' is not valid", E_USER_ERROR);
				}
			}
			
			return true;
		}
	}