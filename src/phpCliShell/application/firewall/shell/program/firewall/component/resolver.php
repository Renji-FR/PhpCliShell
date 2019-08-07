<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Component;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;

	class Resolver
	{
		/**
		  * @var array
		  */
		const API_TYPES = Manager\AbstractManager::API_TYPES;

		/**
		  * @var array
		  */
		const API_INDEXES = Manager\AbstractManager::API_INDEXES;

		/**
		  * @var array
		  */
		const API_CLASSES = Manager\AbstractManager::API_CLASSES;

		/**
		  * @var array
		  */
		protected static $_managers = array(
			'managerModel' => array(),
			'managerClass' => array(),
			'apiModel' => array(),
			'apiClass' => array(),
		);

		/**
		  * @var string
		  */
		protected $_type = null;


		/**
		  * @param string $type
		  * @return $this
		  */
		public function __construct($type)
		{
			$this->setType($type);
		}

		/**
		  * @param string $type
		  * @return $this
		  */
		public function setType($type)
		{
			if(in_array($type, static::API_TYPES, true)) {
				$this->_type = $type;
				return $this;
			}
			else {
				throw new Exception("Object type '".$type."' does not exist", E_USER_ERROR);
			}
		}

		/**
		  * @return string Return key
		  */
		public function resolveKey()
		{
			return static::API_INDEXES[$this->_type];
		}

		/**
		  * @return string Return class
		  */
		public function resolveClass()
		{
			return static::API_CLASSES[$this->_type];
		}

		/**
		  * @return string Return label
		  */
		public function resolveLabel()
		{
			$class = resolveClass();
			return $class::API_LABEL;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\AbstractManager $manager
		  * @return $this
		  */
		public function registerManager(Manager\AbstractManager $manager)
		{
			static::setManager($manager);
			return $this;
		}

		/**
		  * @return \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\AbstractManager
		  */
		public function retrieveManager()
		{
			return static::getManager($this->_type);
		}

		/**
		  * @return \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer\AbstractRenderer
		  */
		public function retrieveRenderer()
		{
			return $this->retrieveManager()->getRenderer();
		}

		/**
		  * @param string $type
		  * @return false|string Return key or false if type does not exist
		  */
		public static function getKey($type)
		{
			return (array_key_exists($type, static::API_INDEXES)) ? (static::API_INDEXES[$type]) : (false);
		}

		/**
		  * @param string $type
		  * @return false|string Return class or false if type does not exist
		  */
		public static function getClass($type)
		{
			return (array_key_exists($type, static::API_CLASSES)) ? (static::API_CLASSES[$type]) : (false);
		}

		/**
		  * @param string $type
		  * @return false|string Return label or false if type does not exist
		  */
		public static function getLabel($type)
		{
			$class = self::getClass($type);
			return ($class !== false) ? ($class::API_LABEL) : (false);
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\AbstractManager $manager
		  * @return void
		  */
		public static function setManager(Manager\AbstractManager $manager)
		{
			static::$_managers['managerModel'][$manager::MANAGER_TYPE] = $manager;
			static::$_managers['managerClass'][get_class($manager)] = $manager;

			foreach($manager::API_TYPES as $apiModel) {
				static::$_managers['apiModel'][$apiModel] = $manager;
			}

			foreach($manager::API_CLASSES as $apiClass) {
				static::$_managers['apiClass'][$apiClass] = $manager;
			}
		}

		/**
		  * @param string $name Manager class id, manager class name, api class id or api clas name
		  * @return \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\AbstractManager
		  */
		public static function getManager($name)
		{
			if(array_key_exists($name, static::$_managers['managerClass'])) {
				return static::$_managers['managerClass'][$name];
			}
			elseif(array_key_exists($name, static::$_managers['managerModel'])) {
				return static::$_managers['managerModel'][$name];
			}
			elseif(array_key_exists($name, static::$_managers['apiClass'])) {
				return static::$_managers['apiClass'][$name];
			}
			elseif(array_key_exists($name, static::$_managers['apiModel'])) {
				return static::$_managers['apiModel'][$name];
			}
			else {
				throw new Exception("Unable to retrieve Manager '".$name."'", E_USER_ERROR);
			}
		}

		/**
		  * @param string $type
		  * @return \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer\AbstractRenderer
		  */
		public static function getRenderer($type)
		{
			return static::getManager($type)->getRenderer();
		}

		/**
		  * @param string $name Requests key, class, label, manager or renderer
		  * @return mixed
		  */
		public function __get($name)
		{
			switch(mb_strtolower($name))
			{
				case 'key': {
					return $this->resolveKey();
				}
				case 'class': {
					return $this->resolveClass();
				}
				case 'label': {
					return $this->resolveLabel();
				}
				case 'manager': {
					return $this->retrieveManager();
				}
				case 'renderer': {
					return $this->retrieveRenderer();
				}
				default: {
					throw new Exception("Resolver attribute '".$name."' does not exist", E_USER_ERROR);
				}
			}
		}
	}