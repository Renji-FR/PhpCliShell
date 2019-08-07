<?php
	namespace PhpCliShell\Addon\Ipam\Common\Api;

	use ReflectionClass;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Common\Resolver;
	use PhpCliShell\Addon\Ipam\Common\Orchestrator;

	abstract class AbstractApi extends C\Addon\Api\AbstractApi
	{
		/**
		  * @var string
		  */
		const FIELD_ID = 'id';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var string
		  */
		const WILDCARD = '*';

		/**
		  * @var string
		  */
		const SEPARATOR_PATH = ',';


		/**
		  * @return \PhpCliShell\Addon\Ipam\Common\Resolver
		  */
		protected function _newResolver()
		{
			$ReflectionClass = new ReflectionClass($this);
			$thisNamespace = $ReflectionClass->getNamespaceName();

			$resolver = new Resolver($thisNamespace);	// namespace of this
			$resolver->addNamespace(__NAMESPACE__);		// namespace of self
			return $resolver;
		}

		public static function objectIdIsValid($objectId)
		{
			return C\Tools::is('int&&>0', $objectId);
		}

		public function objectExists()
		{
			if(!$this->hasObjectId()) {
				return false;
			}
			elseif($this->_objectExists === null) {
				$this->_objectExists = ($this->_getObject() !== false);
			}
			return $this->_objectExists;
		}

		public function hasObjectLabel()
		{
			return ($this->getObjectLabel() !== false);
		}

		public function getObjectLabel()
		{
			if($this->_objectLabel !== null) {		// /!\ Ne pas appeler hasObjectLabel sinon boucle infinie
				return $this->_objectLabel;
			}
			elseif($this->hasObjectId()) {			// /!\ Ne pas appeler objectExists sinon boucle infinie
				$objectDatas = $this->_getObject();
				$this->_objectLabel = ($objectDatas !== false) ? ($objectDatas[static::FIELD_NAME]) : (false);
				return $this->_objectLabel;
			}
			else {
				return false;
			}
		}

		protected function _setObjectLabel($objectLabel)
		{
			if(!$this->objectExists() && C\Tools::is('string&&!empty', $objectLabel)) {
				$this->_objectLabel = $objectLabel;
				return true;
			}
			else {
				return false;
			}
		}

		/**
		  * Méthode courte comme getPath
		  */
		public function getLabel()
		{
			return $this->getObjectLabel();
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'ipam':
				case '_IPAM': {
					return $this->_adapter;
				}
				default: {
					return parent::__get($name);
				}
			}
		}

		/**
		  * @return \PhpCliShell\Addon\Ipam\Common\Orchestrator
		  */
		protected static function _getOrchestrator()
		{
			return Orchestrator::getInstance();
		}
	}