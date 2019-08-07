<?php
	namespace PhpCliShell\Application\Firewall\Core\Template;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core;
	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Core\Exception;

	abstract class AbstractTemplate
	{
		const API_TYPE_SECTION = array(
			Api\Host::API_TYPE => 'hosts',
			Api\Subnet::API_TYPE => 'subnets',
			Api\Network::API_TYPE => 'networks',
			Api\AclRule::API_TYPE => 'aclRules',
			Api\NatRule::API_TYPE => 'natRules',
		);

		const SECTION_TYPE_VAR = array(
			'hosts' => 'hosts',
			'subnets' => 'subnets',
			'networks' => 'networks',
			'aclRules' => 'aclRules',
			'natRules' => 'natRules',
		);

		/**
		  * @var \PhpCliShell\Cli\Shell\Main
		  */
		protected $_SHELL;

		/**
		  * @var \PhpCliShell\Core\Config
		  */
		protected $_CONFIG;

		/**
		  * @var \PhpCliShell\Application\Firewall\Core\Api\Site[]
		  */
		protected $_sites;

		/**
		  * @var \PhpCliShell\Application\Firewall\Core\Firewall
		  */
		protected $_firewall;

		/**
		  * @var \PhpCliShell\Application\Firewall\Core\Site
		  */
		protected $_site;

		/**
		  * @var \PhpCliShell\Application\Firewall\Core\Api\Site
		  */
		protected $_siteApi;

		/**
		  * Script filename
		  * @var string
		  */
		protected $_script;

		/**
		  * Export filename
		  * @var string
		  */
		protected $_export;

		/**
		  * Debug mode
		  * @var bool
		  */
		protected $_debug = false;


		/**
		  * @param \PhpCliShell\Shell\Service\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Site[] $sites
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, array $sites)
		{
			$this->_SHELL = $SHELL;
			$this->_sites = $sites;

			$this->_CONFIG = $SHELL->applicationConfig;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Firewall $firewall
		  * @param array $sections
		  * @return bool
		  */
		public function templating(Core\Firewall $firewall, array $sections)
		{
			$this->_firewall = $firewall;
			$this->_site = $firewall->site;

			$this->_script = $this->_getScript();
			$this->_export = $this->_getExport();

			$objects = array();

			foreach($sections as $section)
			{
				// @todo array objects a la place?? a voir
				if(array_key_exists($section, self::SECTION_TYPE_VAR)) {
					$varName = self::SECTION_TYPE_VAR[$section];
					$objects[$section] = $firewall->{$varName};
				}
			}

			return $this->_rendering($objects);
		}

		/**
		  * @return array Variables for rendering template
		  */
		protected function _getTemplateVars()
		{
			return array();
		}

		/**
		  * @param array $objects Current objects
		  * @return bool
		  */
		protected function _rendering(array $objects)
		{
			$sites = $this->_siteProcessing();
			$status = $this->_processing($sites, $objects);

			if($status)
			{
				$vars = $this->_getTemplateVars();

				try {
					$Core_Template = new C\Template($this->_script, $this->_export, $vars);
					$this->_script = $Core_Template->script;
					$this->_export = $Core_Template->export;
					return $Core_Template->rendering();
				}
				catch(E\Message $e) {
					$this->_SHELL->throw($e);
				}
				catch(\Exception $e) {
					$this->_SHELL->error($e->getMessage(), 'orange');
				}
			}

			return false;
		}

		/**
		  * Return all neighbour sites
		  *
		  * Current \PhpCliShell\Application\Firewall\Site is filtered
		  * Current \PhpCliShell\Application\Firewall\Core\Api\Site is registered
		  *
		  * @return \PhpCliShell\Application\Firewall\Core\Api\Site[] Sites
		  */
		protected function _siteProcessing()
		{
			$sites = $this->_sites;
			$this->_siteApi = null;

			// /!\ Doit travailler sur une copie
			foreach($sites as $index => $siteApi)
			{
				if($siteApi->name === $this->_site->getName())
				{
					if($this->_siteApi === null) {
						$this->_siteApi = $siteApi;
						unset($sites[$index]);
					}
					else {
						throw new Exception("The site '".$siteApi->name."' is declared more than once", E_USER_ERROR);
					}
				}
			}

			if($this->_siteApi === null) {
				throw new Exception("The site '".$this->_site->getName()."' is is not declared", E_USER_ERROR);
			}

			return $sites;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Site[] $sites
		  * @param array $objects
		  * @return bool
		  */
		protected function _processing(array $sites, array $objects)
		{
			return false;
		}

		/**
		  * @return string Script filename
		  */
		protected function _getScript()
		{
			$pathname = $this->_CONFIG->configuration->paths->templates;
			$script = rtrim($pathname, '/').'/'.static::VENDOR.'-'.static::PLATFORM;

			if(C\Tools::is('string&&!empty', static::TEMPLATE)) {
				$script .= '_'.static::TEMPLATE;
			}

			$script .= '.php';
			$firstChar = substr($script, 0, 1);

			if($firstChar !== '/' && $firstChar !== '~') {
				$script = APP_DIR.'/'.$script;
			}

			return $script;
		}

		/**
		  * @return string Export filename
		  */
		protected function _getExport()
		{
			$pathname = $this->_CONFIG->configuration->paths->exports;
			return rtrim($pathname, '/').'/'.$this->_site->hostname.'.'.static::TEMPLATE_EXT;
		}

		/**
		  * @param string $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'site': {
					return $this->_site;
				}
				case 'siteApi': {
					return $this->_siteApi;
				}
				case 'firewall': {
					return $this->_firewall;
				}
				case 'script':
				case 'template': {
					return $this->_script;
				}
				case 'export': {
					return $this->_export;
				}
				default: {
					throw new Exception("This attribute '".$name."' does not exist", E_USER_ERROR);
				}
			}
		}
	}