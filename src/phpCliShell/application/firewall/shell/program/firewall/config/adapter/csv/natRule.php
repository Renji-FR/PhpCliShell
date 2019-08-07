<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\Csv;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Component\Resolver;

	class NatRule extends AbstractRule
	{
		/**
		  * @var string
		  */
		const PREFIX = '';

		/**
		  * @var string
		  */
		const SUFFIX = '.nat';

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\NatRule
		  */
		protected $_natRuleFwProgram = null;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config $ORCHESTRATOR
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Config $ORCHESTRATOR, ArrayObject $objects)
		{
			parent::__construct($SHELL, $ORCHESTRATOR, $objects);

			$this->_natRuleFwProgram = Resolver::getManager(Manager\NatRule::class);
		}

		/**
		  * @param array $configs Configuration to import
		  * @param bool $keepName Keep name or allow to rewrite it
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @return false|array Number of objects imported
		  */
		protected function _import(array $configs, $keepName, $prefix, $suffix)
		{
			$counterNatRules = $this->importRules($configs, $keepName, $prefix, $suffix, true);

			return array(
				Api\NatRule::class => $counterNatRules
			);
		}

		/**
		  * @param array $natRules NAT rules configuration
		  * @param bool $keepName Keep name or allow to rewrite it
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @param bool $checkValidity
		  * @return int Number of rules imported
		  */
		public function importRules(array $natRules, $keepName, $prefix, $suffix, $checkValidity)
		{
			$csvName = null;
			$ruleCounter = 0;
return 0;
			$type = Api\NatRule::API_TYPE;

			if(!$keepName) {
				$baseRuleName = $this->_natRuleFwProgram->getNextName($type);
			}

			// /!\ Du plus précis au plus large
			$addressTypes = array(
				Api\Host::API_TYPE,
				Api\Subnet::API_TYPE,
				Api\Network::API_TYPE
			);

			// /!\ Important pour $csvName et $append
			usort($natRules, function($a, $b) {
				return strnatcasecmp($a[0], $b[0]);
			});

			foreach($natRules as $index => $rule)
			{
				if(count($rule) !== 14) {
					throw new E\Message("Impossible d'importer les règles NAT, fichier CSV non valide", E_USER_ERROR);
				}

				/**
				  * Permet de garantir l'unicité des noms des règles
				  * Permet de garantir l'idempotence lors de l'export
				  */
				$configRuleName = $rule[0];

				if($csvName === $configRuleName) {
					$append = true;
				}
				else {
					$append = false;
					$csvName = $configRuleName;
					unset($Core_Api_Rule);
				}

				switch($rule[1])
				{
					case Api\NatRule::DIRECTION_TWOWAY: {
						$direction = Api\NatRule::DIRECTION_TWOWAY;
						break;
					}
					default: {
						$direction = Api\NatRule::DIRECTION_ONEWAY;
					}
				}

				$srcZone = $rule[2];
				$dstZone = $rule[3];
				$state = ($rule[4] === 'active');

				$termSource = $rule[5];
				$termDestination = $rule[6];
				$termProtocol = $rule[7];
				$ruleSource = $rule[8];
				$ruleDestination = $rule[9];
				$ruleProtocol = $rule[10];

				$description = $rule[11];
				$tags = $rule[12];
				$timestamp = (int) $rule[13];

				foreach(array('src' => $source, 'dst' => $destination) as $attr => $attribute)
				{
					${'Core_Api_Address__'.$attr} = false;

					/**
					  * On recherche d'abord localement pour l'ensemble des types
					  * puis ensuite si pas de résultat alors on crée l'objet à partir de l'IPAM
					  */
					foreach($addressTypes as $addressType)
					{
						${'Core_Api_Address__'.$attr} = $this->_addressFwProgram->getObject($addressType, $attribute, true);

						if(${'Core_Api_Address__'.$attr} !== false) {
							break;
						}
					}

					if(${'Core_Api_Address__'.$attr} === false)
					{
						foreach($addressTypes as $addressType)
						{
							/**
							  * Peut prendre du temps lors de l'utilisation de l'API IPAM pour les recherches d'adresses
							  * Permet à l'utilisateur de renseigner une adresse dans le CSV sans que celle-ci existe au préalable en local
							  *
							  * /!\ Risque de bug lorsqu'un host et un subnet sont nommés pareil
							  */
							try {
								${'Core_Api_Address__'.$attr} = $this->_addressFwProgram->autoCreateObject($addressType, $attribute, true);
							}
							catch(E\Message $e) {
								$this->_SHELL->throw($e);
							}
							catch(\Exception $e) {
								$this->_SHELL->error($e->getMessage(), 'orange');
							}

							if(is_object(${'Core_Api_Address__'.$attr})) {
								break;
							}
						}
					}
				}

				if($Core_Api_Address__src instanceof Api\Address && $Core_Api_Address__dst instanceof Api\Address)
				{
					$Core_Api_Protocol = new Api\Protocol($protocol, $protocol);
					$isValidProtocol = $Core_Api_Protocol->protocol($protocol);

					if($isValidProtocol && $Core_Api_Protocol->isValid())
					{
						/**
						  * /!\ Si quelque chose se passe mal il faut arrêter l'importation
						  * Le système "append" ne permet pas de poursuivre en cas d'erreur
						  */
						if(!$append)
						{
							if(!C\Tools::is('string&&!empty', $prefix)) $prefix = false;
							if(!C\Tools::is('string&&!empty', $suffix)) $suffix = false;

							if($keepName) {
								$name = $configRuleName;
							}
							else {
								/**
								  * Ne pas utiliser index car certaines règles sont mises à jour
								  * ruleCounter qui correspond exactement au nombre de nouvelles règles
								  */
								$name = $baseRuleName + $ruleCounter;
							}

							$name = $prefix.$name.$suffix;

							try {
								$Core_Api_Rule = $this->_natRuleFwProgram->insert($type, $name, null);
							}
							catch(\Exception $e) {
								$this->_SHELL->throw($e);
								$Core_Api_Rule = null;
							}

							if($Core_Api_Rule instanceof Api\NatRule)
							{
								$Core_Api_Rule->direction($direction);
								$Core_Api_Rule->srcZone($srcZone);
								$Core_Api_Rule->dstZone($dstZone);
								$Core_Api_Rule->state($state);
								$Core_Api_Rule->description($description);

								$tags = preg_split('#(?<!\\\\)'.preg_quote(static::TAG_SEPARATOR, '#').'#i', $tags);

								foreach($tags as $tag)
								{
									$tag = str_ireplace('\\'.static::TAG_SEPARATOR, static::TAG_SEPARATOR, $tag);

									$Core_Api_Tag = new Api\Tag($tag, $tag);
									$tagStatus = $Core_Api_Tag->tag($tag);

									if($tagStatus && $Core_Api_Tag->isValid()) {
										$Core_Api_Rule->tag($Core_Api_Tag);
									}
								}

								$Core_Api_Rule->timestamp($timestamp);
							}
						}

						if($Core_Api_Rule instanceof Api\NatRule)
						{
							/**
							  * /!\ Risque de doublon si Api\NatRule n'a pas la sécurité
							  *
							  * Pour simplifier on essaie d'ajouter à chaque fois source et destination
							  * mais dans certains cas il se peut que cela crée des doublons en source et/ou en destination
							  *
							  * Example: une règle avec une source mais deux destinations sera découpée en deux lignes dans le CSV
							  * Lors du chargement du CSV, on va tenter d'ajouter deux fois la source puisqu'elle figure sur les deux lignes
							  *
							  * Idem pour le protocole, risque de doublon lorsque le changement se situe au niveau de la source ou de la destination
							  *
							  * @todo coder une vérification en amont ou laisser Api\NatRule faire le job de vérification?
							  */
							$Core_Api_Rule->addSource($Core_Api_Address__src);
							$Core_Api_Rule->addDestination($Core_Api_Address__dst);
							$Core_Api_Rule->addProtocol($Core_Api_Protocol);

							$status = false;

							if($Core_Api_Rule->isValid())
							{
								$status = true;

								/**
								  * Afficher juste les messages et ne pas bloquer
								  * afin de permettre à l'utilisateur de corriger
								  */
								try {
									$Core_Api_Rule->checkOverlapAddress();
								}
								catch(E\Message $e) {
									$this->_SHELL->error("RULE '".$Core_Api_Rule->name."': ".$e->getMessage(), 'orange');
								}

								if(!$append) {
									$ruleCounter++;
									$this->_SHELL->print("Règle '".$Core_Api_Rule->name."' (position ".$configRuleName.") importée!", 'green');
								}
								else {
									$this->_SHELL->print("Règle '".$Core_Api_Rule->name."' (position ".$configRuleName.") mis à jour!", 'green');
								}
							}

							if(!$status)
							{
								try {
									$status = $this->_natRuleFwProgram->drop($Core_Api_Rule);
								}
								catch(\Exception $e) {
									$this->_SHELL->throw($e);
									$status = null;
								}

								throw new E\Message("La règle '".$configRuleName."' semble invalide et n'a pas pu être importée", E_USER_ERROR);
							}
						}
						else {
							throw new E\Message("Une erreur s'est produite durant l'importation d'une règle", E_USER_ERROR);
						}
					}
					else {
						throw new E\Message("L'attribut protocole de la règle '".$configRuleName."'est invalide", E_USER_ERROR);
					}
				}
				else {
					throw new E\Message("La règle '".$configRuleName."' possède des attributs incorrects (source ou destination) [".$source."] [".$destination."]", E_USER_ERROR);
				}
			}

			/**
			  * Sécurité afin de s'assurer que l'ordre des règles est correct
			  * /!\ NE PAS EFFECTUER UN KSORT SINON LE GIT DIFF NE SERA PAS LISIBLE
			  */
			//uksort($this->_objects[Api\AclRule::API_TYPE], 'strnatcasecmp');

			return $ruleCounter;
		}

		/**
		  * @param array $configItems Configurations to export
		  * @return false|array Configuration datas
		  */
		protected function _export(array $configs)
		{
			return $this->exportRules($configs);
		}

		/**
		  * @param array $configs Configurations to export
		  * @return array Export datas
		  */
		public function exportRules(array $configs)
		{
			$items = array();

			if(array_key_exists(Api\NatRule::API_TYPE, $configs))
			{
				$rules = $configs[Api\NatRule::API_TYPE];

				foreach($rules as $rule)
				{
					// /!\ CSV fields order !!
					$item = array();
					$item[0] = $rule['name'];
					$item[1] = $rule['direction'];
					$item[2] = $rule['srcZone'];
					$item[3] = $rule['dstZone'];
					$item[4] = ($rule['state']) ? ('active') : ('inactive');
					$item[5] = null;
					$item[6] = null;
					$item[7] = null;
					$item[8] = null;
					$item[9] = null;
					$item[10] = null;
					$item[11] = $rule['description'];
					$item[12] = null;
					$item[13] = $rule['timestamp'];

					array_walk($rule['tags'], function(&$tag) {
						$tag = str_ireplace(static::TAG_SEPARATOR, '\\'.static::TAG_SEPARATOR, $tag);
					});
					unset($tag);

					$item[12] = implode(static::TAG_SEPARATOR, $rule['tags']);

					/**
					  * @todo bug, si source/destination existent en differents types (host, subnet, network) --> faire un check?
					  * /!\ Risque de bug lorsqu'un host et un subnet sont nommés pareil
					  */

					$typeSeparator = preg_quote(Api\NatRule::SEPARATOR_TYPE, '#');
					$typeRegex = '#^([^\s:]+'.$typeSeparator.')#i';

					foreach($rule['terms']['sources'] as $source)
					{
						$item[5] = preg_replace($typeRegex, '', $source);

						foreach($rule['terms']['destinations'] as $destination)
						{
							$item[6] = preg_replace($typeRegex, '', $destination);

							foreach($rule['terms']['protocols'] as $protocol)
							{
								$item[7] = preg_replace($typeRegex, '', $protocol);

								foreach($rule['rules']['sources'] as $source)
								{
									$item[8] = preg_replace($typeRegex, '', $source);

									foreach($rule['rules']['destinations'] as $destination)
									{
										$item[9] = preg_replace($typeRegex, '', $destination);

										foreach($rule['rules']['protocols'] as $protocol) {
											$item[10] = preg_replace($typeRegex, '', $protocol);
											//ksort($item);
											$items[] = $item;
										}
									}
								}
							}
						}
					}

					// /!\ Ne plus modifier $item à partir d'ici
				}
			}

			return $items;
		}
	}