<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;

	abstract class AbstractRule extends AbstractManager
	{
		/**
		  * @var bool
		  */
		const NAME_MODE_AUTO = true;

		/**
		  * @var \PhpCliShell\Application\Firewall\Core\Api\AbstractRule
		  */
		protected $_editingRuleApi = null;

		/**
		  * @var string
		  */
		protected $_terminalShellPrompt = '';


		/**
		  * @return bool
		  */
		public function isEditingRule()
		{
			return $this->_isEditingRule(false);
		}

		/**
		  * @return false|string
		  */
		public function getEditingRuleType()
		{
			return ($this->isEditingRule()) ? ($this->_editingRuleApi::API_TYPE) : (false);
		}

		/**
		  * @return false|string
		  */
		public function getEditingRuleName()
		{
			return ($this->isEditingRule()) ? ($this->_editingRuleApi->name) : (false);
		}

		/**
		  * @param bool $printError
		  * @return bool
		  */
		protected function _isEditingRule($printError = true)
		{
			if($this->_editingRuleApi === null)
			{
				if($printError) {
					$this->_SHELL->error("Merci de rentrer au préalable dans le mode d'édition de règle ".static::MANAGER_RULE_SHORTNAME, 'orange');
				}

				return false;
			}
			else {
				return true;
			}
		}

		/**
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\AbstractRule
		  */
		protected function _getEditingRule()
		{
			return ($this->_isEditingRule(true)) ? ($this->_editingRuleApi) : (false);
		}

		/**
		  * @param string $name
		  * @return bool
		  */
		protected function _matchEditingRule($name)
		{
			return ($this->_isEditingRule(false)) ? ($this->_editingRuleApi->name === $name) : (false);
		}

		/**
		  * /!\ Publique, doit accepter un ID "humain"
		  *
		  * @return false|mixed Name
		  */
		public static function normalizeName($name)
		{
			if(static::NAME_MODE_AUTO && C\Tools::is('int&&>0', $name)) {
				return ($name-1);
			}

			return parent::normalizeName($name);
		}

		/**
		  * /!\ Protégée, doit accepter un ID "machine"
		  *
		  * @return false|mixed Name
		  */
		protected static function _normalizeName($name)
		{
			if(static::NAME_MODE_AUTO && C\Tools::is('int&&>=0', $name)) {
				return ($name+1);
			}

			return parent::normalizeName($name);
		}

		/**
		  * @param string $type
		  * @return false|mixed Returns next rule name
		  */
		public function getNextName($type = null)
		{
			if($type === null) {
				$type = current(static::API_TYPES);
			}

			$name = $this->_nextRuleId($type);
			return $this->_normalizeName($name);
		}

		/**
		  * @param string $type
		  * @return false|mixed Returns next rule ID
		  */
		protected function _nextRuleId($type)
		{
			$id = 0;
			$counter = count($this->_objects[$type]);

			if(static::NAME_MODE_AUTO)
			{
				$keys = array_keys($this->_objects[$type]);

				foreach($keys as $key)
				{
					if(C\Tools::is('int&&>=0', $key)) {
						$id = (max($id, $key) + 1);
					}
				}
			}

			return max($id, $counter);
		}

		/**
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function create($type, array $args)
		{
			if(isset($args[0]))
			{
				$category = $args[0];

				if(static::NAME_MODE_AUTO || isset($args[1]))
				{
					$name = (isset($args[1])) ? ($args[1]) : (null);

					try {
						$Core_Api_Rule = $this->_insert($type, $name, $category, true);
					}
					catch(\Exception $e) {
						$this->_SHELL->throw($e);
						$Core_Api_Rule = null;
					}

					if($Core_Api_Rule instanceof Api\AbstractRule)
					{
						$this->_SHELL->deleteWaitingMsg();		// Garanti la suppression du message

						if(!$this->isEditingRule()) {
							$this->_terminalShellPrompt = $this->_TERMINAL->getShellPrompt();
						}

						$this->_configureShellPrompt($Core_Api_Rule);
					}
					elseif($Core_Api_Rule === false) {			// Evite d'afficher ce message si une exception s'est produite
						$this->_SHELL->error("Une erreur s'est produite durant la création d'une règle ".static::MANAGER_RULE_SHORTNAME, 'orange');
					}

					return true;
				}
				else {
					$this->_SHELL->error("Merci de préciser le nom de la règle ".static::MANAGER_RULE_SHORTNAME, 'orange');
				}
			}

			return false;
		}

		/**
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function clone($type, array $args)
		{
			if($this->_typeIsAllowed($type))
			{
				if(isset($args[0]))
				{
					$srcName = $args[0];

					if(static::NAME_MODE_AUTO || isset($args[1]))
					{
						$dstName = (isset($args[1])) ? ($args[1]) : (null);

						if(($Core_Api_Rule = $this->getObject($type, $srcName)) !== false)
						{
							if($dstName === null || !$this->objectExists($type, $dstName))
							{
								$Core_Api_Rule = clone $Core_Api_Rule;
								// /!\ Ne pas garder le timestamp afin d'éviter à l'utilisateur de confondre les règles après le clonage

								if($dstName === null) {
									$dstName = $this->_nextRuleId($type);
									$dstName = $this->_normalizeName($dstName);
								}

								$Core_Api_Rule->id($dstName);
								$Core_Api_Rule->name($dstName);
								$this->_register($Core_Api_Rule);
								$this->_editingRuleApi = $Core_Api_Rule;

								$this->_SHELL->deleteWaitingMsg();		// Garanti la suppression du message

								if(!$this->isEditingRule()) {
									$this->_terminalShellPrompt = $this->_TERMINAL->getShellPrompt();
								}

								$this->_configureShellPrompt($Core_Api_Rule);
							}
							else {
								$this->_SHELL->error("Une règle ".static::MANAGER_RULE_SHORTNAME." avec le même nom '".$dstName."' existe déjà", 'orange');
							}
						}
						else {
							$this->_SHELL->error("La règle ".static::MANAGER_RULE_SHORTNAME." '".$srcName."' n'existe pas", 'orange');
						}

						return true;
					}
					else {
						$this->_SHELL->error("Merci de préciser le nom de la règle ".static::MANAGER_RULE_SHORTNAME, 'orange');
					}
				}
			}

			return false;
		}

		/**
		  * @param string $type
		  * @param string $name
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\AbstractRule
		  */
		public function update($type, $name)
		{
			return $this->_update($type, $name, false);
		}

		/**
		  * @param string $type
		  * @param string $name
		  * @param bool $enterEditingMode
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\AbstractRule
		  */
		protected function _update($type, $name, $enterEditingMode = false)
		{
			if($this->_typeIsAllowed($type))
			{
				if(!$this->_matchEditingRule($name))
				{
					if(($Core_Api_Rule = $this->getObject($type, $name)) !== false)
					{
						if($enterEditingMode) {
							$this->_editingRuleApi = $Core_Api_Rule;
						}

						return $Core_Api_Rule;
					}
					else {
						throw new E\Message("La règle ".static::MANAGER_RULE_SHORTNAME." '".$name."' n'existe pas", E_USER_WARNING);
					}
				}
				else {
					throw new E\Message("La règle ".static::MANAGER_RULE_SHORTNAME." '".$name."' est déjà en cours d'édition", E_USER_WARNING);
				}
			}

			return false;
		}

		/**
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function modify($type, array $args)
		{
			if(isset($args[0]))
			{
				$name = $args[0];

				try {
					$Core_Api_Rule = $this->_update($type, $name, true);
				}
				catch(\Exception $e) {
					$this->_SHELL->throw($e);
					$Core_Api_Rule = null;
				}

				if($Core_Api_Rule instanceof Api\AbstractRule)
				{
					$this->_SHELL->deleteWaitingMsg();		// Garanti la suppression du message

					if(!$this->isEditingRule()) {
						$this->_terminalShellPrompt = $this->_TERMINAL->getShellPrompt();
					}

					$this->_configureShellPrompt($Core_Api_Rule);
				}
				elseif($Core_Api_Rule === false) {			// Evite d'afficher ce message si une exception s'est produite
					$this->_SHELL->error("Une erreur s'est produite durant la modification d'une règle ".static::MANAGER_RULE_SHORTNAME, 'orange');
				}
				elseif($Core_Api_Rule !== null) {
					throw new Exception("Manager '".get_called_class()."' is not compatible with object '".get_class($Core_Api_Rule)."'", E_USER_ERROR);
				}

				return true;
			}

			return false;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractRule $objectApi
		  * @param string $newName
		  * @return bool
		  */
		public function move(Api\AbstractApi $objectApi, $newName)
		{
			return $this->_rename($objectApi, null, $newName, true);
		}

		/**
		  * @param string $type
		  * @param string $name
		  * @param string $newName
		  * @return bool
		  */
		public function appoint($type, $name, $newName)
		{
			return $this->_rename($type, $name, $newName, false);
		}

		/**
		  * @param string|\PhpCliShell\Application\Firewall\Core\Api\AbstractRule $typeOrObjectApi
		  * @param string $name
		  * @param string $newName
		  * @param bool $checkInstance
		  * @return bool
		  */
		protected function _rename($typeOrObjectApi, $name, $newName, $checkInstance)
		{
			if($typeOrObjectApi instanceof Api\AbstractRule) {
				$type = $typeOrObjectApi::API_TYPE;
				$name = $typeOrObjectApi->name;
				$Core_Api_Rule = $typeOrObjectApi;
				$checkPresence = true;
				//$checkInstance = true;	// @todo laisser le choix ou le forcer par sécurité?
			}
			else {
				$type = $typeOrObjectApi;
				$checkPresence = false;
				$checkInstance = false;
			}

			if($this->_typeIsAllowed($type) && $name !== null)
			{
				if(!$this->_matchEditingRule($name))
				{
					if($name !== $newName)
					{
						if(!isset($Core_Api_Rule) && ($Core_Api_Rule = $this->getObject($type, $name)) === false) {
							throw new E\Message("La règle ".static::MANAGER_RULE_SHORTNAME." '".$name."' n'existe pas", E_USER_WARNING);
						}

						if(!$checkPresence || $this->objectExists($type, $name))
						{
							$Core_Api_Rule__new = $this->getObject($type, $newName, true);

							/**
							  * Si une règle de même type avec le nouveau nom n'existe pas OU
							  * Si la même règle correspond au nouveau nom: changement de case
							  */
							if($Core_Api_Rule__new === false || $Core_Api_Rule__new->_id_ === $Core_Api_Rule->_id_)
							{
								$unregisterStatus = $this->_unregister($Core_Api_Rule, false, $checkInstance);

								if($unregisterStatus)
								{
									$renameIdStatus = $Core_Api_Rule->id($newName);
									$renameNameStatus = $Core_Api_Rule->name($newName);
									$registerStatus = $this->_register($Core_Api_Rule);

									if(!$registerStatus) {		// /!\ Plus important que le renommage
										throw new Exception("La règle ".static::MANAGER_RULE_SHORTNAME." '".$newName."' semble avoir été perdue", E_ERROR);		// Critical: do not use E\Message and E_USER_ERROR
									}
									elseif(!$renameIdStatus || !$renameNameStatus) {
										throw new Exception("La règle ".static::MANAGER_RULE_SHORTNAME." '".$newName."' n'a pas pu être renommée", E_ERROR);		// Critical: do not use E\Message and E_USER_ERROR
									}
								}
								else {
									throw new Exception("La règle ".static::MANAGER_RULE_SHORTNAME." '".$newName."' semble être verrouillée", E_ERROR);			// Critical: do not use E\Message and E_USER_ERROR
								}
							}
							else {
								throw new E\Message("La règle ".static::MANAGER_RULE_SHORTNAME." '".$newName."' existe déjà", E_USER_WARNING);
							}
						}
						else {
							throw new E\Message("La règle ".static::MANAGER_RULE_SHORTNAME." '".$name."' n'existe pas", E_USER_WARNING);
						}
					}
					else {
						throw new E\Message("La règle ".static::MANAGER_RULE_SHORTNAME." '".$name."' est déjà correctement nommée", E_USER_NOTICE);
					}

					return true;
				}
				else {
					throw new E\Message("Impossible de renommer une règle ".static::MANAGER_RULE_SHORTNAME." en cours d'édition", E_USER_WARNING);
				}
			}
			else {
				throw new E\Message("Impossible de renommer la règle ".static::MANAGER_RULE_SHORTNAME." (type or name are not valid)", E_USER_ERROR);
			}

			return false;
		}

		/**
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function rename($type, array $args)
		{
			if(isset($args[0]) && isset($args[1]))
			{
				$oldName = $args[0];
				$newName = $args[1];

				try {
					$status = $this->_rename($type, $oldName, $newName, false);
				}
				catch(\Exception $e) {
					$this->_SHELL->throw($e);
					$status = null;
				}

				if($status === true) {
					$this->_SHELL->print("Règle ".static::MANAGER_RULE_SHORTNAME." '".$oldName."' renommée en '".$newName."'", 'green');
				}
				elseif($status === false) {
					$this->_SHELL->error("Impossible de renommer la règle ".static::MANAGER_RULE_SHORTNAME." '".$oldName."'", 'orange');
				}

				return true;
			}

			return false;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractRule $objectApi
		  * @param bool $checkInstance
		  * @return bool
		  */
		public function drop(Api\AbstractApi $objectApi, $checkInstance = false)
		{
			return $this->_delete($objectApi, null, $checkInstance);
		}

		/**
		  * @param string $type
		  * @param string $name
		  * @return bool
		  */
		public function delete($type, $name)
		{
			return $this->_delete($type, $name, false);
		}

		/**
		  * @param string|\PhpCliShell\Application\Firewall\Core\Api\AbstractRule $typeOrObjectApi
		  * @param null|string $name
		  * @param bool $checkInstance
		  * @return bool
		  */
		protected function _delete($typeOrObjectApi, $name = null, $checkInstance = false)
		{
			if($typeOrObjectApi instanceof Api\AbstractRule) {
				$type = $typeOrObjectApi::API_TYPE;
				$name = $typeOrObjectApi->name;
				$Core_Api_Rule = $typeOrObjectApi;
				$checkPresence = true;
				//$checkInstance = true;	// @todo laisser le choix ou le forcer par sécurité?
			}
			else {
				$type = $typeOrObjectApi;
				$checkPresence = false;
				$checkInstance = false;
			}

			if($this->_typeIsAllowed($type) && $name !== null)
			{
				if(!$this->_matchEditingRule($name))
				{
					if(!isset($Core_Api_Rule) && ($Core_Api_Rule = $this->getObject($type, $name)) === false) {
						throw new E\Message("La règle ".static::MANAGER_RULE_SHORTNAME." '".$name."' n'existe pas", E_USER_WARNING);
					}

					if(!$checkPresence || $this->objectExists($type, $name)) {
						return $this->_unregister($Core_Api_Rule, false, $checkInstance);
					}
					else {
						throw new E\Message("La règle ".static::MANAGER_RULE_SHORTNAME." '".$name."' n'existe pas", E_USER_WARNING);
					}
				}
				else {
					throw new E\Message("Impossible de supprimer une règle ".static::MANAGER_RULE_SHORTNAME." en cours d'édition", E_USER_WARNING);
				}
			}
			else {
				throw new E\Message("Impossible de supprimer la règle ".static::MANAGER_RULE_SHORTNAME." (type or name are not valid)", E_USER_ERROR);
			}
		}

		/**
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function remove($type, array $args)
		{
			if(isset($args[0]))
			{
				$name = $args[0];

				try {
					$status = $this->_delete($type, $name);
				}
				catch(\Exception $e) {
					$this->_SHELL->throw($e);
					$status = null;
				}

				if($status === true) {
					$this->_SHELL->print("Règle ".static::MANAGER_RULE_SHORTNAME." '".$name."' supprimée!", 'green');
				}
				elseif($status === false) {
					$this->_SHELL->error("Impossible de supprimer la règle ".static::MANAGER_RULE_SHORTNAME." '".$name."'", 'orange');
				}

				return true;
			}

			return false;
		}

		/**
		  * @param string $type
		  * @param string $search
		  * @param bool $strict
		  * @return array Results
		  */
		public function locate($type, $search, $strict = false)
		{
			if($this->_typeIsAllowed($type))
			{
				$results = array();

				foreach($this->_objects[$type] as $ruleId => $Core_Api_Rule)
				{
					if($Core_Api_Rule->match($search, $strict)) {
						$results[$ruleId] = $Core_Api_Rule;
					}
				}

				if(count($results) > 0) {
					return $results;
				}
				else {
					$this->_SHELL->print("Aucune règle ".static::MANAGER_RULE_SHORTNAME." ne semble correspondre à cette recherche", 'green');
				}

				return true;
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $type
		  * @return bool
		  */
		public function clear($type)
		{
			$this->exit();
			return parent::clear($type);
		}

		/**
		  * @return bool
		  */
		public function exit()
		{
			$this->_SHELL->deleteWaitingMsg();

			if($this->_isEditingRule(false)) {
				$this->_TERMINAL->setShellPrompt($this->_terminalShellPrompt);
				$this->_terminalShellPrompt = '';
				$this->_editingRuleApi = null;
				return true;
			}

			return false;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractRule $ruleApi
		  * @return $this
		  */
		abstract protected function _configureShellPrompt(Api\AbstractRule $ruleApi);

		/**
		  * @param array $args
		  * @return bool
		  */
		public function state(array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_Rule = $this->_getEditingRule();

				if($Core_Api_Rule !== false)
				{
					switch($args[0])
					{
						case 'en':
						case 'enable': {
							$state = true;
							break;
						}
						case 'dis':
						case 'disable': {
							$state = false;
							break;
						}
						default: {
							return false;
						}
					}

					$status = $Core_Api_Rule->state($state);

					if($status) {
						$this->_SHELL->print("Statut '".$args[0]."' OK!", 'green');								
					}
					else {
						$this->_SHELL->error("Impossible d'effectuer l'opération", 'orange');
					}
				}

				return true;
			}

			return false;
		}

		/**
		  * @param array $args
		  * @return bool
		  */
		public function description(array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_Rule = $this->_getEditingRule();

				if($Core_Api_Rule !== false)
				{
					$description = $args[0];

					$status = $Core_Api_Rule->description($description);

					if($status) {
						$this->_SHELL->print("Description '".$description."' OK!", 'green');								
					}
					else {
						$this->_SHELL->error("Impossible d'effectuer l'opération", 'orange');
					}
				}

				return true;
			}

			return false;
		}

		/**
		  * $type for future use
		  *
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function tags($type, array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_Rule = $this->_getEditingRule();

				if($Core_Api_Rule !== false)
				{
					$tags = $args;

					foreach($tags as $tag)
					{
						$Core_Api_Tag = new Api\Tag($tag, $tag);
						$status = $Core_Api_Tag->tag($tag);

						if($status && $Core_Api_Tag->isValid())
						{
							$status = $Core_Api_Rule->tag($Core_Api_Tag);

							if($status) {
								$this->_SHELL->print("Tag '".$tag."' OK!", 'green');								
							}
							else {
								$this->_SHELL->error("Impossible d'effectuer l'opération, vérifiez qu'il n'y a pas de tag en doublon", 'orange');
							}
						}
						else {
							$this->_SHELL->error("Tag non valide", 'orange');
						}
					}
				}

				return true;
			}

			return false;
		}

		/**
		  * @return bool
		  */
		public function check()
		{
			$Core_Api_Rule = $this->_getEditingRule();

			if($Core_Api_Rule !== false)
			{
				$invalidAttributes = $Core_Api_Rule->isValid(true);

				if(count($invalidAttributes) === 0) {
					$this->_SHELL->print("Cette règle ".static::MANAGER_RULE_SHORTNAME." semble correctement configurée mais cela ne dispense pas l'administrateur de vérifier personnellement", 'green');
				}
				else {
					$this->_SHELL->error("Cette règle ".static::MANAGER_RULE_SHORTNAME." n'est pas correctement configurée notamment pour les attributs suivants: ".implode(', ', $invalidAttributes), 'orange');
				}

				return true;
			}

			return false;
		}
	}