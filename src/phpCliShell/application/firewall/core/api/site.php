<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	use PhpCliShell\Core as C;

	use PhpCliShell\Application\Firewall\Core;

	class Site extends AbstractApi
	{
		/**
		  * @var string
		  */
		const API_TYPE = 'site';

		/**
		  * @var string
		  */
		const API_INDEX = 'site';

		/**
		  * @var string
		  */
		const API_LABEL = 'site';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var \PhpCliShell\Application\Firewall\Core\Site
		  */
		protected $_coreSite;

		/**
		  * @var array
		  */
		protected $_datas = array(
			'_id_' => null,
			'name' => null,
			'equipment' => null,
			'zones' => array(),
			'topology' => array(),
			'metadata' => array(),
			'options' => array(),
		);


		/**
		  * @param string $id ID
		  * @param string $name Name
		  * @return $this
		  */
		public function __construct($id = null, $name = null)
		{
			$this->id($id);
			$this->name($name);
		}

		/**
		  * Sets name
		  * Make auto load configuration
		  *
		  * @param string $name
		  * @return bool
		  */
		public function name($name)
		{
			if(C\Tools::is('string&&!empty', $name)) {
				$this->_datas['name'] = $name;
				return $this->_loadConfig($name);
			}

			return false;
		}

		protected function _loadConfig($name)
		{
			$coreSites = new Core\Sites();

			if(isset($coreSites->{$name}))
			{
				$this->_coreSite = $coreSites->{$name};
				$site = $this->_coreSite->toArray();

				$this->_datas['equipment'] = $site['hostname'];
				$this->_datas['zones'] = $site['zones'];
				$this->_datas['topology'] = $site['topology'];

				if(array_key_exists('metadata', $site)) {
					$this->_datas['metadata'] = $site['metadata'];
				}

				if(array_key_exists('options', $site)) {
					$this->_datas['options'] = $site['options'];
				}

				return true;
			}
			else {
				return false;
			}
		}

		public function getZoneAlias($zone)
		{
			if(array_key_exists($zone, $this->_datas['metadata']) && array_key_exists('zoneAlias', $this->_datas['metadata'][$zone])) {
				return $this->_datas['metadata'][$zone]['zoneAlias'];
			}
			else
			{
				/**
				  * Ne pas retourner false mais la zone interrogée
				  */
				return $zone;
			}
		}

		public function hasGlobalZone()
		{
			return ($this->getGlobalZone() !== false);
		}

		public function getGlobalZone()
		{
			if(array_key_exists('globalZone', $this->_datas['options'])) {
				return $this->_datas['options']['globalZone'];
			}
			else {
				return false;
			}
		}

		public function isValid($returnInvalidAttributes = false)
		{		
			$tests = array(
				array(self::FIELD_NAME => 'string&&!empty'),
				array('equipment' => 'string&&!empty'),
				array('zones' => 'array&&count>0'),
				array('topology' => 'array&&count>0'),
				array('metadata' => 'array&&count>=0'),
				array('options' => 'array&&count>=0'),
			);

			return $this->_isValid($tests, $returnInvalidAttributes);
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'site': {
					return $this->_coreSite;
				}
				case 'globalZone': {
					return $this->getGlobalZone();
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}