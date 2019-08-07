<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall;

	use ArrayObject;

	use PhpCliShell\Cli;

	use PhpCliShell\Application\Firewall\Shell\Program;
	use PhpCliShell\Application\Firewall\Shell\Exception;

	use PhpCliShell\Application\Firewall\Core;
	use PhpCliShell\Application\Firewall\Core\Api;

	class Store
	{
		/**
		  * @var \PhpCliShell\Cli\Shell\Main
		  */
		protected $_SHELL = null;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\Site
		  */
		protected $_siteProgram = null;

		/**
		  * @var \ArrayObject
		  */
		protected $_objects = null;

		/**
		  * @var array
		  */
		protected $_firewalls = array();


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall $firewallProgram
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Program\Firewall $firewallProgram, ArrayObject $objects)
		{
			$this->_SHELL = $SHELL;
			$this->_objects = $objects;
		}

		/**
		  * @param string $name Site or firewall name
		  * @return false|\PhpCliShell\Application\Firewall\Core\Firewall
		  */
		public function getFirewall($name)
		{
			if(array_key_exists($name, $this->_firewalls)) {
				return $this->_firewalls[$name];
			}
			else
			{
				foreach($this->_firewalls as $firewall)
				{
					if($firewall->name === $name) {
						return $firewall;
					}
				}

				return false;
			}
		}

		/**
		  * @return array
		  */
		public function getFirewalls()
		{
			return $this->_firewalls;
		}

		/**
		  * @return bool
		  */
		public function refresh()
		{
			$sites = $this->_getSiteProgram()->getEnabledSites();

			$toDelete = array_diff_key($this->_firewalls, $sites);
			$toCreate = array_diff_key($sites, $this->_firewalls);

			foreach($toDelete as $siteName => $Core_Firewall) {
				unset($this->_firewalls[$siteName]);
			}

			foreach($toCreate as $siteName => $coreSite) {
				$this->_firewalls[$siteName] = new Core\Firewall($coreSite);
			}

			return true;
		}

		/**
		  * @return bool
		  */
		public function synchronize()
		{
			foreach($this->_firewalls as $firewall) {
				$firewall->clearHosts()->addHosts($this->_objects[Api\Host::API_TYPE]);
				$firewall->clearSubnets()->addSubnets($this->_objects[Api\Subnet::API_TYPE]);
				$firewall->clearNetworks()->addNetworks($this->_objects[Api\Network::API_TYPE]);
				$firewall->clearAclRules()->addAclRules($this->_objects[Api\AclRule::API_TYPE]);
				$firewall->clearNatRules()->addNatRules($this->_objects[Api\NatRule::API_TYPE]);
			}

			return true;
		}

		protected function _getSiteProgram()
		{
			/**
			  * Ne pas déplacer dans __construct car les affectations
			  * des programmes n'auront pas encore été effectuées
			  *
			  * Concerne:
			  * - $SHELL->program
			  * - $firewallProgram->getProgram(x);
			  */

			if($this->_siteProgram === null) {
				$this->_siteProgram = $this->_SHELL->program->getProgram('site');
			}

			return $this->_siteProgram;
		}

		/**
		  * @param mixed $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'firewalls': {
					return $this->getFirewalls();
				}
				default:
				{
					$firewall = $this->getFirewall($name);

					if($firewall !== false) {
						return $firewall;
					}
					else {
						throw new Exception("Attribute name '".$name."' does not exist", E_USER_ERROR);
					}
				}
			}
		}
	}