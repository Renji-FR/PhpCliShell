<?php
	namespace PhpCliShell\Application\Firewall\Core;

	use PhpCliShell\Core as C;

	class Sites extends C\ArrayCollection
	{
		/**
		  * @var string
		  */
		protected static $_itemClassName = Site::class;

		/**
		  * @var \PhpCliShell\Core\Config
		  */
		protected $_config = null;


		/**
		  * @return $this
		  */
		public function __construct()
		{
			$this->_config = Config::getInstance()->sites;

			parent::__construct($this->_config);
		}

		/**
		  * @return \PhpCliShell\Core\Config
		  */
		public function getConfig()
		{
			return $this->_config;
		}

		/**
		  * @return array Site keys
		  */
		public function getSiteKeys()
		{
			return $this->keys();
		}

		/**
		  * @return array Firewalls [site => hostname]
		  */
		public function getFirewallHostnames()
		{
			return $this->_arrayObject->column('hostname', null, true);
		}
	}