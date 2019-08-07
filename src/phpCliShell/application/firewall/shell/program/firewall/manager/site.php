<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;

	use ArrayObject;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core;
	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer;

	class Site extends AbstractManager
	{
		/**
		  * @var string
		  */
		const MANAGER_TYPE = 'siteManager';

		/**
		  * @var string
		  */
		const MANAGER_LABEL = 'site';

		/**
		  * @var array
		  */
		const API_TYPES = array(
			Api\Site::API_TYPE
		);

		/**
		  * @var array
		  */
		const API_INDEXES = array(
			Api\Site::API_TYPE => Api\Site::API_INDEX
		);

		/**
		  * @var array
		  */
		const API_CLASSES = array(
			Api\Site::API_TYPE => Api\Site::class
		);

		/**
		  * @var string
		  */
		const ALL_SITES = 'all';

		/**
		  * @var \PhpCliShell\Application\Firewall\Core\Sites
		  */
		protected $_coreSites = null;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall $firewallProgram
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Firewall $firewallProgram, ArrayObject $objects)
		{
			parent::__construct($SHELL, $firewallProgram, $objects);

			$this->_coreSites = new Core\Sites();
		}

		/**
		  * @return \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer\Site
		  */
		public function getRenderer()
		{
			return new Renderer\Site();
		}

		/**
		  * @return \PhpCliShell\Application\Firewall\Core\Sites
		  */
		public function getAvailableSites()
		{
			return $this->_coreSites;
		}

		/**
		  * @return \PhpCliShell\Application\Firewall\Core\Site[]
		  */
		public function getEnabledSites()
		{
			$sites = array();

			foreach(self::API_TYPES as $type)
			{
				foreach($this->_objects[$type] as $siteApi) {
					$sites[$siteApi->name] = $this->_coreSites->{$siteApi->name};
				}
			}

			return $sites;
		}

		/**
		  * @param string|\PhpCliShell\Application\Firewall\Core\Site|\PhpCliShell\Application\Firewall\Core\Api\Site $site
		  * @return bool Site is enabled or not
		  */
		public function isSiteEnabled($site)
		{
			if($site instanceof Api\Site) {
				return $this->_isRegistered($site);
			}
			else
			{
				if($site instanceof Core\Site) {
					$site = $site->getName();
				}

				$enabledSites = $this->getEnabledSites();
				return array_key_exists($site, $enabledSites);
			}
		}

		public function create($type, array $args)
		{
			if($this->_typeIsAllowed($type))
			{
				if(isset($args[0]))
				{
					$name = $args[0];
					$availableSites = $this->_coreSites->getSiteKeys();

					if(mb_strtolower($name) !== self::ALL_SITES)
					{
						if(in_array($name, $availableSites, true)) {
							$sites = array($name);
						}
					}
					else {
						$sites = $availableSites;
					}

					if(isset($sites))
					{
						$class = $this->_typeToClass($type);
						$objectName = ucfirst($class::API_LABEL);

						$currentSites = $this->_objects[$class::API_TYPE];
						$missingSites = array_diff($sites, array_keys($currentSites));

						if(count($missingSites) > 0)
						{
							foreach($missingSites as $site)
							{
								$Core_Api_Site = new $class($site, $site);
								$isValid = $Core_Api_Site->isValid();

								if($isValid) {
									$this->_register($Core_Api_Site);
									$this->_SHELL->error($objectName." '".$site."' activé", 'green');
								}
								else {
									$this->_SHELL->error($objectName." '".$site."' invalide", 'orange');
								}
							}
						}
						else {
							$this->_SHELL->error("Site(s) déjà activé(s)", 'orange');
						}
					}
					else {
						$this->_SHELL->error("Le site '".$name."' n'existe pas", 'orange');
					}

					return true;
				}
			}

			return false;
		}

		public function modify($type, array $args)
		{
			return false;
		}

		public function rename($type, array $args)
		{
			return false;
		}

		public function remove($type, array $args)
		{
			if(isset($args[0]))
			{
				$name = $args[0];

				if(mb_strtolower($name) === self::ALL_SITES) {
					return $this->clear($type);
				}
				else
				{
					if(($Core_Api_Site = $this->getObject($type, $name)) !== false) {
						$this->_unregister($Core_Api_Site);
						$objectName = ucfirst($Core_Api_Site::API_LABEL);
						$this->_SHELL->print($objectName." '".$name."' supprimé", 'green');
					}
					else {
						$objectName = $this->getName($type, true);
						$this->_SHELL->print($objectName." '".$name."' introuvable", 'orange');
					}
				}

				return true;
			}

			return false;
		}

		public function locate($type, $search, $strict = false)
		{
			return false;
		}

		public function filter($type, $filter, $strict = false)
		{
			return false;
		}

		/**
		  * @param mixed $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'coreSites': {
					return $this->getAvailableSites();
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}