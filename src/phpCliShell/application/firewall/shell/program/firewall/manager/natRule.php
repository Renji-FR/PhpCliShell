<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer;

	class NatRule extends AbstractRule
	{
		/**
		  * @var string
		  */
		const MANAGER_TYPE = 'natRuleManager';

		/**
		  * @var string
		  */
		const MANAGER_LABEL = 'NAT rule';

		/**
		  * @var string
		  */
		const MANAGER_RULE_SHORTNAME = 'NAT';

		/**
		  * @var array
		  */
		const API_TYPES = array(
			Api\NatRule::API_TYPE
		);

		/**
		  * @var array
		  */
		const API_INDEXES = array(
			Api\NatRule::API_TYPE => Api\NatRule::API_INDEX
		);

		/**
		  * @var array
		  */
		const API_CLASSES = array(
			Api\NatRule::API_TYPE => Api\NatRule::class
		);

		/**
		  * @var false|\PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\Address
		  */
		protected $_addressFwProgram = null;


		/**
		  * @return \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer\NatRule
		  */
		public function getRenderer()
		{
			return new Renderer\NatRule();
		}

		/**
		  * @return false|\PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\Address
		  */
		protected function _getAddressFwProgram()
		{
			if($this->_addressFwProgram === null) {
				$this->_addressFwProgram = $this->_firewallProgram->getProgram('address');
			}

			return $this->_addressFwProgram;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\NatRule $ruleApi
		  * @return $this
		  */
		protected function _configureShellPrompt(Api\AbstractRule $ruleApi)
		{
			$objectName = mb_strtoupper(self::MANAGER_RULE_SHORTNAME);
			$this->_TERMINAL->setShellPrompt($objectName.' ('.$ruleApi->direction.') ['.$ruleApi->name.']');
			return $this;
		}

		/**
		  * @param string $type
		  * @param null|string $name
		  * @param null|string $direction
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\NatRule
		  */
		public function insert($type, $name = null, $direction = null)
		{
			return $this->_insert($type, $name, $direction, false);
		}

		/**
		  * @param string $type
		  * @param null|string $name
		  * @param null|string $direction
		  * @param bool $enterEditingMode
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\NatRule
		  */
		protected function _insert($type, $name = null, $direction = null, $enterEditingMode = false)
		{
			if($this->_typeIsAllowed($type))
			{
				if(self::NAME_MODE_AUTO && $name === null) {
					$name = $this->_nextRuleId($type);
					$name = $this->_normalizeName($name);
				}

				if($name !== null)
				{
					if(!$this->objectExists($type, $name))
					{
						$class = $this->_typeToClass($type);

						switch(true)
						{
							case ($direction === null):
							case ($direction === Api\NatRule::DIRECTION_ONEWAY):
							case ($direction === Api\NatRule::DIRECTION_TWOWAY): {
								$Core_Api_NatRule = new $class($name, $name, $direction);
								break;
							}
							default: {
								throw new E\Message("Cette direction '".$direction."' n'est pas valide", E_USER_ERROR);
							}
						}

						$this->_register($Core_Api_NatRule);

						if($enterEditingMode) {
							$this->_editingRuleApi = $Core_Api_NatRule;
						}

						return $Core_Api_NatRule;
					}
					else {
						throw new E\Message("Une règle NAT avec le même nom '".$name."' existe déjà", E_USER_ERROR);
					}
				}
				else {
					throw new E\Message("Merci de préciser le nom de la règle NAT", E_USER_ERROR);
				}
			}

			return false;
		}

		/**
		  * @param array $args
		  * @return bool
		  */
		public function direction(array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_NatRule = $this->_getEditingRule();

				if($Core_Api_NatRule !== false)
				{
					$direction = $args[0];

					switch($direction)
					{
						case Api\NatRule::DIRECTION_ONEWAY:
						case Api\NatRule::DIRECTION_TWOWAY: {
							$status = $Core_Api_NatRule->direction($direction);
							break;
						}
						default: {
							$this->_SHELL->error("Cette direction '".$direction."' n'est pas valide", 'orange');
							return false;
						}
					}

					if($status) {
						$this->_SHELL->error("Direction '".$direction."' OK!", 'green');								
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
		  * @param string $attribute
		  * @param array $args
		  * @return bool
		  */
		public function zone($attribute, array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_NatRule = $this->_getEditingRule();

				if($Core_Api_NatRule !== false)
				{
					$zone = $args[0];

					switch($attribute)
					{
						case 'source': {
							$status = $Core_Api_NatRule->srcZone($zone);
							break;
						}
						case 'destination': {
							$status = $Core_Api_NatRule->dstZone($zone);
							break;
						}
						default: {
							$this->_SHELL->error("L'attribut zone '".$attribute."' n'est pas valide", 'orange');
							return false;
						}
					}

					if($status) {
						$this->_SHELL->error("Zone ".$attribute." '".$zone."' OK!", 'green');								
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
		  * @param string $part
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function source($part, $type, array $args)
		{
			return $this->_srcDst($part, 'source', $type, $args);
		}

		/**
		  * @param string $part
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function destination($part, $type, array $args)
		{
			return $this->_srcDst($part, 'destination', $type, $args);
		}

		/**
		  * @param string $part
		  * @param string $attribute
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		protected function _srcDst($part, $attribute, $type, array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_NatRule = $this->_getEditingRule();

				if($Core_Api_NatRule !== false)
				{
					$address = $args[0];
					$addressFwProgram = $this->_getAddressFwProgram();

					if($addressFwProgram !== false)
					{
						/**
						  * Cela permet notamment de garantir que l'IP ne changera pas en prod dans le cas où elle changerait dans l'IPAM
						  */
						try {
							$Core_Api_Address = $addressFwProgram->autoCreateObject($type, $address);
						}
						catch(E\Message $e) {
							$this->_SHELL->throw($e);
							return true;
						}
						catch(\Exception $e) {
							$this->_SHELL->error("Une exception s'est produite durant la recherche des objets de type '".$type."':", 'orange');
							$this->_SHELL->error($e->getFile().' | '.$e->getLine().' | '.$e->getMessage(), 'orange');
							return true;
						}

						// /!\ switch utilise une comparaison large (==)
						if($Core_Api_Address === null) {
							$this->_SHELL->error("Impossible de trouver cet objet '".$address."' dans l'inventaire LOCAL ou IPAM", 'orange');
						}
						elseif($Core_Api_Address === false) {
							$this->_SHELL->error("Plusieurs objets correspondent à '".$address."' dans l'inventaire LOCAL ou IPAM", 'orange');
						}
						else
						{
							$Core_Api_NatPart = $Core_Api_NatRule->getPart($part);

							if($Core_Api_NatPart !== false)
							{
								$status = $Core_Api_NatPart->configure($attribute, $Core_Api_Address);

								if($status) {
									$this->_SHELL->print(ucfirst($attribute)." '".$Core_Api_Address->name."' OK!", 'green');								
								}
								else {
									$this->_SHELL->error("Impossible d'effectuer l'opération, vérifiez qu'il n'y a pas de doublon ou que la source et la destination n'ont pas d'objets en communs", 'orange');
								}
							}
							else {
								$this->_SHELL->error("La section '".$part."' n'existe pas", 'orange');
								return false;
							}
						}
					}
					else {
						throw new Exception("Address firewall program is not available", E_USER_ERROR);
					}
				}

				return true;
			}

			return false;
		}

		/**
		  * $type for future use
		  *
		  * @param string $part
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function protocol($part, $type, array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_NatRule = $this->_getEditingRule();

				if($Core_Api_NatRule !== false)
				{
					$protocol = $args[0];

					if(isset($args[1])) {
						$protocol .= Api\Protocol::PROTO_SEPARATOR.$args[1];
					}

					$protocolApi = new Api\Protocol($protocol, $protocol);
					$status = $protocolApi->protocol($protocol);

					if($status && $protocolApi->isValid())
					{
						$Core_Api_NatPart = $Core_Api_NatRule->getPart($part);

						if($Core_Api_NatPart !== false)
						{
							$status = $Core_Api_NatPart->protocol($protocolApi);

							if($status) {
								$protocolApi->syncIdentity();
								$protocol = $protocolApi->protocol;	// /!\ Protocol aliases
								$this->_SHELL->print("Protocol '".$protocol."' OK!", 'green');								
							}
							else {
								$this->_SHELL->error("Impossible d'effectuer l'opération, vérifiez qu'il n'y a pas de doublon", 'orange');
							}
						}
						else {
							$this->_SHELL->error("La section '".$part."' n'existe pas", 'orange');
							return false;
						}
					}
					else {
						$this->_SHELL->error("Protocole invalide, entrez un protocole par commande et vérifiez sa syntaxe: ip, tcp|udp 12345[-12345], icmp type:code", 'orange');
					}
				}

				return true;
			}

			return false;
		}
	}