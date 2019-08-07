<?php
	namespace PhpCliShell\Application\Firewall\Core;

	use Closure;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Network;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Core\Exception;

	class Firewall
	{
		/**
		  * Firewall site
		  * @var \PhpCliShell\Application\Firewall\Core\Site
		  */
		protected $_site = null;

		/**
		  * Firewall name
		  * @var string
		  */
		protected $_name = null;

		/**
		  * @var array
		  */
		protected $_objects = array(
			Api\Host::API_TYPE => array(),
			Api\Subnet::API_TYPE => array(),
			Api\Network::API_TYPE => array(),
			Api\AclRule::API_TYPE => array(),
			Api\NatRule::API_TYPE => array(),
		);


		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Site $site
		  * @return $this
		  */
		public function __construct(Site $site)
		{
			$this->_site = $site;
			$this->name($site->hostname);
		}

		public function name($name)
		{
			if(C\Tools::is('string&&!empty', $name) || C\Tools::is('int&&>=0', $name)) {
				$this->_name = (string) $name;
				return true;
			}

			return false;
		}

		public function addObject($object)
		{
			if($object instanceof Api\Host) {
				$this->addHost($object);
			}
			elseif($object instanceof Api\Subnet) {
				$this->addSubnet($object);
			}
			elseif($object instanceof Api\Network) {
				$this->addNetwork($object);
			}
			elseif($object instanceof Api\AclRule) {
				$this->addAclRule($object);
			}
			elseif($object instanceof Api\NatRule) {
				$this->addNatRule($object);
			}
			else {
				$class = (is_object($object)) ? (get_class($object)) : ('');
				throw new Exception("Object type '".gettype($object)."'@'".$class."' is not allowed", E_USER_ERROR);
			}

			return $this;
		}

		public function addHost(Api\Host $host)
		{
			return $this->_addObject($host);
		}

		public function addSubnet(Api\Subnet $subnet)
		{
			return $this->_addObject($subnet);
		}

		public function addNetwork(Api\Network $network)
		{
			return $this->_addObject($network);
		}

		public function addAclRule(Api\AclRule $rule)
		{
			return $this->_addObject($rule);
		}

		public function addNatRule(Api\NatRule $rule)
		{
			return $this->_addObject($rule);
		}

		protected function _addObject($object)
		{
			$this->_objects[$object::API_TYPE][] = $object;
			return $this;
		}

		public function addHosts(array $hosts)
		{
			return $this->_addObjects(Api\Host::class, $hosts);
		}

		public function addSubnets(array $subnets)
		{
			return $this->_addObjects(Api\Subnet::class, $subnets);
		}

		public function addNetworks(array $networks)
		{
			return $this->_addObjects(Api\Network::class, $networks);
		}

		public function addAclRules(array $rules)
		{
			return $this->_addObjects(Api\AclRule::class, $rules);
		}

		public function addNatRules(array $rules)
		{
			return $this->_addObjects(Api\NatRule::class, $rules);
		}

		protected function _addObjects($class, array $objects)
		{
			$type = $class::API_TYPE;

			foreach($objects as $object)
			{
				if($object instanceof $class) {
					$this->_objects[$type][] = $object;
				}
			}

			return $this;
		}

		public function clearHosts()
		{
			return $this->_clearObjects(Api\Host::class);
		}

		public function clearSubnets()
		{
			return $this->_clearObjects(Api\Subnet::class);
		}

		public function clearNetworks()
		{
			return $this->_clearObjects(Api\Network::class);
		}

		public function clearAclRules()
		{
			return $this->_clearObjects(Api\AclRule::class);
		}

		public function clearNatRules()
		{
			return $this->_clearObjects(Api\NatRule::class);
		}

		protected function _clearObjects($class)
		{
			$this->_objects[$class::API_TYPE] = array();
			return $this;
		}

		/*public function checkObjects($type = null)
		{
			if($type === null) {
				$objectsToCheck = $this->_objects;
			}
			elseif(array_key_exists($type, $this->_objects)) {
				$objectsToCheck = array($type => $this->_objects);
			}
			else {
				throw new Exception("Object type '".$type." is not valid", E_USER_ERROR); 
			}

			$invalidObjects = array();

			foreach($objectsToCheck as $type => $objects)
			{
				foreach($objects as $object)
				{
					if(!$object->isValid()) {
						$invalidObjects[] = $object;
					}
				}
			}

			return $invalidObjects;
		}*/

		/**
		  * @param null|\Closure $tunnelCallback
		  * @param null|\Closure $sessionCallback
		  * @return \PhpCliShell\Core\Network\Ssh Remote SSH
		  */
		public function ssh($tunnelCallback = null, $sessionCallback = null)
		{
			$siteName = $this->_site->getName();

			if($this->_site->scp === true)
			{
				$sshIp = $this->_site->ip;
				$sshPort = $this->_site->ssh_remotePort;
				$sshOptions = null;

				if($sshIp !== false && $sshPort !== false)
				{
					$sshBastionHost = $this->_site->ssh_bastionHost;
					$sshBastionPort = $this->_site->ssh_bastionPort;
					$sshPortForwarding = $this->_site->ssh_portForwarding;

					if($sshBastionHost !== false && $sshBastionPort !== false && $sshPortForwarding !== false)
					{
						list($sshSysLogin, $sshSysPassword) = $this->_getCredentials($this->_site, 'ssh');

						if($sshSysLogin !== false)
						{
							$tunnelSSH = new Network\Ssh($sshIp, $sshPort, $sshBastionHost, $sshBastionPort, $sshPortForwarding);

							if($sshSysPassword === false) {
								$tunnelSSH->useSshAgent($sshSysLogin);
							}
							else {
								$tunnelSSH->setCredentials($sshSysLogin, $sshSysPassword);
							}

							$tunnelIsConnected = $tunnelSSH->connect();

							if($tunnelIsConnected)
							{
								if($tunnelCallback instanceof Closure) {
									$tunnelCallback($tunnelSSH);
								}

								$sshIp = '127.0.0.1';
								$sshPort = $sshPortForwarding;
								$sshOptions = array(
									'LogLevel=ERROR',
									'StrictHostKeyChecking=no',
									'UserKnownHostsFile=/dev/null'
								);
							}
							else {
								$tunnelSSH->disconnect();
								throw new E\Message("Impossible de se connecter en SSH au bastion du site '".$site."' (".$sshBastionHost.":".$sshBastionPort.")", E_USER_WARNING);
							}
						}
						else {
							throw new E\Message("Identifiant (et mot de passe) système pour le SSH manquants", E_USER_WARNING);
						}
					}

					list($sshNetLogin, $sshNetPassword) = $this->_getCredentials($this->_site, 'scp');

					if($sshNetLogin !== false)
					{
						$remoteSSH = new Network\Ssh($sshIp, $sshPort, null, null, null, false);
						$remoteSSH->setOptions($sshOptions);

						if($sshNetPassword === false) {
							$remoteSSH->useSshAgent($sshNetLogin);
						}
						else {
							$remoteSSH->setCredentials($sshNetLogin, $sshNetPassword);
						}

						if(isset($tunnelSSH)) {
							$remoteSSH->bastionSSH($tunnelSSH);
						}

						$sessionIsConnected = $remoteSSH->connect();

						if($sessionIsConnected)
						{
							if($sessionCallback instanceof Closure) {
								$sessionCallback($remoteSSH);
							}

							return $remoteSSH;
						}
						else {
							// remoteSSH will be destruct (disconnected), it is not used anymore
							throw new E\Message("Impossible de se connecter en SSH au site '".$siteName."' (".$sshIp.":".$sshPort.")", E_USER_WARNING);
						}
					}
					else {
						// tunnelSSH will be destruct (disconnected), it is not used anymore
						throw new E\Message("Identifiant (et mot de passe) réseau pour le SSH manquants", E_USER_WARNING);
					}
				}
				else {
					throw new E\Message("IP et port SSH absents pour le site '".$siteName."'", E_USER_WARNING);
				}
			}
			else {
				throw new E\Message("SCP n'est pas activé pour le site '".$siteName."'", E_USER_WARNING);
			}
		}

		protected function _getCredentials(Site $site, $prefix = null, $suffix = null)
		{
			if(C\Tools::is('string&&!empty', $prefix)) {
				$prefix = $prefix.'_';
			}

			if(C\Tools::is('string&&!empty', $suffix)) {
				$suffix = '_'.$suffix;
			}

			return C\Tools::getCredentials($site, $prefix, $suffix, false, false);
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'site': {
					return $this->_site;
				}
				case 'siteName': {
					return $this->_site->getName();
				}
				case 'name':
				case 'label':
				case 'hostname': {
					return $this->_name;
				}
				case 'ip': {
					return $this->_site->get('ip');
				}
				case 'host':
				case 'hosts': {
					return $this->_objects[Api\Host::API_TYPE];
				}
				case 'subnet':
				case 'subnets': {
					return $this->_objects[Api\Subnet::API_TYPE];
				}
				case 'network':
				case 'networks': {
					return $this->_objects[Api\Network::API_TYPE];
				}
				case 'aclRule':
				case 'aclRules': {
					return $this->_objects[Api\AclRule::API_TYPE];
				}
				case 'natRule':
				case 'natRules': {
					return $this->_objects[Api\NatRule::API_TYPE];
				}
				case 'object':
				case 'objects': {
					return $this->_objects;
				}
				default: {
					throw new Exception("Attribute name '".$name."' does not exist", E_USER_ERROR);
				}
			}
		}
	}