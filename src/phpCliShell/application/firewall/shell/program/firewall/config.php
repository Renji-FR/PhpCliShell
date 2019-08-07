<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall as FirewallProgram;

	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Component\Resolver;

	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Helper;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter;

	class Config extends AbstractProgram
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
		  * @var string
		  */
		const CONTEXT_ADDRESSES = 'objects';

		/**
		  * @var string
		  */
		const CONTEXT_CONFIGURATIONS = 'configs';

		/**
		  * @var array
		  */
		const CONTEXTS = array(
			self::CONTEXT_ADDRESSES => array(
				Manager\Address::MANAGER_TYPE => Manager\Address::class
			),
			self::CONTEXT_CONFIGURATIONS => array(
				Manager\Site::MANAGER_TYPE => Manager\Site::class,
				Manager\AclRule::MANAGER_TYPE => Manager\AclRule::class,
				Manager\NatRule::MANAGER_TYPE => Manager\NatRule::class,
			),
		);

		/**
		  * @var array
		  */
		const MANAGERS = array(
			'address' => Manager\Address::class,
			'site' => Manager\Site::class,
			'aclRule' => Manager\AclRule::class,
			'natRule' => Manager\NatRule::class,
		);

		/**
		  * @var array
		  */
		const ADAPTERS = array(
			'csv_'.Api\AclRule::API_TYPE => Adapter\Csv\AclRule::class,
			'csv_'.Api\NatRule::API_TYPE => Adapter\Csv\NatRule::class,
			'json' => Adapter\Json\OneJson::class,
			'json_'.Api\AclRule::API_TYPE => Adapter\Json\AclRule::class,
			'json_'.Api\NatRule::API_TYPE => Adapter\Json\NatRule::class,
		);

		/**
		  * @var array
		  */
		const HELPERS = array(
			'import' => Helper\Import::class,
			'export' => Helper\Export::class
		);

		/**
		  * @var array
		  */
		protected static $_CONTEXTS = null;

		/**
		  * @var \PhpCliShell\Core\Config
		  */
		protected $_config = null;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\AbstractManager[]
		  */
		protected $_managers = array();

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\AbstractAdapter[]
		  */
		protected $_adapters = array();

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Helper\AbstractHelper[]
		  */
		protected $_helpers = array();

		/**
		  * @var bool
		  */
		protected $_hasChanges = false;

		/**
		  * @var bool
		  */
		protected $_isSaving = false;

		/**
		  * @var bool
		  */
		protected $_isAutoSaving = false;

		/**
		  * @var null|string
		  */
		protected $_pathname = null;

		/**
		  * @var null|string
		  */
		protected $_basename = null;

		/**
		  * Null allow to load a config file
		  * False forbid to load a config file, after a recovery
		  * String is the filename of the config after loaded it
		  *
		  * @var null|false|string Null allow to load a config, false forbid it, string is the file loaded
		  */
		protected $_filename = null;

		/**
		  * All prefix and suffix files loaded
		  * @var array
		  */
		protected $_filenames = array();

		/**
		  * Auto save filename
		  * @var string
		  */
		protected $_asFilename = null;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall $firewallProgram
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, FirewallProgram $firewallProgram, ArrayObject $objects)
		{
			parent::__construct($SHELL, $firewallProgram, $objects);

			$this->_config = $SHELL->applicationConfig->configuration;

			if(self::$_CONTEXTS === null)
			{
				self::$_CONTEXTS = array();

				foreach(self::CONTEXTS as $context => $managerClasses)
				{
					foreach($managerClasses as $managerType => $managerClass) {
						self::$_CONTEXTS[$managerClass] = $context;
						self::$_CONTEXTS[$managerType] = $context;
					}
				}
			}
		}

		protected function _canLoad($prefix = null, $suffix = null)
		{
			$filename = $this->_getFilename($prefix, $suffix);
			return ($filename === null);
		}

		protected function _isLoaded($prefix = null, $suffix = null)
		{
			return !$this->_canLoad($prefix, $suffix);
		}

		protected function _isRecovery()
		{
			return ($this->_filename === false);
		}

		public function hasChanges()
		{
			$this->_isSaving = false;
			$this->_isAutoSaving = false;
			$this->_hasChanges = true;

			$this->autosave();
			return $this;
		}

		protected function _isSaving()
		{
			$this->_isSaving = true;
			$this->_isAutoSaving = false;
			$this->_hasChanges = false;

			$this->_resetAutosave();
			return $this;
		}

		protected function _isAutoSaving()
		{
			$this->_isAutoSaving = true;
			return $this;
		}

		protected function _resetAutosave()
		{
			$filename = $this->_getAsFilename();

			if($filename !== false && file_exists($filename) && is_writable($filename)) {
				return unlink($filename);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $pathname
		  * @return void
		  */
		protected function _setPathname($pathname)
		{
			$this->_pathname = $pathname;
		}

		/**
		  * @param string $basename
		  * @return void
		  */
		protected function _setBasename($basename)
		{
			$this->_basename = $basename;
		}

		/**
		  * @param string $filename
		  * @param string $prefix
		  * @param string $suffix
		  * @return void
		  */
		protected function _setFilename($filename, $prefix = null, $suffix = null)
		{
			if($prefix !== null || $suffix !== null) {
				if(!C\Tools::is('string&&!empty', $prefix)) $prefix = 0;
				if(!C\Tools::is('string&&!empty', $suffix)) $suffix = 0;
				$this->_filenames[$prefix][$suffix] = $filename;
			}
			else {
				$this->_filename = $filename;
			}
		}

		/**
		  * @return null|string
		  */
		protected function _getPathname()
		{
			return $this->_pathname;
		}

		/**
		  * @return null|string
		  */
		protected function _getBasename()
		{
			return $this->_basename;
		}

		/**
		  * @param string $prefix
		  * @param string $suffix
		  * @return null|string
		  */
		protected function _getFilename($prefix = null, $suffix = null)
		{
			if($prefix !== null || $suffix !== null)
			{
				$filenames = $this->_filenames;

				if(!C\Tools::is('string&&!empty', $prefix)) {
					$prefix = 0;
				}

				if(array_key_exists($prefix, $filenames)) {
					$filenames = $filenames[$prefix];
				}
				else {
					return null;
				}

				if(!C\Tools::is('string&&!empty', $suffix)) {
					$suffix = 0;
				}

				if(array_key_exists($suffix, $filenames)) {
					$filenames = $filenames[$suffix];
				}
				else {
					return null;
				}

				return $filenames;
			}
			else {
				return $this->_filename;
			}
		}

		protected function _getAsFilename()
		{
			if($this->_asFilename === null)
			{
				$status = $this->_config->autosave->status;
				$filename = $this->_config->paths->autosave;

				if($status && C\Tools::is('string&&!empty', $filename)) {
					$this->_asFilename = C\Tools::filename($filename);
				}
				else {
					$this->_asFilename = false;
				}
			}

			return $this->_asFilename;
		}

		protected function _resetLoad()
		{
			$this->_resetStatus();
			$this->_clearConfigurations();
			$this->_filename = null;
			$this->_filenames = array();
		}

		protected function _resetStatus()
		{
			$this->_hasChanges = false;
			$this->_isSaving = false;
			$this->_isAutoSaving = false;
			return $this;
		}

		protected function _clearAddresses()
		{		
			foreach(self::CONTEXTS[self::CONTEXT_ADDRESSES] as $manager)
			{
				foreach($manager::API_CLASSES as $objectApiClass) {
					$type = $objectApiClass::API_TYPE;
					$objects =& $this->_objects[$type];		// /!\ Important
					$objects = array();
				}
			}
		}

		protected function _clearConfigurations()
		{
			foreach(self::CONTEXTS[self::CONTEXT_CONFIGURATIONS] as $manager)
			{
				foreach($manager::API_CLASSES as $objectApiClass) {
					$type = $objectApiClass::API_TYPE;
					$objects =& $this->_objects[$type];		// /!\ Important
					$objects = array();
				}
			}
		}

		protected function _checkAllObjects()
		{
			$errors = array();

			foreach($this->_objects as $type => $objects)
			{
				foreach($objects as $object)
				{
					if(!$object->isValid()) {
						$errors[] = $object;
					}
				}
			}

			return $errors;
		}

		protected function _getObjectsToSave($checkValidity = true)
		{
			$items = array();
			$errors = array();

			foreach($this->_objects as $type => $objects)
			{
				$context = null;

				/**
				  * Ceux sont les programmes (manager) qui définissent de quel objets ils sont responsables
				  * On doit donc tester le type pour chaque programme (manager) afin de déterminer celui correspondant
				  *
				  * Pourquoi les contextes dépendent du programme (manager) et non pas de l'objet API?
				  * Un programme (manager) peut être responsable de plusieurs objets
				  */
				foreach(self::MANAGERS as $managerName)
				{
					$manager = $this->getManager($managerName);

					if($manager->isType($type))
					{
						if(array_key_exists($manager::MANAGER_TYPE, self::$_CONTEXTS)) {
							$context = self::$_CONTEXTS[$manager::MANAGER_TYPE];
							break;
						}
						else {
							throw new Exception("Context for object type '".$type."' is not available", E_USER_ERROR);
						}
					}
				}

				if($context !== null)
				{
					$items[$context][$type] = array();

					foreach($objects as $object)
					{
						if(!$checkValidity || $object->isValid()) {
							$items[$context][$type][] = $object->sleep();
						}
						else {
							$errors[] = $object;
						}
					}
				}
				else {
					throw new Exception("Can not retrieve manager for object type '".$type."'", E_USER_ERROR);
				}
			}

			return array($items, $errors);
		}

		// LOAD
		// --------------------------------------------------
		protected function _recovery()
		{
			$status = false;

			$pathname = $this->_getAsFilename();

			if($pathname !== false)
			{
				if(file_exists($pathname) && is_readable($pathname))
				{
					$this->_SHELL->EOL();

					$Cli_Terminal_Question = new Cli\Terminal\Question();

					$question = "Souhaitez-vous charger le fichier d'autosauvegarde? [Y|n]";
					$question = C\Tools::e($question, 'orange', false, false, true);
					$answer = $Cli_Terminal_Question->question($question);
					$answer = mb_strtolower($answer);

					if($answer === '' || $answer === 'y' || $answer === 'yes')
					{
						$jsonAdapter = $this->getAdapter('json');

						try {
							$status = $jsonAdapter->apply($pathname, false);
						}
						catch(\Exception $e) {
							$this->_SHELL->throw($e);
							$status = false;
						}

						if(!$status) {
							$this->_clearAddresses();
							$this->_clearConfigurations();

							$this->_SHELL->error("Impossible de récupérer la sauvegarde automatique!", 'red');
						}
						else
						{
							/**
							  * /!\ Important, permet de bloquer un futur chargement
							  */
							$this->_setFilename(false);

							$this->getProgram('store')->refresh();
							$basename = basename($pathname, $jsonAdapter::EXTENSION);
							$this->_SHELL->print("Récupération de la sauvegarde automatique '".$basename."' terminée!", 'green');
						}

						/**
						  * Le fichier autosave existant alors même si il est vide on a bien essayé de la charger
						  * donc il faut l'indiquer en retournant true et même si une erreur s'est produite
						  */
						$status = true;
					}
				}
			}
			else {
				$this->_SHELL->error("/!\ La sauvegarde automatique est désactivée !", 'red');
			}

			return $status;
		}

		public function autoload()
		{
			$status = $this->_recovery();

			if(!$status)
			{
				$status = false;
				$filename = $this->_config->paths->objects;
				$filename = C\Tools::filename($filename);

				if(file_exists($filename))
				{
					$jsonAdapter = $this->getAdapter('json');

					try {
						$status = $jsonAdapter->loadAddresses($filename);
					}
					catch(\Exception $e) {
						$this->_SHELL->throw($e);
						$status = false;
					}

					if($status) {
						$this->_SHELL->print("Chargement des objets terminée!", 'green');
					}
					else {
						$this->_clearAddresses();
						$this->_SHELL->error("Une erreur s'est produite pendant le chargement des objets", 'orange');
					}
				}
				else {
					$status = true;
				}
			}

			$this->_SHELL->EOL();
			return $status;
		}

		public function load(array $args)
		{
			if(isset($args[0]))
			{
				$prefix = (isset($args[1]) && C\Tools::is('string&&!empty', $args[1])) ? ($args[1]) : (false);

				if($this->_canLoad() || $prefix)
				{
					if($this->_canLoad($prefix))
					{
						if($this->isSaving || !$this->hasChanges || (isset($args[2]) && $args[2] === 'force'))
						{
							if(preg_match('#(\.[^.\s]+)$#i', $args[0], $matches)) {
								$filenameLength = (mb_strlen($args[0])-mb_strlen($matches[1]));
								$basename = mb_substr($args[0], 0, $filenameLength);				// pathname or filename
								$extension = mb_strtolower($matches[1]);							// format (extension)
							}
							else {
								$basename = $args[0];												// pathname or filename
								$extension = Adapter\Json::EXTENSION;								// format (extension)
							}

							if(($adapter = $this->retrieveAdapter($extension)) !== false)
							{
								if($adapter::ALLOW_LOAD)
								{
									$status = true;

									$pathname = $this->_config->paths->configs;
									$pathname = C\Tools::filename($pathname);

									$filename = $adapter->formatFilename($pathname, $basename);

									try {
										$counters = $adapter->load($filename, $prefix);
									}
									catch(\Exception $e) {
										$this->_clearConfigurations();		// Une Exception signifie qu'une erreur critique s'est produite durant le chargement, effectuer un resetLoad
										$this->_SHELL->throw($e);
										$counters = false;
									}

									if($counters !== false)					// False signifie qu'une erreur non critique s'est produite durant le chargement, ne pas effectuer un resetLoad
									{
										foreach($counters as $classApi => $counter)
										{
											switch($counter)
											{
												case 0:
												case 1: {
													$this->_SHELL->print($counter." ".$classApi::API_LABEL." a été chargé(e)", 'green');
													break;
												}
												default: {
													$this->_SHELL->print($counter." ".$classApi::API_LABEL." ont été chargé(e)s", 'green');
												}
											}
										}

										$this->_SHELL->EOL();
									}
									else {
										$this->_SHELL->EOL()->error("Une erreur s'est produite pendant le chargement de la configuration '".$filename."'", 'orange');
										return true;
									}

									$this->_setPathname($pathname);
									$this->_setBasename($basename);
									$this->_setFilename($filename, $prefix);

									if($adapter::FORMAT !== Adapter\Json::FORMAT)
									{
										$jsonAdapter = $this->getAdapter('json');
										$filename = $adapter->formatFilename($pathname, $basename);

										try {
											$status = $jsonAdapter->loadSites($filename);
										}
										catch(\Exception $e) {
											$this->_clearConfigurations();		// Une Exception signifie qu'une erreur critique s'est produite durant le chargement, effectuer un resetLoad
											$this->_SHELL->throw($e);
											$status = false;
										}

										if(!$status) {							// False signifie qu'une erreur non critique s'est produite durant le chargement, ne pas effectuer un resetLoad
											$this->_SHELL->error("Impossible de charger les sites de la configuration '".$filename."'", 'orange');
										}
									}

									if($status) {
										$this->_setFilename($filename);			// Toujours extension JSON
										$this->getProgram('store')->refresh();
										$this->_SHELL->print("Chargement de la configuration '".$basename."' terminée!", 'green');
									}
									else {
										$this->_SHELL->error("Une erreur s'est produite pendant le chargement de la configuration '".$filename."'", 'orange');
									}
								}
								else {
									$this->_SHELL->error("Le format '".$format."' ne permet pas le chargement d'une configuration", 'orange');
								}
							}
							else {
								$this->_SHELL->error("Le format '".$format."' du fichier de configuration n'est pas supporté", 'orange');
								return false;
							}
						}
						else {
							$this->_SHELL->error("Il est vivement recommandé de sauvegarder la configuration avant d'en charger une autre. Pour charger malgrés tout utilisez l'argument 'force'", 'orange');
						}
					}
					else {
						$this->_SHELL->error("Un fichier de sauvegarde '".$this->_getFilename($prefix)."' a déjà été chargé pour ce prefix '".$prefix."'. Merci d'indiquer un autre préfix ou d'importer la configuration", 'orange');
					}
				}
				elseif($this->_isRecovery()) {
					$this->_SHELL->error("Il n'est pas possible de charger une configuration après une récupération automatique sauf si vous indiquez un préfix", 'orange');
				}
				else {
					$this->_SHELL->error("Un fichier de sauvegarde '".$this->_getFilename()."' a déjà été chargé. Merci d'indiquer un préfix lors du chargement ou d'importer la configuration", 'orange');
				}

				return true;
			}

			return false;
		}
		// --------------------------------------------------

		// SAVE
		// --------------------------------------------------
		public function autosave()
		{
			$filename = $this->_getAsFilename();

			if($filename !== false && !$this->isSaving && !$this->isAutoSaving && $this->hasChanges)
			{
				$status = false;

				try {
					list($items,) = $this->_getObjectsToSave(false);
				}
				catch(E\Message $e) {
					$this->_SHELL->error("[AUTOSAVE] ".$e->getMessage(), 'orange');
					return true;
				}

				$jsonAdapter = $this->getAdapter('json');
				$status = $jsonAdapter->save($filename, $items);

				if($status) {
					$this->_isAutoSaving();
				}
				else {
					$this->_SHELL->error("[AUTOSAVE] Une erreur s'est produite pendant la sauvegarde du fichier de configuration '".$filename."'", 'orange');
				}

				return $status;
			}
			else {
				return true;
			}
		}

		public function save(array $args)
		{
			$status = false;
			$filename = $this->_config->paths->objects;
			$filename = C\Tools::filename($filename);

			$pathname = pathinfo($filename, PATHINFO_DIRNAME);

			if((!file_exists($filename) && is_writable($pathname)) || (file_exists($filename) && is_writable($filename)))
			{
				try {
					list($items, $errObjects) = $this->_getObjectsToSave();
				}
				catch(\Exception $e) {
					$this->_SHELL->throw($e);
					return true;
				}

				if(count($errObjects) > 0)
				{
					foreach($errObjects as $errObject) {
						$this->_SHELL->error("L'objet '".$errObject::API_LABEL."' '".$errObject->name."' n'est pas valide", 'orange');
					}

					return true;
				}
				elseif(array_key_exists(self::CONTEXT_ADDRESSES, $items))
				{
					$jsonAdapter = $this->getAdapter('json');
					$status = $jsonAdapter->save($filename, $items[self::CONTEXT_ADDRESSES]);

					if($status !== false)
					{
						$this->_SHELL->print("Sauvegarde des objets terminée! (".$filename.")", 'green');
						unset($filename);	// /!\ Important

						if(array_key_exists(self::CONTEXT_CONFIGURATIONS, $items))
						{
							if(isset($args[0]))
							{
								$basename = $args[0];

								$pathname = $this->_config->paths->configs;
								$pathname = C\Tools::filename($pathname);

								if($basename === $this->_getBasename() && $pathname === $this->_getPathname()) {
									$args[1] = 'force';
								}
							}
							elseif($this->_isLoaded()) {
								$basename = $this->_getBasename();
								$pathname = $this->_getPathname();
								$args[1] = 'force';
							}

							if(isset($pathname))
							{
								$status = true;

								foreach(self::ADAPTERS as $adapterKey => $adapter)
								{
									$adapter = $this->getAdapter($adapterKey);

									if($adapter::ALLOW_SAVE)
									{
										$filename = $adapter->formatFilename($pathname, $basename);

										if(!file_exists($filename) || (isset($args[1]) && $args[1] === 'force'))
										{
											try {
												$counters = $adapter->save($filename, $items[self::CONTEXT_CONFIGURATIONS]);
											}
											catch(E\Message $e) {
												$this->_SHELL->throw($e);
												$counters = false;
											}

											if($counters !== false) {
												$this->_SHELL->print("Sauvegarde de la configuration '".$basename."' terminée! (".$filename.")", 'green');
											}
											else {
												$this->_SHELL->error("Une erreur s'est produite pendant la sauvegarde du fichier de configuration '".$filename."'", 'orange');
												$status = false;
												break;
											}
										}
										else {
											$this->_SHELL->error("Le fichier de sauvegarde '".$filename."' existe déjà. Pour l'écraser utilisez l'argument 'force'", 'orange');
										}
									}
								}

								if($status) {
									$this->_isSaving();
								}
							}
							else {
								$this->_SHELL->error("Merci d'indiquer le nom du fichier de sauvegarde sans chemin ni extension. Example: myBackup", 'orange');
							}
						}
					}
					else {
						$this->_SHELL->error("Une erreur s'est produite pendant l'écriture du fichier de sauvegarde '".$filename."'", 'orange');
					}
				}
			}
			else {
				$this->_SHELL->error("Impossible de sauvegarder les objets dans '".$filename."'", 'orange');
				$this->_SHELL->error("Vérifiez les droits d'écriture du fichier et le chemin '".$pathname."'", 'orange');
			}

			return $status;
		}
		// --------------------------------------------------

		// IMPORT / EXPORT
		// --------------------------------------------------
		public function import(array $firewalls, $type, array $args)
		{
			$status = true;

			if(isset($args[0]))
			{
				$format = $args[0];

				if(($adapter = $this->getAdapter($format, $type)) !== false)
				{
					if($adapter::ALLOW_IMPORT)
					{
						if(isset($args[1]))
						{
							$filename = $args[1];
							$pathParts = pathinfo($filename);

							if($pathParts['dirname'] !== '.')
							{
								$pathname = realpath($pathParts['dirname']);

								if($pathname !== false) {
									$filename = $pathname.'/'.$pathParts['basename'];
								}
							}

							if(file_exists($filename) && is_readable($filename) && is_file($filename))
							{
								$prefix = (isset($args[2]) && C\Tools::is('string&&!empty', $args[2])) ? ($args[2]) : (null);

								if($this->isSaving || !$this->hasChanges || (isset($args[3]) && $args[3] === 'force'))
								{
									try {
										$counters = $adapter->import($filename, $prefix);
									}
									catch(\Exception $e) {
										$this->_resetLoad();			// Une Exception signifie qu'une erreur critique s'est produite durant l'importation, effectuer un resetLoad
										$this->_SHELL->throw($e);
										$counters = false;
									}

									if($counters !== false)				// False signifie qu'une erreur non critique s'est produite durant l'importation, ne pas effectuer un resetLoad
									{
										foreach($counters as $classApi => $counter)
										{
											switch($counter)
											{
												case 0:
												case 1: {
													$this->_SHELL->print($counter." ".$classApi::API_LABEL." a été importé(e)", 'green');
													break;
												}
												default: {
													$this->_SHELL->print($counter." ".$classApi::API_LABEL." ont été importé(e)s", 'green');
												}
											}
										}

										$this->_SHELL->EOL()->print("Importation de la configuration '".$filename."' terminée!", 'green');
									}
									else {
										$this->_SHELL->EOL()->error("Une erreur s'est produite pendant l'importation de la configuration '".$filename."'", 'orange');
									}
								}
								else {
									$this->_SHELL->error("Il est vivement recommandé de sauvegarder la configuration avant d'en importer une autre. Pour importer malgrés tout utilisez l'argument 'force'", 'orange');
								}
							}
							else {
								$this->_SHELL->error("Le fichier de configuration à importer '".$filename."' n'existe pas ou ne peut être lu", 'orange');
							}

							return true;
						}
						else {
							$this->_SHELL->error("Merci de préciser le fichier de configuration à importer", 'orange');
						}
					}
					else {
						$this->_SHELL->error("Le format '".$format."' et le type '".$type."' ne permettent pas d'importer une configuration", 'orange');
					}
				}
				else {
					$this->_SHELL->error("Impossible de trouver l'adapteur, le format '".$format."' et/ou le type '".$type."' ne sont pas supportés", 'orange');
				}
			}

			return false;
		}

		public function importFirewall(array $args)
		{
			$prefix = (isset($args[0]) && C\Tools::is('string&&!empty', $args[0])) ? ($args[0]) : (null);

			if($this->isSaving || !$this->hasChanges || (isset($args[1]) && $args[1] === 'force'))
			{
				$this->_SHELL->deleteWaitingMsg();
				$this->_SHELL->eol(2);

				$importHelper = $this->getHelper('import');

				try {
					$importHelper->import($prefix);
				}
				catch(\Exception $e) {
					$this->_clearConfigurations();
					$this->_SHELL->throw($e);
				}
			}
			else {
				$this->_SHELL->error("Il est vivement recommandé de sauvegarder la configuration avant d'en importer une autre. Pour importer malgrés tout utilisez l'argument 'force'", 'orange');
			}

			return true;
		}

		public function export(array $firewalls, $type, array $args)
		{
			if(isset($args[0]))
			{
				$format = $args[0];
				$force = (isset($args[1]) && $args[1] === 'force');

				try {
					$errObjects = $this->_checkAllObjects();
				}
				catch(\Exception $e) {
					$this->_SHELL->throw($e);
					return true;
				}

				if(count($errObjects) > 0)
				{
					foreach($errObjects as $errObject) {
						$this->_SHELL->error("L'objet '".$errObject::API_LABEL."' '".$errObject->name."' n'est pas valide", 'orange');
					}
				}
				else {
					$exportHelper = $this->getHelper('export');
					$exportHelper->export($firewalls, $type, $format, $force);
				}

				return true;
			}

			return false;
		}

		public function copy(array $firewalls, $type, array $args)
		{
			if(isset($args[0]) && isset($args[1]) && isset($args[2]))
			{
				$format = $args[0];
				$method = $args[1];
				$site = $args[2];

				try {
					$errObjects = $this->_checkAllObjects();
				}
				catch(\Exception $e) {
					$this->_SHELL->throw($e);
					return true;
				}

				if(count($errObjects) > 0)
				{
					foreach($errObjects as $errObject) {
						$this->_SHELL->error("L'objet '".$errObject::API_LABEL."' '".$errObject->name."' n'est pas valide", 'orange');
					}
				}
				else {
					$exportHelper = $this->getHelper('export');
					$exportHelper->copy($firewalls, $type, $format, $method, $site);
				}

				return true;
			}

			return false;
		}
		// --------------------------------------------------

		// TOOLS
		// --------------------------------------------------
		/**
		  * @param string $name Manager class or type
		  * @return false|string
		  */
		public function getContext($name)
		{
			return (array_key_exists($name, self::$_CONTEXTS)) ? (self::$_CONTEXTS[$name]) : (false);
		}

		/**
		  * @param string $name Program class or name
		  * @return false|mixed
		  */
		public function getProgram($name)
		{
			return $this->_SHELL->program->getProgram($name);
		}

		/**
		  * @param string $name Manager class or name
		  * @return false|\PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager\AbstractManager
		  */
		public function getManager($name)
		{
			if(array_key_exists($name, $this->_managers)) {
				return $this->_managers[$name];
			}
			else
			{
				if(array_key_exists($name, self::MANAGERS)) {
					$managerClass = self::MANAGERS[$name];
					$managerName = $name;
				}
				elseif(($managerName = array_search($name, self::MANAGERS, true)) !== false) {
					$managerClass = $name;
				}
				else {
					return false;
				}

				$AbstractManager = Resolver::getManager($managerClass);	// OU $AbstractManager = $this->_firewallProgram->getProgram($managerClass);
				$this->_managers[$managerClass] = $AbstractManager;
				$this->_managers[$managerName] = $AbstractManager;
				return $AbstractManager;
			}
		}

		/**
		  * @param string $name Adapter class or name
		  * @param null|string $type Object API type
		  * @return false|\PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\AbstractAdapter
		  */
		public function getAdapter($name, $type = null)
		{
			$parts = array($name);

			if($type !== null) {
				$parts[] = $type;
			}

			$name = implode('_', $parts);

			if(array_key_exists($name, $this->_adapters)) {
				return $this->_adapters[$name];
			}
			else
			{
				if(array_key_exists($name, self::ADAPTERS)) {
					$adapterClass = self::ADAPTERS[$name];
					$adapterName = $name;
				}
				elseif(($adapterName = array_search($name, self::ADAPTERS, true)) !== false) {
					$adapterClass = $name;
				}
				else {
					return false;
				}

				$AbstractAdapter = new $adapterClass($this->_SHELL, $this, $this->_objects, $type);
				$this->_adapters[$adapterClass] = $AbstractAdapter;
				$this->_adapters[$adapterName] = $AbstractAdapter;
				return $AbstractAdapter;
			}
		}

		/**
		  * @param string $name Helper class or name
		  * @return false|\PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Helper\AbstractHelper
		  */
		public function getHelper($name)
		{
			if(array_key_exists($name, $this->_helpers)) {
				return $this->_helpers[$name];
			}
			else
			{
				if(array_key_exists($name, self::HELPERS)) {
					$helperClass = self::HELPERS[$name];
					$helperName = $name;
				}
				elseif(($helperName = array_search($name, self::HELPERS, true)) !== false) {
					$helperClass = $name;
				}
				else {
					return false;
				}

				$AbstractHelper = new $helperClass($this->_SHELL, $this, $this->_objects);
				$this->_helpers[$helperClass] = $AbstractHelper;
				$this->_helpers[$helperName] = $AbstractHelper;
				return $AbstractHelper;
			}
		}

		/**
		  * @param string $extension
		  * @return false|\PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\AbstractAdapter
		  */
		public function retrieveAdapter($extension)
		{
			$extension = mb_strtolower($extension);

			if(substr($extension, 0, 1) !== '.') {
				$extension = '.'.$extension;
			}

			foreach(self::ADAPTERS as $adapterKey => $adapter)
			{
				if($extension === mb_strtolower($adapter::EXTENSION)) {
					return $this->getAdapter($adapterKey);
				}
			}

			return false;
		}
		// --------------------------------------------------

		public function __get($name)
		{
			switch($name)
			{
				case 'isSaving': {
					return $this->_isSaving;
				}
				case 'isAutoSaving': {
					return $this->_isAutoSaving;
				}
				case 'hasChanges': {
					return $this->_hasChanges;
				}
				default: {
					throw new Exception("This attribute '".$name."' does not exist", E_USER_ERROR);
				}
			}
		}
	}