<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program;

	use ArrayObject;
	use SplFileObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core;
	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Helper;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Component\Resolver;

	require_once(COMPOSER_ROOT_DIR.'/autoload.php');

	use Symfony\Component\Console\Application as SF_Application;

	class Firewall extends Cli\Shell\Program\Program
	{
		const SEARCH_FROM_CURRENT_CONTEXT = false;
		const SHELL_AC__SRC_DST__MIN_SEARCH_LEN = 3;

		protected $_LIST_TITLES = array(
			Api\Site::API_TYPE => 'SITES',
			Api\Host::API_TYPE => 'HOSTS',
			Api\Subnet::API_TYPE => 'SUBNETS',
			Api\Network::API_TYPE => 'NETWORKS',
			Api\AclRule::API_TYPE => 'RULES',
			Api\NatRule::API_TYPE => 'TRANSLATIONS'
		);

		protected $_LIST_FIELDS = array(
			Api\Site::API_TYPE => array(
				'fields' => array('name', 'equipment'),
				'format' => "%s\t\t\t\t\t\t%s",
				'zones' => array(
					'fields' => array('zone', 'ipv', 'filter'),
					'format' => "\t- %s\t\t%s\t\t%s"
				),
			),
			Api\Host::API_TYPE => array(
				'fields' => array('name', 'addressV4', 'addressV6'),
				'format' => "%s\t\t\t\t\t\t\t\t%s\t\t\t%s"
			),
			Api\Subnet::API_TYPE => array(
				'fields' => array('name', 'subnetV4', 'subnetV6'),
				'format' => "%s\t\t\t\t\t\t\t\t%s\t\t\t%s"
			),
			Api\Network::API_TYPE => array(
				'fields' => array('name', 'networkV4', 'networkV6'),
				'format' => "%s\t\t\t\t\t\t\t\t%s\t\t\t%s"
			),
			Api\AclRule::API_TYPE => array(
				'fields' => array('id', 'category', 'fullmesh', 'state', 'action', 'description', 'tags', 'date'),
				'format' => "[%d]\t{%s}\t\tFullmesh: %s\t\tStatus: %s\t\tAction: %s\t\t(%s)\t\t%s\t\t@%s",
				'sources' => array(
					'fields' => array('source'),
					'format' => "%s [%s] {%s}"
				),
				'destinations' => array(
					'fields' => array('destination'),
					'format' => "%s [%s] {%s}"
				),
				'protocols' => array(
					'fields' => array('protocol'),
					'format' => "%s"
				),
				'tags' => array(
					'fields' => array('tag'),
					'format' => "\t- %s"
				)
			),
			Api\NatRule::API_TYPE => array(
				'fields' => array('id', 'direction', 'srcZone', 'dstZone', 'state', 'description', 'tags', 'date'),
				'format' => "[%d]\t{%s}\t\tSource zone: %s\t\tDest zone: %s\t\tStatus: %s\t\t(%s)\t\t%s\t\t@%s",
				'sources' => array(
					'fields' => array('source'),
					'format' => "%s [%s] {%s}"
				),
				'destinations' => array(
					'fields' => array('destination'),
					'format' => "%s [%s] {%s}"
				),
				'protocols' => array(
					'fields' => array('protocol'),
					'format' => "%s"
				),
				'tags' => array(
					'fields' => array('tag'),
					'format' => "\t- %s"
				)
			)
		);

		protected $_PRINT_TITLES = array(
			Api\Site::API_TYPE => 'SITES',
			Api\Host::API_TYPE => 'HOSTS',
			Api\Subnet::API_TYPE => 'SUBNETS',
			Api\Network::API_TYPE => 'NETWORKS',
			Api\AclRule::API_TYPE => 'RULES',
			Api\NatRule::API_TYPE => 'TRANSLATIONS'
		);

		protected $_PRINT_FIELDS = array(
			Api\Site::API_TYPE => array(
				'name' => 'Name: %s',
				'equipment' => 'Firewall: %s',
				'zones' => PHP_EOL.'Zones:'.PHP_EOL.'%s',
			),
			Api\Host::API_TYPE => array(
				'name' => 'Name: %s',
				'addressV4' => 'Address IPv4: %s',
				'addressV6' => 'Address IPv6: %s',
			),
			Api\Subnet::API_TYPE => array(
				'name' => 'Name: %s',
				'subnetV4' => 'Subnet V4: %s',
				'subnetV6' => 'Subnet V6: %s',
			),
			Api\Network::API_TYPE => array(
				'name' => 'Name: %s',
				'networkV4' => 'Network V4: %s',
				'networkV6' => 'Network V6: %s',
			),
			Api\AclRule::API_TYPE => array(
				'id' => 'ID: %s',
				'date' => 'Date: %s',
				'category' => 'Type: %s',
				'fullmesh' => 'Fullmesh: %s',
				'description' => 'Description: %s',
				'tags' => 'Tags:'.PHP_EOL.'%s',
				'state' => PHP_EOL.'Status: %s',
				'action' => 'Action: %s',
				'acl' => PHP_EOL.'%s',
			),
			Api\NatRule::API_TYPE => array(
				'id' => 'ID: %s',
				'date' => 'Date: %s',
				'direction' => 'Direction: %s',
				'srcZone' => 'Source zone: %s',
				'dstZone' => 'Destination zone: %s',
				'description' => 'Description: %s',
				'tags' => 'Tags:'.PHP_EOL.'%s',
				'state' => PHP_EOL.'Status: %s',
				'terms' => PHP_EOL.'Matches'.PHP_EOL.'%s',
				'rules' => PHP_EOL.'Translations'.PHP_EOL.'%s',
			)
		);

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\Site
		  */
		protected $_siteFwProgram;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\Address
		  */
		protected $_addressFwProgram;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\AclRule
		  */
		protected $_aclRuleFwProgram;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\NatRule
		  */
		protected $_natRuleFwProgram;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Ipam
		  */
		protected $_ipamFwProgram;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config
		  */
		protected $_configFwProgram;

		/**
		  * @var array
		  */
		protected $_sites = array();

		/**
		  * @var array
		  */
		protected $_hosts = array();

		/**
		  * @var array
		  */
		protected $_subnets = array();

		/**
		  * @var array
		  */
		protected $_networks = array();

		/**
		  * @var array
		  */
		protected $_aclRules = array();

		/**
		  * @var array
		  */
		protected $_natRules = array();

		/**
		  * @var \ArrayObject
		  */
		protected $_objects = null;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL)
		{
			parent::__construct($SHELL);

			$this->_objects = new ArrayObject(array(
				Api\Site::API_TYPE => &$this->_sites,
				Api\Host::API_TYPE => &$this->_hosts,
				Api\Subnet::API_TYPE => &$this->_subnets,
				Api\Network::API_TYPE => &$this->_networks,
				Api\AclRule::API_TYPE => &$this->_aclRules,
				Api\NatRule::API_TYPE => &$this->_natRules,
			), ArrayObject::ARRAY_AS_PROPS);

			// @todo do not pass $this, use $SHELL->program instead of this
			$this->_ipamFwProgram = new Firewall\Ipam($SHELL, $this, $this->_objects);
			$this->_storeFwProgram = new Firewall\Store($SHELL, $this, $this->_objects);
			$this->_configFwProgram = new Firewall\Config($SHELL, $this, $this->_objects);
			$this->_siteFwProgram = new Manager\Site($SHELL, $this, $this->_objects);
			$this->_addressFwProgram = new Manager\Address($SHELL, $this, $this->_objects);
			$this->_aclRuleFwProgram = new Manager\AclRule($SHELL, $this, $this->_objects);
			$this->_natRuleFwProgram = new Manager\NatRule($SHELL, $this, $this->_objects);

			Resolver::setManager($this->_siteFwProgram);
			Resolver::setManager($this->_addressFwProgram);
			Resolver::setManager($this->_aclRuleFwProgram);
			Resolver::setManager($this->_natRuleFwProgram);

			$this->_programs = array(
				'ipam' => $this->_ipamFwProgram,
				'store' => $this->_storeFwProgram,
				'config' => $this->_configFwProgram,
				'site' => $this->_siteFwProgram,
				'address' => $this->_addressFwProgram,
				'aclRule' => $this->_aclRuleFwProgram,
				'natRule' => $this->_natRuleFwProgram,
			);
		}

		// OBJECT > CREATE
		// --------------------------------------------------
		public function createSite(array $args)
		{
			$status = $this->_siteFwProgram->create(Api\Site::API_TYPE, $args);

			if(!$this->_storeFwProgram->refresh()) {
				throw new Exception("Unable to refresh firewall objects", E_USER_ERROR);
			}
			else {
				return $this->_setHasChanges($status);
			}
		}

		public function createHost(array $args)
		{
			$status = $this->_addressFwProgram->create(Api\Host::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function createSubnet(array $args)
		{
			$status = $this->_addressFwProgram->create(Api\Subnet::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function createNetwork(array $args)
		{
			$status = $this->_addressFwProgram->create(Api\Network::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function createRule(array $args)
		{
			$this->rule_exit();
			$status = $this->_aclRuleFwProgram->create(Api\AclRule::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function createTranslation(array $args)
		{
			$this->rule_exit();
			$status = $this->_natRuleFwProgram->create(Api\NatRule::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function cloneRule(array $args)
		{
			$this->rule_exit();
			$status = $this->_aclRuleFwProgram->clone(Api\AclRule::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function cloneTranslation(array $args)
		{
			$this->rule_exit();
			$status = $this->_natRuleFwProgram->clone(Api\NatRule::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}
		// --------------------------------------------------

		// OBJECT > MODIFY
		// --------------------------------------------------
		public function modifyHost(array $args)
		{
			$status = $this->_addressFwProgram->modify(Api\Host::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function modifySubnet(array $args)
		{
			$status = $this->_addressFwProgram->modify(Api\Subnet::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function modifyNetwork(array $args)
		{
			$status = $this->_addressFwProgram->modify(Api\Network::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function modifyRule(array $args)
		{
			$this->rule_exit();
			$status = $this->_aclRuleFwProgram->modify(Api\AclRule::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function modifyTranslation(array $args)
		{
			$this->rule_exit();
			$status = $this->_natRuleFwProgram->modify(Api\NatRule::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}
		// --------------------------------------------------

		// OBJECT > REFRESH
		// --------------------------------------------------
		public function refreshHost(array $args)
		{
			$status = $this->_addressFwProgram->refresh(Api\Host::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function refreshSubnet(array $args)
		{
			$status = $this->_addressFwProgram->refresh(Api\Subnet::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function refreshNetwork(array $args)
		{
			$status = $this->_addressFwProgram->refresh(Api\Network::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function refreshHosts()
		{
			$status = $this->_addressFwProgram->refreshAll(Api\Host::API_TYPE);
			return $this->_setHasChanges($status);
		}

		public function refreshSubnets()
		{
			$status = $this->_addressFwProgram->refreshAll(Api\Subnet::API_TYPE);
			return $this->_setHasChanges($status);
		}

		public function refreshNetworks()
		{
			$status = $this->_addressFwProgram->refreshAll(Api\Network::API_TYPE);
			return $this->_setHasChanges($status);
		}
		// --------------------------------------------------

		// OBJECT > REPLACE
		// --------------------------------------------------
		public function replace(array $args)
		{
			if(count($args) === 4) {
				list($badType, $badName, $newType, $newName) = $args;
				$status = $this->_aclRuleFwProgram->replace(Api\AclRule::API_TYPE, $badType, $badName, $newType, $newName);
				return $this->_setHasChanges($status);
			}
			else {
				return false;
			}
		}
		// --------------------------------------------------

		// OBJECT > RENAME
		// --------------------------------------------------
		public function renameHost(array $args)
		{
			$status = $this->_addressFwProgram->rename(Api\Host::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function renameSubnet(array $args)
		{
			$status = $this->_addressFwProgram->rename(Api\Subnet::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function renameNetwork(array $args)
		{
			$status = $this->_addressFwProgram->rename(Api\Network::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function renameRule(array $args)
		{
			$status = $this->_aclRuleFwProgram->rename(Api\AclRule::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function renameTranslation(array $args)
		{
			$status = $this->_natRuleFwProgram->rename(Api\NatRule::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}
		// --------------------------------------------------

		// OBJECT > REMOVE
		// --------------------------------------------------
		public function removeSite(array $args)
		{
			$status = $this->_siteFwProgram->remove(Api\Site::API_TYPE, $args);

			if(!$this->_storeFwProgram->refresh()) {
				throw new Exception("Unable to refresh firewall objects", E_USER_ERROR);
			}
			else {
				return $this->_setHasChanges($status);
			}
		}

		public function removeHost(array $args)
		{
			$status = $this->_addressFwProgram->remove(Api\Host::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function removeSubnet(array $args)
		{
			$status = $this->_addressFwProgram->remove(Api\Subnet::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function removeNetwork(array $args)
		{
			$status = $this->_addressFwProgram->remove(Api\Network::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function removeRule(array $args)
		{
			$status = $this->_aclRuleFwProgram->remove(Api\AclRule::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}

		public function removeTranslation(array $args)
		{
			$status = $this->_natRuleFwProgram->remove(Api\NatRule::API_TYPE, $args);
			return $this->_setHasChanges($status);
		}
		// --------------------------------------------------

		// OBJECT > CLEAR
		// --------------------------------------------------
		public function clearAll()
		{
			$this->clearSites();
			$this->clearHosts();
			$this->clearSubnets();
			$this->clearNetworks();
			$this->clearRules();
			$this->clearTranslations();
			return true;
		}

		public function clearSites()
		{
			$status = $this->_siteFwProgram->clear(Api\Site::API_TYPE);
			return $this->_setHasChanges($status);
		}

		public function clearHosts()
		{
			$status = $this->_addressFwProgram->clear(Api\Host::API_TYPE);
			return $this->_setHasChanges($status);
		}

		public function clearSubnets()
		{
			$status = $this->_addressFwProgram->clear(Api\Subnet::API_TYPE);
			return $this->_setHasChanges($status);
		}

		public function clearNetworks()
		{
			$status = $this->_addressFwProgram->clear(Api\Network::API_TYPE);
			return $this->_setHasChanges($status);
		}

		public function clearRules()
		{
			$status = $this->_aclRuleFwProgram->clear(Api\AclRule::API_TYPE);
			return $this->_setHasChanges($status);
		}

		public function clearTranslations()
		{
			$status = $this->_natRuleFwProgram->clear(Api\NatRule::API_TYPE);
			return $this->_setHasChanges($status);
		}
		// --------------------------------------------------

		// OBJECT > SHOW
		// --------------------------------------------------
		//@todo rename showAll() like clearAll
		public function showConfig()
		{
			if($this->_aclRuleFwProgram->isEditingRule()) {
				$editingRuleName = $this->_aclRuleFwProgram->getEditingRuleName();
				$this->showRule(array($editingRuleName));
			}
			elseif($this->_natRuleFwProgram->isEditingRule()) {
				$editingRuleName = $this->_natRuleFwProgram->getEditingRuleName();
				$this->showTranslation(array($editingRuleName));
			}
			else {
				$this->printObjectsList();
			}

			return true;
		}

		public function showSite(array $args)
		{
			return $this->_showObject(Api\Site::API_TYPE, $args);
		}

		public function showHost(array $args)
		{
			return $this->_showObject(Api\Host::API_TYPE, $args);
		}

		public function showSubnet(array $args)
		{
			return $this->_showObject(Api\Subnet::API_TYPE, $args);
		}

		public function showNetwork(array $args)
		{
			return $this->_showObject(Api\Network::API_TYPE, $args);
		}

		public function showRule(array $args)
		{
			return $this->_showObject(Api\AclRule::API_TYPE, $args);
		}

		public function showTranslation(array $args)
		{
			return $this->_showObject(Api\NatRule::API_TYPE, $args);
		}

		protected function _showObject($type, array $args)
		{
			if(isset($args[0]))
			{
				$name = $args[0];

				if(($class = Resolver::getClass($type)) !== false)
				{
					$objects = $this->_getItemObjects($type, $name);

					if(count($objects) > 0) {
						$this->_RESULTS->append($objects);
						$infos = $this->_formatObjects($type, $objects, Renderer\AbstractRenderer::VIEW_EXTENSIVE);
						$this->_printInformations($type, $infos);
					}
					else {
						$this->_SHELL->error(ucfirst($class::API_LABEL)." '".$name."' introuvable", 'orange');
					}
				}
				else {
					throw new Exception("Unknown object type '".$type."'", E_USER_ERROR);
				}

				return true;
			}

			return false;
		}

		public function showSites()
		{
			return $this->_showObjectsInfos(Api\Site::API_TYPE);
		}

		public function showHosts(array $args)
		{
			return $this->_showObjectsInfos(Api\Host::API_TYPE, $args);
		}

		public function showSubnets(array $args)
		{
			return $this->_showObjectsInfos(Api\Subnet::API_TYPE, $args);
		}

		public function showNetworks(array $args)
		{
			return $this->_showObjectsInfos(Api\Network::API_TYPE, $args);
		}

		public function showRules(array $args)
		{
			return $this->_showObjectsInfos(Api\AclRule::API_TYPE, $args);
		}

		public function showTranslations(array $args)
		{
			return $this->_showObjectsInfos(Api\NatRule::API_TYPE, $args);
		}

		protected function _showObjectsInfos($type, array $args = null)
		{
			if(($class = Resolver::getClass($type)) !== false)
			{
				if(isset($args[0])) {
					$objects = $this->_getItemObjects($type, $args[0]);
				}
				else {
					$objects = $this->_objects[$class::API_TYPE];
				}

				if(count($objects) > 0) {
					$this->_RESULTS->append($objects);
					$infos = $this->_formatObjects($type, $objects, Renderer\AbstractRenderer::VIEW_BRIEF);
					$this->_printObjectsList(array($type => $infos));
				}
				else {
					$this->_SHELL->error("Aucun ".$class::API_LABEL." trouvé", 'orange');
				}
			}
			else {
				throw new Exception("Unknown object type '".$type."'", E_USER_ERROR);
			}

			return true;
		}
		// --------------------------------------------------

		// OBJECT > LOCATE
		// --------------------------------------------------
		public function locateHost(array $args)
		{
			return $this->_locate(Api\Host::API_TYPE, $args);
		}

		public function locateSubnet(array $args)
		{
			return $this->_locate(Api\Subnet::API_TYPE, $args);
		}

		public function locateNetwork(array $args)
		{
			return $this->_locate(Api\Network::API_TYPE, $args);
		}

		public function locateRule(array $args)
		{
			return $this->_locate(Api\AclRule::API_TYPE, $args);
		}

		public function locateFlow(array $args)
		{
			return $this->_locate(Api\Flow::API_TYPE, $args);
		}

		public function locateTranslation(array $args)
		{
			return $this->_locate(Api\NatRule::API_TYPE, $args);
		}

		protected function _locate($type, array $args)
		{
			if(count($args) >= 1)
			{
				$strict = (isset($args[1]) && $args[1] === 'exact');

				switch($type)
				{
					case Api\Host::API_TYPE:
					case Api\Subnet::API_TYPE:
					case Api\Network::API_TYPE: {
						$results = $this->_addressFwProgram->locate($type, $args[0], $strict);
						$type = Api\AclRule::API_TYPE;											// Retourne des règles
						break;
					}
					case Api\AclRule::API_TYPE: {
						$results = $this->_aclRuleFwProgram->locate($type, $args[0], $strict);
						break;
					}
					case Api\Flow::API_TYPE: {
						$type = Api\AclRule::API_TYPE;											// Recherche des règles
						$results = $this->_aclRuleFwProgram->locateFlow($type, $args, $strict);		// /!\ Passer l'ensemble des arguments!
						break;
					}
					case Api\NatRule::API_TYPE: {
						$results = $this->_natRuleFwProgram->locate($type, $args[0], $strict);
						break;
					}
					default: {
						return false;
					}
				}

				if(is_array($results) && count($results) > 0) {
					$view = Renderer\AbstractRenderer::VIEW_BRIEF;
					$infos = $this->_formatObjects($type, $results, $view);
					$this->_printObjectsList(array($type => $infos));
					return true;
				}
				else {
					return $results;
				}
			}
			else {
				return false;
			}
		}
		// --------------------------------------------------

		// OBJECT > FILTER
		// --------------------------------------------------
		public function filter($filter, $type)
		{
			if($filter === 'duplicates')
			{
				switch($type)
				{
					case Api\Host::API_TYPE:
					case Api\Subnet::API_TYPE:
					case Api\Network::API_TYPE: {
						$results = $this->_addressFwProgram->filter($type, Manager\Address::FILTER_DUPLICATES);
						break;
					}
					case Api\AclRule::API_TYPE: {
						$results = $this->_aclRuleFwProgram->filter($type, Manager\AclRule::FILTER_DUPLICATES);
						break;
					}
					case Api\Flow::API_TYPE: {
						$type = Api\AclRule::API_TYPE;
						$results = $this->_aclRuleFwProgram->filterFlow($type, Manager\AclRule::FILTER_DUPLICATES);
						break;
					}
					case Api\NatRule::API_TYPE: {
						$results = $this->_natRuleFwProgram->filter($type, Manager\NatRule::FILTER_DUPLICATES);
						break;
					}
					default: {
						return false;
					}
				}

				if(count($results) > 0)
				{
					$items = array();
					$objects = $this->_objects[$type];

					foreach($results as $objectId => $result)
					{
						$item = array();

						foreach($result as $duplicateObjectId) {
							$item[] = $objects[$duplicateObjectId]->name;
						}

						$item = implode(', ', $item);
						$items[] = array($objects[$objectId]->name, $item);
					}

					$table = C\Tools::formatShellTable($items);
					$this->_SHELL->print($table, 'grey');
				}
				else {
					$this->_SHELL->print("Aucun résultat n'a été trouvé pour ce filtre '".$filter."'", 'green');
				}

				return true;
			}

			return false;
		}

		public function filterRules($filter, $type, array $args)
		{
			if($filter === 'duplicates')
			{
				if(isset($args[0]))
				{
					$results = $this->_aclRuleFwProgram->filterAttributes($type, Manager\AclRule::FILTER_DUPLICATES, $args[0]);

					if(count($results) > 0)
					{
						$items = array();
						$objects = $this->_objects[$type];

						foreach($results as $ruleId => $attributes)
						{
							$item = array();

							foreach($attributes as $attribute => $counter) {
								$item[] = $attribute.' {'.$counter.'}';
							}

							$item = implode(', ', $item);
							$items[] = array($objects[$ruleId]->name, $item);
						}

						$table = C\Tools::formatShellTable($items);
						$this->_SHELL->print($table, 'grey');
					}
					else {
						$this->_SHELL->print("Aucun résultat n'a été trouvé pour ce filtre '".$filter."'", 'green');
					}

					return true;
				}
			}

			return false;
		}
		// --------------------------------------------------

		// OBJECT > COMMON RULE
		// --------------------------------------------------
		public function rule_state(array $args)
		{
			$ruleManager = $this->_retrieveActiveRuleManager();

			if($ruleManager !== false) {
				return $ruleManager->state($args);
			}
			else {
				$this->_SHELL->error("Commande ignorée", 'orange');
				return true;
			}
		}

		public function rule_description(array $args)
		{
			$ruleManager = $this->_retrieveActiveRuleManager();

			if($ruleManager !== false) {
				return $ruleManager->description($args);
			}
			else {
				$this->_SHELL->error("Commande ignorée", 'orange');
				return true;
			}
		}

		public function rule_tag($type, array $args)
		{
			$ruleManager = $this->_retrieveActiveRuleManager();

			if($ruleManager !== false) {
				return $ruleManager->tags($type, $args);
			}
			else {
				$this->_SHELL->error("Commande ignorée", 'orange');
				return true;
			}
		}

		public function rule_tags($type, array $args)
		{
			$ruleManager = $this->_retrieveActiveRuleManager();

			if($ruleManager !== false) {
				return $ruleManager->tags($type, $args);
			}
			else {
				$this->_SHELL->error("Commande ignorée", 'orange');
				return true;
			}
		}

		public function rule_check()
		{
			$ruleManager = $this->_retrieveActiveRuleManager();

			if($ruleManager !== false) {
				return $ruleManager->check();
			}
			else {
				$this->_SHELL->error("Commande ignorée", 'orange');
				return true;
			}
		}

		public function rule_reset($attribute = null, $type = null, array $args = null)
		{
			$ruleManager = $this->_retrieveActiveRuleManager();

			if($ruleManager !== false) {
				return $ruleManager->reset($attribute, $type, $args);
			}
			else {
				$this->_SHELL->error("Commande ignorée", 'orange');
				return true;
			}
		}

		/**
		  * @return bool
		  */
		public function rule_exit()
		{
			$ruleManager = $this->_retrieveActiveRuleManager();

			if($ruleManager !== false) {
				$status = $ruleManager->exit();
				return $this->_setHasChanges($status);	// /!\ Important pour l'autosave
			}
			else {
				return false;	// /!\ Indique qu'aucun manager n'était actif
			}
		}
		// --------------------------------------------------

		// OBJECT > ACL RULE
		// --------------------------------------------------
		public function aclRule_category(array $args)
		{
			return $this->_aclRuleFwProgram->category($args);
		}

		public function aclRule_fullmesh(array $args)
		{
			return $this->_aclRuleFwProgram->fullmesh($args);
		}

		public function aclRule_action(array $args)
		{
			return $this->_aclRuleFwProgram->action($args);
		}

		public function aclRule_source($type, array $args)
		{
			return $this->_aclRuleFwProgram->source($type, $args);
		}

		public function aclRule_destination($type, array $args)
		{
			return $this->_aclRuleFwProgram->destination($type, $args);
		}

		public function aclRule_protocol($type, array $args)
		{
			return $this->_aclRuleFwProgram->protocol($type, $args);
		}
		// --------------------------------------------------

		// OBJECT > NAT RULE
		// --------------------------------------------------
		public function natRule_direction(array $args)
		{
			return $this->_natRuleFwProgram->direction($args);
		}

		public function natRule_zone($attribute, array $args)
		{
			return $this->_natRuleFwProgram->zone($attribute, $args);
		}

		public function natRule_source($part, $type, array $args)
		{
			return $this->_natRuleFwProgram->source($part, $type, $args);
		}

		public function natRule_destination($part, $type, array $args)
		{
			return $this->_natRuleFwProgram->destination($part, $type, $args);
		}

		public function natRule_protocol($part, $type, array $args)
		{
			return $this->_natRuleFwProgram->protocol($part, $type, $args);
		}
		// --------------------------------------------------

		// OBJECT > SEARCH
		// --------------------------------------------------
		public function printSearchObjects(array $args, $localSearch = true, $ipamSearch = true, $forceIpamSearch = true)
		{
			if(count($args) === 3)
			{
				$time1 = microtime(true);
				$objects = $this->_searchObjects($args[0], $args[1], $args[2], $localSearch, $ipamSearch, $forceIpamSearch);
				$time2 = microtime(true);

				if($objects !== false)
				{
					$this->_RESULTS->append($objects);
					$this->_SHELL->EOL()->print('RECHERCHE ('.round($time2-$time1).'s)', 'black', 'white', 'bold');

					if(!$this->_SHELL->isOneShotCall())
					{
						if(array_key_exists(Api\Host::API_TYPE, $objects))
						{
							$counter = count($objects[Api\Host::API_TYPE]);
							$this->_SHELL->EOL()->print('HOSTS ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								// /!\ Object Firewall_Api_Host ou ArrayObject
								foreach($objects[Api\Host::API_TYPE] as &$host)
								{
									$host = array(
										$host->name,
										$host->addressV4,
										$host->addressV6
									);
								}
								unset($host);

								$table = C\Tools::formatShellTable($objects[Api\Host::API_TYPE]);
								$this->_SHELL->print($table, 'grey');
							}
							else {
								$this->_SHELL->error('Aucun résultat', 'orange');
							}
						}

						if(array_key_exists(Api\Subnet::API_TYPE, $objects))
						{
							$counter = count($objects[Api\Subnet::API_TYPE]);
							$this->_SHELL->EOL()->print('SUBNETS ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								// /!\ Object Firewall_Api_Subnet ou ArrayObject
								foreach($objects[Api\Subnet::API_TYPE] as &$subnet)
								{
									$subnet = array(
										$subnet->name,
										$subnet->subnetV4,
										$subnet->subnetV6
									);
								}
								unset($subnet);

								$table = C\Tools::formatShellTable($objects[Api\Subnet::API_TYPE]);
								$this->_SHELL->print($table, 'grey');
							}
							else {
								$this->_SHELL->error('Aucun résultat', 'orange');
							}
						}

						if(array_key_exists(Api\Network::API_TYPE, $objects))
						{
							$counter = count($objects[Api\Network::API_TYPE]);
							$this->_SHELL->EOL()->print('NETWORKS ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								foreach($objects[Api\Network::API_TYPE] as &$network)
								{
									$network = array(
										$network->name,
										$network->networkV4,
										$network->networkV6
									);
								}
								unset($network);

								$table = C\Tools::formatShellTable($objects[Api\Network::API_TYPE]);
								$this->_SHELL->print($table, 'grey');
							}
							else {
								$this->_SHELL->error('Aucun résultat', 'orange');
							}
						}

						if(array_key_exists(Api\AclRule::API_TYPE, $objects))
						{
							$counter = count($objects[Api\AclRule::API_TYPE]);
							$this->_SHELL->EOL()->print('ACL RULES ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								$RuleRenderer = Resolver::getRenderer(Api\AclRule::class);

								foreach($objects[Api\AclRule::API_TYPE] as &$rule)
								{
									$ruleBrief = $RuleRenderer->formatToTable($rule, $this->_LIST_FIELDS, Renderer\AbstractRenderer::VIEW_BRIEF);
									$ruleBrief = C\Tools::e($ruleBrief, 'green', false, false, true);

									$ruleSummary = $RuleRenderer->formatToObject($rule, $this->_LIST_FIELDS, Renderer\AbstractRenderer::VIEW_SUMMARY);
									$ruleACL = C\Tools::e($ruleSummary['acl'], 'blue', false, false, true);

									$rule = $ruleBrief.PHP_EOL.$ruleACL;
								}
								unset($rule);

								$this->_SHELL->print(implode(PHP_EOL.PHP_EOL, $objects[Api\AclRule::API_TYPE]), 'grey');
							}
							else {
								$this->_SHELL->error('Aucun résultat', 'orange');
							}
						}

						if(array_key_exists(Api\NatRule::API_TYPE, $objects))
						{
							$counter = count($objects[Api\NatRule::API_TYPE]);
							$this->_SHELL->EOL()->print('NAT RULES ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								$RuleRenderer = Resolver::getRenderer(Api\NatRule::class);

								foreach($objects[Api\NatRule::API_TYPE] as &$rule)
								{
									$ruleBrief = $RuleRenderer->formatToTable($rule, $this->_LIST_FIELDS, Renderer\AbstractRenderer::VIEW_BRIEF);
									$ruleBrief = C\Tools::e($ruleBrief, 'green', false, false, true);

									$ruleSummary = $RuleRenderer->formatToObject($rule, $this->_LIST_FIELDS, Renderer\AbstractRenderer::VIEW_SUMMARY);
									$ruleTerms = C\Tools::e($ruleSummary['terms'], 'blue', false, false, true);
									$ruleRules = C\Tools::e($ruleSummary['rules'], 'blue', false, false, true);

									$rule = $ruleBrief.PHP_EOL.$ruleTerms.PHP_EOL.$ruleRules;
								}
								unset($rule);

								$this->_SHELL->print(implode(PHP_EOL.PHP_EOL, $objects[Api\NatRule::API_TYPE]), 'grey');
							}
							else {
								$this->_SHELL->error('Aucun résultat', 'orange');
							}
						}

						$this->_SHELL->EOL();
					}
				}
				else {
					$this->_SHELL->error("Aucun résultat trouvé", 'orange');
				}

				return true;
			}

			return false;
		}

		protected function _searchObjects($context, $type, $search, $localSearch = true, $ipamSearch = true, $forceIpamSearch = true)
		{
			switch($type)
			{
				case Api\Host::API_TYPE:
				{
					$hosts = array();

					if($localSearch) {
						$hosts = $this->_getHostObjects($search, self::SEARCH_FROM_CURRENT_CONTEXT, $context);
					}

					if($ipamSearch && ($forceIpamSearch || count($hosts) === 0)) {
						$ipamAddresses = $this->_ipamFwProgram->searchAddresses($search);
						$hosts = array_merge($hosts, $ipamAddresses);
					}

					return array(Api\Host::API_TYPE => $hosts);
				}
				case Api\Subnet::API_TYPE:
				{
					$subnets = array();

					if($localSearch) {
						$subnets = $this->_getSubnetObjects($search, self::SEARCH_FROM_CURRENT_CONTEXT, $context);
					}

					if($ipamSearch && ($forceIpamSearch || count($subnets) === 0)) {
						$ipamSubnets = $this->_ipamFwProgram->searchSubnets($search);
						$subnets = array_merge($subnets, $ipamSubnets);
					}

					return array(Api\Subnet::API_TYPE => $subnets);
				}
				case Api\Network::API_TYPE:
				{
					$networks = array();

					if($localSearch) {
						$networks = $this->_getNetworkObjects($search, self::SEARCH_FROM_CURRENT_CONTEXT, $context);
					}

					return array(Api\Network::API_TYPE => $networks);
				}
				case Api\AclRule::API_TYPE:
				{
					$rules = array();

					if($localSearch) {
						$rules = $this->_getAclRuleObjects($search, self::SEARCH_FROM_CURRENT_CONTEXT, $context);
					}

					return array(Api\AclRule::API_TYPE => $rules);
				}
				case Api\NatRule::API_TYPE:
				{
					$rules = array();

					if($localSearch) {
						$rules = $this->_getNatRuleObjects($search, self::SEARCH_FROM_CURRENT_CONTEXT, $context);
					}

					return array(Api\NatRule::API_TYPE => $rules);
				}
				case 'all':
				{
					$hosts = $this->_searchObjects($context, Api\Host::API_TYPE, $search, $localSearch, $ipamSearch, $forceIpamSearch);
					$subnets = $this->_searchObjects($context, Api\Subnet::API_TYPE, $search, $localSearch, $ipamSearch, $forceIpamSearch);
					$networks = $this->_searchObjects($context, Api\Network::API_TYPE, $search, $localSearch, $ipamSearch, $forceIpamSearch);
					$aclRrules = $this->_searchObjects($context, Api\AclRule::API_TYPE, $search, $localSearch, $ipamSearch, $forceIpamSearch);
					$natRules = $this->_searchObjects($context, Api\NatRule::API_TYPE, $search, $localSearch, $ipamSearch, $forceIpamSearch);
					return array_merge($hosts, $subnets, $networks, $aclRrules, $natRules);
				}
				default: {
					$this->_SHELL->error("Unknown object type '".$type."'", 'orange');
					return array();
				}
			}
		}

		protected function _getSiteObjects($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemObjects(Api\Site::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getHostObjects($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemObjects(Api\Host::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getSubnetObjects($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemObjects(Api\Subnet::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getNetworkObjects($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemObjects(Api\Network::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getAclRuleObjects($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemObjects(Api\AclRule::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getNatRuleObjects($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemObjects(Api\NatRule::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getItemObjects($type, $search, $strictKey = false, $strictMatch = false)
		{
			$Shell_Program_Firewall_Object_Abstract = Resolver::getManager($type);
			return $Shell_Program_Firewall_Object_Abstract->getObjects($type, $search, $strictKey, $strictMatch);
		}

		protected function _getSiteInfos($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemInfos(Api\Site::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getHostInfos($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemInfos(Api\Host::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getSubnetInfos($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemInfos(Api\Subnet::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getNetworkInfos($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemInfos(Api\Network::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getAclRuleInfos($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemInfos(Api\AclRule::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getNatRuleInfos($search, $fromCurrentContext = true, $context = null, $strictKey = false, $strictMatch = false)
		{
			return $this->_getItemInfos(Api\NatRule::API_TYPE, $search, $strictKey, $strictMatch);
		}

		protected function _getItemInfos($type, $search, $strictKey = false, $strictMatch = false)
		{
			$objects = $this->_getItemObjects($type, $search, $strictKey, $strictMatch);
			return $this->_formatObjects($type, $objects, Renderer\AbstractRenderer::VIEW_EXTENSIVE);
		}
		// --------------------------------------------------

		// LOCAL
		// --------------------------------------------------
		public function search($type, $search)
		{
			$args = array('.', $type, $search);
			return $this->printSearchObjects($args, true, false, false);
		}
		// --------------------------------------------------

		// IPAM
		// --------------------------------------------------
		public function ipamSearch($type, $search)
		{
			if($this->_ipamFwProgram->isAvailable())
			{
				if($type === 'all') {
					$type = null;
				}

				return $this->_ipamFwProgram->printSearch($type, $search);
			}
			else {
				$this->_SHELL->error("Le service IPAM n'est pas configuré ou est indisponible", 'orange');
				return true;
			}
		}

		public function ipamImport($type, $search)
		{
			if($this->_ipamFwProgram->isAvailable())
			{
				try {
					$Core_Api_Abstract = $this->_addressFwProgram->autoCreateObject($type, $search, false, false);
				}
				catch(E\Message $e) {
					$this->_SHELL->throw($e);
					$Core_Api_Abstract = false;
				}
				catch(\Exception $e) {
					$this->_SHELL->error("Une exception s'est produite durant l'importation des objets de type '".$type."':", 'orange');
					$this->_SHELL->error($e->getMessage(), 'orange');
					$Core_Api_Abstract = false;
				}

				if($Core_Api_Abstract instanceof Api\AbstractApi) {
					$this->_SHELL->print("Objet '".$Core_Api_Abstract->name."' créé avec succès!", 'green');
					$this->_setHasChanges(true);
				}
				elseif($Core_Api_Abstract === null) {
					$this->_SHELL->error("Impossible de trouver l'objet '".$type."' correspondant à '".$search."'", 'orange');
				}
				else {
					$this->_SHELL->error("Impossible d'importer l'objet '".$type."' correspondant à '".$search."'", 'orange');
				}

				return true;
			}
			else {
				$this->_SHELL->error("Le service IPAM n'est pas configuré ou est indisponible", 'orange');
				return true;
			}
		}
		// --------------------------------------------------

		// CONFIG
		// --------------------------------------------------
		public function hasChanges()
		{
			return $this->_configFwProgram->hasChanges;
		}

		public function autoload()
		{
			return $this->_configFwProgram->autoload();
		}

		public function load(array $args)
		{
			return $this->_configFwProgram->load($args);
		}

		public function run(array $args)
		{
			if(isset($args[0]))
			{
				$filename = C\Tools::filename($args[0]);

				if(file_exists($filename))
				{
					if(is_readable($filename))
					{
						$SplFileObject = new SplFileObject($filename, 'r');
						$SplFileObject->setFlags(SplFileObject::DROP_NEW_LINE);

						foreach($SplFileObject as $line => $cmd)
						{
							if(is_string($cmd) && $cmd !== '')
							{
								$cmd = trim($cmd, " \t");

								ob_start();

								try {
									$status = $this->_SHELL->executeCmdCall($cmd);
								}
								catch(\Exception $e) {
									$status = false;
								}

								$buffer = ob_get_clean();
								$this->_SHELL->print($cmd, 'blue');

								if($status) {
									$this->_SHELL->echo(" [OK]", 'green');
									$this->_SHELL->print($buffer, 'green');
								}
								else
								{
									$this->_SHELL->echo(" [KO]", 'red');

									if($buffer !== false) {
										$this->_SHELL->print($buffer, 'orange');
									}

									$this->_SHELL->EOL()->error("An error is occured at line ".($line+1)." of '".$filename."'", 'orange');
									
									if(isset($e)) {
										throw $e;
									}

									break;
								}
							}
						}
					}
					else {
						$this->_SHELL->error("File '".$filename."' is not readable", 'orange');
					}
				}
				else {
					$this->_SHELL->error("File '".$filename."' does not exist", 'orange');
				}

				return true;
			}

			return false;
		}

		public function save(array $args)
		{
			return $this->_configFwProgram->save($args);
		}

		public function import($type, array $args)
		{
			$status = $this->_storeFwProgram->synchronize();

			if($status) {
				return $this->_configFwProgram->import($this->_storeFwProgram->firewalls, $type, $args);
			}
			else {
				$this->_SHELL->error("Une erreur s'est produite lors de la synchronisation des firewalls", 'orange');
				return false;
			}
		}

		public function export($type, array $args)
		{
			$status = $this->_storeFwProgram->synchronize();

			if($status) {
				return $this->_configFwProgram->export($this->_storeFwProgram->firewalls, $type, $args);
			}
			else {
				$this->_SHELL->error("Une erreur s'est produite lors de la synchronisation des firewalls", 'orange');
				return false;
			}
		}

		public function copy($type, array $args)
		{
			$status = $this->_storeFwProgram->synchronize();

			if($status) {
				return $this->_configFwProgram->copy($this->_storeFwProgram->firewalls, $type, $args);
			}
			else {
				$this->_SHELL->error("Une erreur s'est produite lors de la synchronisation des firewalls", 'orange');
				return false;
			}
		}

		public function importFirewall(array $args)
		{
			return $this->_configFwProgram->importFirewall($args);
		}

		protected function _setHasChanges($status)
		{
			if($status === true) {
				$this->_configFwProgram->hasChanges();
			}

			// /!\ Retourner le status! méthode magique
			return $status;
		}
		// --------------------------------------------------

		// Service_Cli_Abstract : SYSTEM METHODS
		// --------------------------------------------------
		public function printObjectInfos(array $args, $fromCurrentContext = true)
		{
			$cases = array(
				Api\Site::API_TYPE => '_getSiteInfos',
				Api\Host::API_TYPE => '_getHostInfos',
				Api\Subnet::API_TYPE => '_getSubnetInfos',
				Api\Network::API_TYPE => '_getNetworkInfos',
				Api\AclRule::API_TYPE => '_getAclRuleInfos',
				Api\NatRule::API_TYPE => '_getNatRuleInfos'
			);

			$result = $this->_printObjectInfos($cases, $args, $fromCurrentContext);

			if($result !== false) {
				list($status, $key, $infos) = $result;
				return $status;
			}
			else {
				return false;
			}
		}

		protected function _getObjects($context = null, array $args = null)
		{
			$sites = $this->_formatObjects(Api\Site::API_TYPE, $this->_sites, Renderer\AbstractRenderer::VIEW_BRIEF);
			$aclRules = $this->_formatObjects(Api\AclRule::API_TYPE, $this->_aclRules, Renderer\AbstractRenderer::VIEW_BRIEF);
			$natRules = $this->_formatObjects(Api\NatRule::API_TYPE, $this->_natRules, Renderer\AbstractRenderer::VIEW_BRIEF);

			return array(
				Api\Site::API_TYPE => $sites,
				Api\Host::API_TYPE => $this->_hosts,
				Api\Subnet::API_TYPE => $this->_subnets,
				Api\Network::API_TYPE => $this->_networks,
				Api\AclRule::API_TYPE => $aclRules,
				Api\NatRule::API_TYPE => $natRules,
			);
		}

		protected function _formatObjects($type, array $objects, $view)
		{
			$objectsF = array();

			switch($type)
			{
				case Api\Site::API_TYPE:
				case Api\Host::API_TYPE:
				case Api\Subnet::API_TYPE:
				case Api\Network::API_TYPE:
				case Api\AclRule::API_TYPE:
				case Api\NatRule::API_TYPE:
				{
					$AbstractRenderer = Resolver::getRenderer($type);

					foreach($objects as $object) {
						$objectsF[] = $AbstractRenderer->formatToObject($object, $this->_LIST_FIELDS, $view);
					}

					break;
				}
				default: {
					$objectsF = $objects;
				}
			}

			return $objectsF;
		}
		// --------------------------------------------------

		// TOOL METHODS
		// --------------------------------------------------
		protected function _retrieveActiveRuleManager()
		{
			if($this->_aclRuleFwProgram->isEditingRule()) {
				return $this->_aclRuleFwProgram;
			}
			elseif($this->_natRuleFwProgram->isEditingRule()) {
				return $this->_natRuleFwProgram;
			}
			else {
				return false;
			}
		}
		// --------------------------------------------------

		// ----------------- AutoCompletion -----------------
		public function shellAutoC_load($cmd, $search = null)
		{
			$cwd = $this->_SHELL->applicationConfig->configuration->paths->configs;
			return $this->shellAutoC_filesystem($cmd, $search, $cwd);
		}

		/**
		  * For false search, that is bad arg, return default values or nothing
		  * For null search, that is no arg (space), return default values
		  * For string search, that is a valid arg, return the values found
		  *
		  * Options return must have key for system and value for user
		  * Key are used by AutoComplete arguments to find the true argument
		  * Value are used by AutoComplete arguments to inform user all available arguments
		  * Be carreful to always return Core\StatusValue object
		  *
		  * @param string $cmd Command
		  * @param false|null|string $search Search
		  * @return \PhpCliShell\Core\StatusValue
		  */
		public function shellAutoC_srcDst($cmd, $search = null)
		{
			$Core_StatusValue = new C\StatusValue(false, array());

			if($this->_aclRuleFwProgram->isEditingRule() && C\Tools::is('string&&!empty', $search))
			{
				if(mb_strlen($search) < self::SHELL_AC__SRC_DST__MIN_SEARCH_LEN) {
					$this->_SHELL->error('L\'autocomplétion est disponible seulement pour les recherches de plus de '.self::SHELL_AC__SRC_DST__MIN_SEARCH_LEN.' caractères', 'orange');
				}
				else
				{
					/**
					  * /!\ Pour eviter le double PHP_EOL (celui du MSG et de la touche ENTREE)
					  * penser à désactiver le message manuellement avec un lineUP
					  */
					$this->_SHELL->displayWaitingMsg(true, false, 'Searching custom or IPAM objects');

					$cmdParts = explode(' ', $cmd);
					$object = end($cmdParts);

					$searchIsValidAddress = false;

					/**
					  * @todo
					  * $this->_ipamFwProgram->xxxx ne prends pas forcement en charge le wildcard * (oui pour un hostname, non pour une adresse IP)
					  * Du coups la recherche par adresse (IP, subnet, network) ne fonctionne pas correctement puisque ne permet qu'une recherche stricte
					  *
					  * Lorsque l'utilisateur commence à taper une IP (ou un subnet ou un network), il doit voir le nom de l'objet trouvé (c'est codé)
					  * mais cela ne fonctionne pas correctement car les tests isIPv46, isSubnetV46 ou isNetworkV46 ne fonctionne qu'avec une adresse valide
					  * donc pour un début d'adresse le champs système retourné sera le nom et non l'adresse de l'objet
					  * Le souci est comment savoir que l'utilisateur commence à rentrer une adresse et non un nom
					  *
					  * Le code fonctionne bien sans bug, c'est juste l'expérience utilisateur qui n'est pas optimale
					  */
					switch($object)
					{
						case Api\Host::API_TYPE:
						{
							$objectFieldName = Api\Host::FIELD_NAME;

							if(Core\Tools::isIPv4($search)) {
								$fieldToReturn = Api\Host::FIELD_ATTRv4;
								$searchIsValidAddress = true;
							}
							elseif(Core\Tools::isIPv6($search)) {
								$fieldToReturn = Api\Host::FIELD_ATTRv6;
								$searchIsValidAddress = true;
							}
							else {
								$fieldToReturn = $objectFieldName;
							}

							$items = $this->_getHostInfos($search, self::SEARCH_FROM_CURRENT_CONTEXT, null, false, false);

							if(count($items) === 0)
							{
								try {
									$ipamAddresses = $this->_ipamFwProgram->getAddresses($search, false);
								}
								catch(\Exception $e) {
									$this->_SHELL->error("[AC] L'erreur suivante s'est produite: ".$e->getMessage(), 'orange');
									$ipamAddresses = array();
								}

								$items = array_merge($items, $ipamAddresses);
							}
							break;
						}
						case Api\Subnet::API_TYPE:
						{
							$objectFieldName = Api\Subnet::FIELD_NAME;

							if(Core\Tools::isSubnetV4($search)) {
								$fieldToReturn = Api\Subnet::FIELD_ATTRv4;
								$searchIsValidAddress = true;
							}
							elseif(Core\Tools::isSubnetV6($search)) {
								$fieldToReturn = Api\Subnet::FIELD_ATTRv6;
								$searchIsValidAddress = true;
							}
							else {
								$fieldToReturn = $objectFieldName;
							}

							$items = $this->_getSubnetInfos($search, self::SEARCH_FROM_CURRENT_CONTEXT, null, false, false);

							if(count($items) === 0)
							{
								try {
									$ipamSubnets = $this->_ipamFwProgram->getSubnets($search, false);
								}
								catch(\Exception $e) {
									$this->_SHELL->error("[AC] L'erreur suivante s'est produite: ".$e->getMessage(), 'orange');
									$ipamSubnets = array();
								}

								$items = array_merge($items, $ipamSubnets);
							}
							break;
						}
						case Api\Network::API_TYPE:
						{
							$objectFieldName = Api\Network::FIELD_NAME;

							if(Core\Tools::isNetworkV4($search, Api\Network::SEPARATOR)) {
								$fieldToReturn = Api\Network::FIELD_ATTRv4;
								$searchIsValidAddress = true;
							}
							elseif(Core\Tools::isNetworkV6($search, Api\Network::SEPARATOR)) {
								$fieldToReturn = Api\Network::FIELD_ATTRv6;
								$searchIsValidAddress = true;
							}
							else {
								$fieldToReturn = $objectFieldName;
							}

							$items = $this->_getNetworkInfos($search, self::SEARCH_FROM_CURRENT_CONTEXT, null, false, false);
							break;
						}
					}

					// Utile car la désactivation doit s'effectuer avec un lineUP, voir message plus haut
					$this->_SHELL->deleteWaitingMsg(true);

					if(isset($items))
					{
						$options = array();

						if(count($items) > 0)
						{
							foreach($items as $item) {
								$options[$item[$fieldToReturn]] = $item[$objectFieldName];
							}
						}
						elseif($searchIsValidAddress)
						{
							/**
							  * Ne pas créer automatiquement l'objet avec getAutoNamingAddress ou autoCreateObject
							  * sinon si l'utilisateur ne valide pas la commande alors l'objet aura été créé pour rien
							  */
							$options[$search] = $search;

							/*if(($addressName = $this->_addressFwProgram->getAutoNamingAddress($search)) !== false)
							{
								$args = array($addressName, $search);
								$status = $this->_addressFwProgram->create($object, $args);

								if($status) {
									$options[$search] = $addressName;
								}
							}*/
						}

						$Core_StatusValue->setStatus(true);
						$Core_StatusValue->setOptions($options);
					}
				}
			}

			return $Core_StatusValue;
		}
		// --------------------------------------------------
	}