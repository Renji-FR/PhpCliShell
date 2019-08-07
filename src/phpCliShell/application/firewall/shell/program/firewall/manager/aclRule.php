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

	class AclRule extends AbstractRule
	{
		/**
		  * @var string
		  */
		const MANAGER_TYPE = 'aclRuleManager';

		/**
		  * @var string
		  */
		const MANAGER_LABEL = 'ACL rule';

		/**
		  * @var string
		  */
		const MANAGER_RULE_SHORTNAME = 'ACL';

		/**
		  * @var array
		  */
		const API_TYPES = array(
			Api\AclRule::API_TYPE
		);

		/**
		  * @var array
		  */
		const API_INDEXES = array(
			Api\AclRule::API_TYPE => Api\AclRule::API_INDEX
		);

		/**
		  * @var array
		  */
		const API_CLASSES = array(
			Api\AclRule::API_TYPE => Api\AclRule::class
		);

		/**
		  * @var false|\PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\Address
		  */
		protected $_addressFwProgram = null;


		/**
		  * @return \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer\AclRule
		  */
		public function getRenderer()
		{
			return new Renderer\AclRule();
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
			$this->_TERMINAL->setShellPrompt($objectName.' ('.$ruleApi->category.') ['.$ruleApi->name.']');
			return $this;
		}

		/**
		  * @param string $type
		  * @param null|string $name
		  * @param null|string $category
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\AclRule
		  */
		public function insert($type, $name = null, $category = null)
		{
			return $this->_insert($type, $name, $category, false);
		}

		/**
		  * @param string $type
		  * @param null|string $name
		  * @param null|string $category
		  * @param bool $enterEditingMode
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\AclRule
		  */
		protected function _insert($type, $name = null, $category = null, $enterEditingMode = false)
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
							case ($category === null):
							case ($category === Api\AclRule::CATEGORY_MONOSITE):
							case ($category === Api\AclRule::CATEGORY_FAILOVER): {
								$Core_Api_AclRule = new $class($name, $name, $category);
								break;
							}
							default: {
								throw new E\Message("Cette catégorie '".$category."' n'est pas valide", E_USER_ERROR);
							}
						}

						$this->_register($Core_Api_AclRule);

						if($enterEditingMode) {
							$this->_editingRuleApi = $Core_Api_AclRule;
						}

						return $Core_Api_AclRule;
					}
					else {
						throw new E\Message("Une règle ACL avec le même nom '".$name."' existe déjà", E_USER_ERROR);
					}
				}
				else {
					throw new E\Message("Merci de préciser le nom de la règle ACL", E_USER_ERROR);
				}
			}

			return false;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param array $object
		  * @return bool
		  */
		protected function _wakeup(Api\AbstractApi $objectApi, array $object)
		{
			$status = $objectApi->wakeup($object);

			if($status)
			{
				foreach(array('source' => 'sources', 'destination' => 'destinations') as $attribute => $attributes)
				{
					if(array_key_exists($attributes, $object))
					{
						foreach($object[$attributes] as $Core_Api_Address)
						{
							$status = $objectApi->configure($attribute, $Core_Api_Address);

							if(!$status) {
								throw new E\Message("Impossible d'ajouter l'adresse '".$Core_Api_Address->name."' à la règle '".$objectApi->name."'", E_USER_ERROR);
							}
						}
					}
				}

				return true;
			}
			else {
				return false;
			}
		}

		public function replace($type, $badType, $badName, $newType, $newName)
		{
			if($this->_typeIsAllowed($type))
			{
				$addressFwProgram = $this->_getAddressFwProgram();

				if($addressFwProgram !== false)
				{
					if($addressFwProgram->isType($badType) && $addressFwProgram->isType($newType))
					{
						$Core_Api_Address__bad = $addressFwProgram->getObject($badType, $badName, true);
						$Core_Api_Address__new = $addressFwProgram->getObject($newType, $newName, true);

						if($Core_Api_Address__bad === false) {
							$objectName = $addressFwProgram->getName($badType);
							$this->_SHELL->error("L'objet '".$badName."' de type '".$objectName."' semble ne pas exister", 'orange');
						}
						elseif($Core_Api_Address__new === false) {
							$objectName = $addressFwProgram->getName($newType);
							$this->_SHELL->error("L'objet '".$newName."' de type '".$objectName."' semble ne pas exister", 'orange');
						}
						else
						{
							$counter = 0;

							foreach($this->_objects[$type] as $Core_Api_Rule)
							{
								$newAddressApiIsPresent = $Core_Api_Rule->addressIsPresent($Core_Api_Address__new);
								$status = $Core_Api_Rule->replace($Core_Api_Address__bad, $Core_Api_Address__new, $counter);

								if($status && $newAddressApiIsPresent) {
									$message = "l'objet '".$Core_Api_Address__bad::API_LABEL."' '".$Core_Api_Address__bad->name."' a été supprimé car ";
									$message .= "l'objet '".$Core_Api_Address__new::API_LABEL."' '".$Core_Api_Address__new->name."' était déjà présent";
									$this->_SHELL->error("RULE '".$Core_Api_Rule->name."': ".$message, 'red');
								}
							}

							if($counter === 1) {
								$this->_SHELL->print("1 règle a été mise à jour", 'green');
							}
							elseif($counter > 1) {
								$this->_SHELL->print($counter." règles ont été mises à jour", 'green');
							}
							else {
								$this->_SHELL->error("Aucune règle n'a été mise à jour", 'orange');
							}
						}

						return true;
					}
				}
				else {
					throw new Exception("Address firewall program is not available", E_USER_ERROR);
				}
			}

			return false;
		}

		public function locateFlow($type, array $args, $strict = false)
		{
			if($this->_typeIsAllowed($type))
			{
				if(count($args) >= 6)
				{
					$srcAddress = $args[1];
					$dstAddress = $args[3];
					$protocol = $args[5];

					$Core_Api_Address__src = Api\Address::factory($srcAddress);
					$Core_Api_Address__dst = Api\Address::factory($dstAddress);

					if($Core_Api_Address__src !== false && $Core_Api_Address__src->isValid() &&
						$Core_Api_Address__dst !== false && $Core_Api_Address__dst->isValid())
					{
						if(isset($args[6])) {
							$protocol .= Api\Protocol::PROTO_SEPARATOR.$args[6];
						}

						$Core_Api_Protocol = new Api\Protocol($protocol, $protocol);
						$status = $Core_Api_Protocol->protocol($protocol);

						if($status && $Core_Api_Protocol->isValid())
						{
							$time1 = microtime(true);
							$this->_SHELL->print("Recherche d'un flow...", 'orange');

							$results = array();

							foreach($this->_objects[$type] as $ruleId => $Core_Api_Rule)
							{
								if($Core_Api_Rule->addressIsInUse($Core_Api_Address__src, false) &&
									$Core_Api_Rule->addressIsInUse($Core_Api_Address__dst, false) &&
									$Core_Api_Rule->protocolIsInUse($Core_Api_Protocol, false))
								{
									$results[$ruleId] = $Core_Api_Rule;
								}
							}

							$time2 = microtime(true);
							$this->_TERMINAL->deleteMessage(1, true);
							$this->_SHELL->print("Recherche d'un flow (".round($time2-$time1)."s) [OK]", 'green');

							if(count($results) > 0) {
								return $results;
							}
							else {
								$this->_SHELL->print("Aucune règle ne semble correspondre à ce flow", 'green');
							}

							return true;
						}
					}
				}
			}

			return false;
		}

		public function filter($type, $filter, $strict = false)
		{
			if($this->_typeIsAllowed($type))
			{
				if($filter === self::FILTER_DUPLICATES)
				{
					$results = array();
					$runCache = array();

					$time1 = microtime(true);
					$this->_SHELL->print("Vérification des doublons ...", 'orange');

					foreach($this->_objects[$type] as $ruleId_a => $Firewall_Api_Rule__a)
					{
						$runCache[] = $ruleId_a;

						foreach($this->_objects[$type] as $ruleId_b => $Firewall_Api_Rule__b)
						{
							/**
							  * /!\ La vérification (in_array) clé $ruleId_b est correct!
							  */
							if(in_array($ruleId_b, $runCache, true)) {
								continue;
							}
							else
							{
								$filterSrcDst = function($address_a, $address_b)
								{
									if($address_a->attributeV4 === $address_b->attributeV4 && $address_a->attributeV6 === $address_b->attributeV6) {
										return 0;
									}
									else {
										return strnatcasecmp($address_a->name, $address_b->name);
									}
								};

								$diffSrcA = array_udiff($Firewall_Api_Rule__a->sources, $Firewall_Api_Rule__b->sources, $filterSrcDst);

								if(count($diffSrcA) > 0) {
									continue;
								}

								$diffSrcB = array_udiff($Firewall_Api_Rule__b->sources, $Firewall_Api_Rule__a->sources, $filterSrcDst);

								if(count($diffSrcB) > 0) {
									continue;
								}

								$diffDstA = array_udiff($Firewall_Api_Rule__a->destinations, $Firewall_Api_Rule__b->destinations, $filterSrcDst);

								if(count($diffDstA) > 0) {
									continue;
								}

								$diffDstB = array_udiff($Firewall_Api_Rule__b->destinations, $Firewall_Api_Rule__a->destinations, $filterSrcDst);

								if(count($diffDstB) > 0) {
									continue;
								}

								$filterProto = function($protocol_a, $protocol_b)
								{
									if($protocol_a->protocol === $protocol_b->protocol) {
										return 0;
									}
									else {
										return strnatcasecmp($protocol_a->name, $protocol_b->name);
									}
								};

								$diffProtoA = array_udiff($Firewall_Api_Rule__a->protocols, $Firewall_Api_Rule__b->protocols, $filterProto);

								if(count($diffProtoA) > 0) {
									continue;
								}

								$diffProtoB = array_udiff($Firewall_Api_Rule__b->protocols, $Firewall_Api_Rule__a->protocols, $filterProto);

								if(count($diffProtoB) > 0) {
									continue;
								}

								$results[$ruleId_a][] = $ruleId_b;
							}
						}
					}

					$time2 = microtime(true);
					$this->_TERMINAL->deleteMessage(1, true);
					$this->_SHELL->print("Vérification des doublons (".round($time2-$time1)."s) [OK]", 'green');

					return $results;
				}
				else {
					throw new Exception("Unknown filter '".$filter."'", E_USER_ERROR);
				}
			}
			else {
				return false;
			}
		}

		public function filterFlow($type, $filter, $strict = false)
		{
			if($this->_typeIsAllowed($type))
			{
				if($filter === self::FILTER_DUPLICATES)
				{
					$flows = array();
					$results = array();

					$time1 = microtime(true);
					$this->_SHELL->print("Inventaire des flows ...", 'orange');

					foreach($this->_objects[$type] as $ruleId => $Firewall_Api_Rule)
					{
						foreach($Firewall_Api_Rule->sources as $Core_Api_Address__src)
						{
							foreach($Firewall_Api_Rule->destinations as $Core_Api_Address__dst)
							{
								foreach($Firewall_Api_Rule->protocols as $Core_Api_Protocol)
								{
									$flows[] = new ArrayObject(array(
										'ruleId' => $ruleId,
										'ruleName' => $Firewall_Api_Rule->name,
										'state' => $Firewall_Api_Rule->state,
										'action' => $Firewall_Api_Rule->action,
										'source' => $Core_Api_Address__src,
										'destination' => $Core_Api_Address__dst,
										'protocol' => $Core_Api_Protocol
									), ArrayObject::ARRAY_AS_PROPS);
								}
							}
						}
					}

					$time2 = microtime(true);
					$this->_TERMINAL->deleteMessage(1, true);
					$this->_SHELL->print("Inventaire des flows {".count($flows)."} (".round($time2-$time1)."s) [OK]", 'green');
					$this->_SHELL->print("Vérification doublons ...", 'orange');

					foreach($flows as $index_a => $flow_a)
					{
						$this->_TERMINAL->deleteMessage(1, true);
						$this->_SHELL->print("Vérification doublons ... (RULE '".$flow_a->ruleName."')...", 'orange');

						foreach($flows as $index_b => $flow_b)
						{
							if($index_a >= $index_b) {
								continue;
							}
							elseif($flow_a->action === $flow_b->action)
							{
								/**
								  * /!\ A peut ne pas inclure B mais B peut inclure A
								  * donc toujours effectuer les tests pour les 2 combinaisons
								  */
								$srcStatus = $flow_a->source->includes($flow_b->source);
								$dstStatus = $flow_a->destination->includes($flow_b->destination);
								$protoStatus = $flow_a->protocol->includes($flow_b->protocol);

								if($srcStatus && $dstStatus && $protoStatus &&
									(!array_key_exists($flow_b->ruleId, $results) || !in_array($flow_a->ruleId, $results[$flow_b->ruleId], true)))
								{
									$results[$flow_a->ruleId][] = $flow_b->ruleId;
								}
							}
						}
					}

					$time3 = microtime(true);
					$this->_TERMINAL->deleteMessage(1, true);
					$this->_SHELL->print("Vérification doublons (".round($time3-$time2)."s) [OK]", 'green');

					foreach($results as &$result) {
						$result = array_unique($result);
					}
					unset($result);

					return $results;
				}
				else {
					throw new Exception("Unknown filter '".$filter."'", E_USER_ERROR);
				}
			}
			else {
				return false;
			}
		}

		public function filterAttributes($type, $filter, $attribute)
		{
			if($this->_typeIsAllowed($type))
			{
				if($filter === self::FILTER_DUPLICATES)
				{
					$attributes = array();
					$results = array();

					switch($attribute)
					{
						case 'addresses': {
							$attributes[] = 'sources';
							$attributes[] = 'destinations';
							break;
						}
						case 'protocols': {
							$attributes[] = 'protocols';
							break;
						}
						case 'tags': {
							$attributes[] = 'tags';
							break;
						}
						case 'all': {
							$attributes[] = 'sources';
							$attributes[] = 'destinations';
							$attributes[] = 'protocols';
							$attributes[] = 'tags';
						}
					}

					foreach($attributes as $attribute)
					{
						$time1 = microtime(true);
						$this->_SHELL->print("Vérification des doublons dans attribut '".$attribute."' ...", 'orange');

						foreach($this->_objects[$type] as $ruleId => $Core_Api_Rule)
						{
							$stores = array();

							foreach($Core_Api_Rule[$attribute] as $Core_Api_Abstract) {
								$stores[$Core_Api_Abstract::API_TYPE][] = $Core_Api_Abstract->_id_;
							}

							foreach($stores as $store)
							{
								foreach(array_count_values($store) as $id => $counter)
								{
									if($counter > 1)
									{
										if(!isset($results[$ruleId][$attribute])) {
											$results[$ruleId][$attribute] = 0;
										}

										$results[$ruleId][$attribute]++;
									}
								}
							}
						}

						$time2 = microtime(true);
						$this->_TERMINAL->deleteMessage(1, true);
						$this->_SHELL->print("Vérification doublons dans attribut '".$attribute."' (".round($time2-$time1)."s) [OK]", 'green');
					}

					return $results;
				}
				else {
					throw new Exception("Unknown filter '".$filter."'", E_USER_ERROR);
				}
			}
			else {
				return false;
			}
		}

		/**
		  * @param array $args
		  * @return bool
		  */
		public function category(array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_Rule = $this->_getEditingRule();

				if($Core_Api_Rule !== false)
				{
					$category = $args[0];

					switch($category)
					{
						case Api\AclRule::CATEGORY_MONOSITE:
						case Api\AclRule::CATEGORY_FAILOVER: {
							$status = $Core_Api_Rule->category($category);
							break;
						}
						default: {
							$this->_SHELL->error("Cette catégorie '".$category."' n'est pas valide", 'orange');
							return false;
						}
					}

					if($status) {
						$this->_SHELL->error("Catégorie '".$category."' OK!", 'green');								
					}
					else {
						$this->_SHELL->error("Impossible d'effectuer l'opération", 'orange');
					}
				}

				return true;
			}

			return false;
		}

		public function fullmesh(array $args)
		{
			if(!isset($args[0])) {
				$args[0] = null;
			}

			$Core_Api_Rule = $this->_getEditingRule();

			if($Core_Api_Rule !== false)
			{
				switch($args[0])
				{
					case 'en':
					case 'enable': {
						$fullmesh = true;
						break;
					}
					case 'dis':
					case 'disable': {
						$fullmesh = false;
						break;
					}
					default: {
						$fullmesh = null;
					}
				}

				if($Core_Api_Rule->category === Api\AclRule::CATEGORY_MONOSITE) {
					$this->_SHELL->error("L'option 'Full Mesh' n'est disponible que pour les règles ACL de catégorie 'Failover'", 'orange');
				}
				else
				{
					$status = $Core_Api_Rule->fullmesh($fullmesh);

					if($status) {
						$fullmesh = $Core_Api_Rule->fullmesh;
						$fullmesh = ($fullmesh) ? ('enable') : ('disable');
						$this->_SHELL->error("Full mesh '".$fullmesh."' OK!", 'green');								
					}
					else {
						$this->_SHELL->error("Impossible d'effectuer l'opération", 'orange');
					}
				}
			}

			return true;
		}

		public function action(array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_Rule = $this->_getEditingRule();

				if($Core_Api_Rule !== false)
				{
					switch($args[0])
					{
						case 'allow':
						case 'permit': {
							$action = true;
							break;
						}
						case 'forbid':
						case 'deny': {
							$action = false;
							break;
						}
						default: {
							return false;
						}
					}

					$status = $Core_Api_Rule->action($action);

					if($status) {
						$this->_SHELL->print("Action '".$args[0]."' OK!", 'green');								
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
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function source($type, array $args)
		{
			return $this->_srcDst('source', $type, $args);
		}

		/**
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function destination($type, array $args)
		{
			return $this->_srcDst('destination', $type, $args);
		}

		/**
		  * @param string $attribute
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		protected function _srcDst($attribute, $type, array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_Rule = $this->_getEditingRule();

				if($Core_Api_Rule !== false)
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
							try {
								$Core_Api_Rule->testAddressOverlap($attribute, $Core_Api_Address);
							}
							catch(E\Message $e) {
								$this->_SHELL->error($e->getMessage(), 'orange');
							}

							$status = $Core_Api_Rule->configure($attribute, $Core_Api_Address);

							if($status) {
								$this->_SHELL->print(ucfirst($attribute)." '".$Core_Api_Address->name."' OK!", 'green');								
							}
							else {
								$this->_SHELL->error("Impossible d'effectuer l'opération, vérifiez qu'il n'y a pas de doublon ou que la source et la destination n'ont pas d'objets en communs", 'orange');
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
		  * @param string $type
		  * @param array $args
		  * @return bool
		  */
		public function protocol($type, array $args)
		{
			if(isset($args[0]))
			{
				$Core_Api_Rule = $this->_getEditingRule();

				if($Core_Api_Rule !== false)
				{
					$protocol = $args[0];

					if(isset($args[1])) {
						$protocol .= Api\Protocol::PROTO_SEPARATOR.$args[1];
					}

					$protocolApi = new Api\Protocol($protocol, $protocol);
					$status = $protocolApi->protocol($protocol);

					if($status && $protocolApi->isValid())
					{
						$status = $Core_Api_Rule->protocol($protocolApi);

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
						$this->_SHELL->error("Protocole invalide, entrez un protocole par commande et vérifiez sa syntaxe: ip, tcp|udp 12345[-12345], icmp type:code", 'orange');
					}
				}

				return true;
			}

			return false;
		}

		public function reset($attribute = null, $type = null, array $args = null)
		{
			$Core_Api_Rule = $this->_getEditingRule();

			if($Core_Api_Rule !== false)
			{
				if(isset($args[0]))
				{
					$addressFwProgram = $this->_getAddressFwProgram();

					if($addressFwProgram !== false)
					{
						if($addressFwProgram->isType($type))
						{
							$address = $args[0];

							$Core_Api_Address = $addressFwProgram->getObject($type, $address, true);

							if($Core_Api_Address !== false) {
								$object = $Core_Api_Address;
							}
							else {
								$objectName = $addressFwProgram->getName($type);
								$this->_SHELL->error("L'objet ".$objectName." '".$address."' n'existe pas, impossible de réaliser l'opération", 'orange');
								return false;
							}
						}
						elseif($type === Api\Protocol::API_TYPE)
						{
							$protocol = $args[0];

							if(isset($args[1])) {
								$protocol .= Api\Protocol::PROTO_SEPARATOR.$args[1];
							}

							$Core_Api_Protocol = new Api\Protocol($protocol, $protocol);
							$isValidProtocol = $Core_Api_Protocol->protocol($protocol);

							if($isValidProtocol && $Core_Api_Protocol->isValid()) {
								$object = $Core_Api_Protocol;
							}
							else {
								$this->_SHELL->error("Protocole '".$protocol."' non valide, impossible de réaliser l'opération", 'orange');
								return false;
							}
						}
						elseif($type === Api\Tag::API_TYPE)
						{
							$tag = $args[0];

							$Core_Api_Tag = new Api\Tag($tag, $tag);
							$isValidTag = $Core_Api_Tag->tag($tag);

							if($isValidTag && $Core_Api_Tag->isValid()) {
								$object = $Core_Api_Tag;
							}
							else {
								$this->_SHELL->error("Tag '".$tag."' non valide, impossible de réaliser l'opération", 'orange');
								return false;
							}
						}
						else {
							throw new Exception("Object type '".$type."' is not valid", E_USER_ERROR);
						}
					}
					else {
						throw new Exception("Address firewall program is not available", E_USER_ERROR);
					}
				}
				else {
					$type = null;
					$object = null;
				}

				$status = $Core_Api_Rule->reset($attribute, $type, $object);

				if($status) {
					$this->_SHELL->print("Reset OK!", 'green');								
				}
				else {
					$this->_SHELL->error("Impossible d'effectuer l'opération", 'orange');
				}

				return true;
			}

			return false;
		}
	}		