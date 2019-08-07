<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\AbstractProgram;

	abstract class AbstractManager extends AbstractProgram
	{
		/**
		  * @var string
		  */
		const MANAGER_TYPE = 'unknown';

		/**
		  * @var string
		  */
		const MANAGER_LABEL = 'unknown';

		/**
		  * @var array
		  */
		const API_TYPES = array(
			Api\Site::API_TYPE,
			Api\Host::API_TYPE,
			Api\Subnet::API_TYPE,
			Api\Network::API_TYPE,
			Api\AclRule::API_TYPE,
			Api\NatRule::API_TYPE,
		);

		/**
		  * @var array
		  */
		const API_INDEXES = array(
			Api\Site::API_TYPE => Api\Site::API_INDEX,
			Api\Host::API_TYPE => Api\Host::API_INDEX,
			Api\Subnet::API_TYPE => Api\Subnet::API_INDEX,
			Api\Network::API_TYPE => Api\Network::API_INDEX,
			Api\AclRule::API_TYPE => Api\AclRule::API_INDEX,
			Api\NatRule::API_TYPE => Api\NatRule::API_INDEX,
		);

		/**
		  * @var array
		  */
		const API_CLASSES = array(
			Api\Site::API_TYPE => Api\Site::class,
			Api\Host::API_TYPE => Api\Host::class,
			Api\Subnet::API_TYPE => Api\Subnet::class,
			Api\Network::API_TYPE => Api\Network::class,
			Api\AclRule::API_TYPE => Api\AclRule::class,
			Api\NatRule::API_TYPE => Api\NatRule::class,
		);

		/**
		  * @var string
		  */
		const FILTER_DUPLICATES = 'filter_duplicates';


		/**
		  * @return \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer\AbstractRenderer
		  */
		abstract public function getRenderer();

		/**
		  * @param string $type
		  * @param string $arg
		  * @param bool $strictKey
		  * @param bool $strictMatch
		  * @return bool
		  */
		public function objectExists($type, $arg, $strictKey = true, $strictMatch = true)
		{
			return ($this->getObject($type, $arg, $strictKey, $strictMatch) !== false);
		}

		/**
		  * @param string $type
		  * @param string $arg
		  * @param bool $strictKey
		  * @param bool $strictMatch
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\AbstractApi
		  */
		public function getObject($type, $arg, $strictKey = true, $strictMatch = true)
		{
			$object = $this->getObjects($type, $arg, $strictKey, $strictMatch, 1);
			return (count($object) > 0) ? (current($object)) : (false);
		}

		/**
		  * @param string $type
		  * @param string $arg
		  * @param bool $strictKey
		  * @param bool $strictMatch
		  * @param int $limit
		  * @return array
		  */
		public function getObjects($type, $arg, $strictKey = true, $strictMatch = true, $limit = null)
		{
			$name = $this->normalizeName($arg);

			/**
			  * Normalisation du nom impossible
			  * Recherche par correspondance (match)
			  */
			if($name === false) {
				$name = $arg;
			}
			/**
			  * Normalisation du nom possible/réussi
			  * Dans ce cas on force le mode strictKey
			  *
			  * NON car si l'utilisateur demande TOTO alors normalizeName peut retourner toto
			  * Dans ce cas là $arg !== $name mais il ne faut pas forcer le mode strict
			  * car TOTO n'est pas forcément le nom complet de l'objet
			  */
			/*elseif($name !== $arg) {
				$strictKey = true;
			}*/

			return $this->_getObjects($type, $name, $strictKey, $strictMatch, $limit);
		}

		/**
		  * /!\ Publique, doit accepter un ID "humain"
		  *
		  * @return false|mixed Name
		  */
		public static function normalizeName($name)
		{
			return mb_strtolower($name);
		}

		protected function _objectExists($type, $name)
		{
			return ($this->_getObject($type, $name) !== false);
		}

		protected function _getObject($type, $name)
		{
			$objects = $this->_getObjects($type, $name, true);
			return (count($objects) === 1) ? (current($objects)) : (false);
		}

		protected function _getObjects($type, $name, $strictKey = true, $strictMatch = true, $limit = null)
		{
			$results = array();

			if(array_key_exists($type, $this->_objects))
			{
				$objects = $this->_objects[$type];

				if(array_key_exists($name, $objects)) {
					return array($objects[$name]);
				}
				elseif(!$strictKey)
				{
					$counter = 0;

					foreach($objects as $object)
					{
						if($object->match($name, $strictMatch))
						{
							$counter++;
							$results[] = $object;

							if($counter === $limit) {
								break;
							}
						}
					}
				}
			}

			return $results;
		}

		/**
		  * /!\ Protégée, doit accepter un ID "machine"
		  *
		  * @return false|mixed Name
		  */
		protected static function _normalizeName($name)
		{
			return $name;
		}

		public function add(Api\AbstractApi $objectApi, $checkValidity = true)
		{
			$type = $objectApi::API_TYPE;

			if($this->_typeIsAllowed($type) && (!$checkValidity || $objectApi->isValid()) && !$this->objectExists($type, $objectApi->name)) {
				$this->_register($objectApi);
				return true;
			}
			else {
				return false;
			}
		}

		public function insert($type, $name)
		{
			return false;
		}

		abstract public function create($type, array $args);

		public function clone($type, array $args)
		{
			return false;
		}

		/**
		  * @param string $type
		  * @param array $object
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\AbstractApi
		  */
		public function wakeup($type, array $object)
		{
			$objectApiClass = $this->_typeToClass($type);

			if($objectApiClass !== false) {
				$Core_Api_Abstract = new $objectApiClass();
				$status = $this->_wakeup($Core_Api_Abstract, $object);
				return ($status) ? ($Core_Api_Abstract): (false);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $type
		  * @param array $objects
		  * @param bool $checkValidity
		  * @return \PhpCliShell\Application\Firewall\Core\Api\AbstractApi[]
		  */
		public function restore($type, array $objects, $checkValidity = true)
		{
			$results = array();
			$objectApiClass = $this->_typeToClass($type);

			if($objectApiClass !== false)
			{
				foreach($objects as $index => $object)
				{
					$Core_Api_Abstract = new $objectApiClass();

					if(array_key_exists($objectApiClass::FIELD_NAME, $object)) {
						$name = $object[$objectApiClass::FIELD_NAME];
					}
					else {
						throw new E\Message("L'objet '".$Core_Api_Abstract::API_LABEL."' index '".$index."' ne possède pas de nom", E_USER_ERROR);
					}

					if(!$this->objectExists($type, $name))
					{
						try {
							$status = $this->_wakeup($Core_Api_Abstract, $object);
						}
						catch(E\Message $exception) {
							$eMessage = "Une erreur s'est produite lors de la restauration de l'objet ";
							$eMessage .= "'".$Core_Api_Abstract::API_LABEL."' '".$name."':";
							$eMessage .= PHP_EOL.$exception->getMessage();
							throw new E\Message($eMessage, E_USER_ERROR);
						}

						if($status)
						{
							if($checkValidity) {
								$invalidFieldNames = $Core_Api_Abstract->isValid(true);
							}
							else {
								$invalidFieldNames = array();
							}

							if(count($invalidFieldNames) === 0)
							{
								$isAdded = $this->add($Core_Api_Abstract, false);

								if($isAdded) {
									$results[] = $Core_Api_Abstract;
								}
								else {
									throw new E\Message("L'objet '".$Core_Api_Abstract::API_LABEL."' '".$name."' n'a pas pu être ajouté", E_USER_ERROR);
								}
							}
							else {
								$invalidFields = "(".implode(', ', $invalidFieldNames).")";
								throw new E\Message("L'objet '".$Core_Api_Abstract::API_LABEL."' '".$name."' ne semble pas valide ".$invalidFields, E_USER_ERROR);
							}
						}
						else {
							throw new E\Message("L'objet '".$Core_Api_Abstract::API_LABEL."' '".$name."' ne peut être restauré", E_USER_ERROR);
						}
					}
				}

				return $results;
			}
			else {
				throw new Exception("Unknown ".static::MANAGER_LABEL." type '".$type."'", E_USER_ERROR);
			}
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param array $object
		  * @return bool
		  */
		protected function _wakeup(Api\AbstractApi $objectApi, array $object)
		{
			return $objectApi->wakeup($object);
		}

		public function edit(Api\AbstractApi $objectApi, $checkValidity = true)
		{
			return false;
		}

		public function update($type, $name)
		{
			return false;
		}

		abstract public function modify($type, array $args);

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param string $newName
		  * @return bool
		  */
		public function move(Api\AbstractApi $objectApi, $newName)
		{
			return false;
		}

		/**
		  * @param string $type
		  * @param string $name
		  * @param string $newName
		  * @return bool
		  */
		public function appoint($type, $name, $newName)
		{
			return false;
		}

		public function rename($type, array $args)
		{
			return false;
		}

		public function drop(Api\AbstractApi $objectApi, $checkInstance = false)
		{
			return false;
		}

		public function delete($type, $name)
		{
			return false;
		}

		abstract public function remove($type, array $args);

		public function locate($type, $search, $strict = false)
		{
			return false;
		}

		public function filter($type, $filter, $strict = false)
		{
			return false;
		}

		/**
		  * @param string $type
		  * @return bool
		  */
		public function clear($type)
		{
			$class = $this->_typeToClass($type);

			if($class !== false)
			{
				$objects =& $this->_objects[$class::API_TYPE];		// /!\ Important
				$objects = array();

				$objectName = ucfirst($class::API_LABEL);
				$this->_SHELL->print($objectName." réinitialisé", 'green');
				return true;
			}
			else {
				return false;
			}
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @return bool
		  */
		protected function _isRegistered(Api\AbstractApi $objectApi)
		{
			$type = $objectApi::API_TYPE;
			$name = $this->normalizeName($objectApi->name);
			return ($name !== false && array_key_exists($name, $this->_objects[$type]));
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param bool $checkPresence
		  * @param bool $checkValidity
		  * @return bool
		  */
		protected function _register(Api\AbstractApi $objectApi, $checkPresence = false, $checkValidity = false)
		{
			$type = $objectApi::API_TYPE;
			$name = $this->normalizeName($objectApi->name);

			if(!$checkPresence || ($name !== false && !array_key_exists($name, $this->_objects[$type])))
			{
				if(!$checkValidity || $objectApi->isValid())
				{
					$this->_objects[$type][$name] = $objectApi;

					/**
					  * Permet de s'assurer que l'ordre des objets est correct
					  * /!\ NE PAS EFFECTUER UN KSORT SINON LE GIT DIFF NE SERA PAS LISIBLE
					  */
					//uksort($this->_objects[$type], 'strnatcasecmp');

					return true;
				}
			}

			return false;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param bool $checkPresence
		  * @param bool $checkInstance
		  * @return bool
		  */
		protected function _unregister(Api\AbstractApi $objectApi, $checkPresence = false, $checkInstance = false)
		{
			$type = $objectApi::API_TYPE;
			$name = $this->normalizeName($objectApi->name);

			if(!$checkPresence || ($name !== false && array_key_exists($name, $this->_objects[$type])))
			{
				// http://php.net/manual/fr/language.oop5.object-comparison.php
				if(!$checkInstance || $objectApi === $this->_objects[$type][$name]) {
					unset($this->_objects[$type][$name]);
					return true;
				}
			}
	
			return false;
		}
	}