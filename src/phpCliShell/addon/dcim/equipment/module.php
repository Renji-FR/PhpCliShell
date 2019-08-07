<?php
	namespace PhpCliShell\Addon\Dcim\Equipment;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Dcim\Api;
	use PhpCliShell\Addon\Dcim\Exception;

	abstract class Module
	{
		/**
		  * @var int
		  */
		protected $_moduleId = null;

		/**
		  * @var \PhpCliShell\Addon\Dcim\Api\Equipment
		  */
		protected $_moduleApi = null;

		/**
		  * @var \PhpCliShell\Addon\Dcim\Equipment\Equipment
		  */
		protected $_equipment = null;

		/**
		  * @var \PhpCliShell\Addon\Dcim\Equipment\Slot[]
		  */
		protected $_slots = array();

		/**
		  * @var \PhpCliShell\Addon\Dcim\Equipment\Port[]
		  */
		protected $_ports = array();

		/**
		  * @var \PhpCliShell\Addon\Dcim\Equipment\Interface_[]
		  */
		protected $_interfaces = array();


		/**
		  * @param \PhpCliShell\Addon\Dcim\Equipment\Equipment $equipment
		  * @param int $moduleId
		  * @return $this
		  */
		public function __construct(Equipment $equipment, $moduleId)
		{
			$this->_equipment = $equipment;
			$this->_moduleId = (int) $moduleId;		// Test moduleId or cast to INT
			$this->_moduleApi = new Api\Equipment($this->_moduleId);
		}

		/**
		  * @return int
		  */
		public function getModuleId()
		{
			return $this->_moduleId;
		}

		/**
		  * @return \PhpCliShell\Addon\Dcim\Api\Equipment
		  */
		public function getModuleApi()
		{
			return $this->_moduleApi;
		}

		/**
		  * @return string
		  */
		public function getModuleName()
		{
			return $this->_moduleApi->label;
		}

		/**
		  * @return \PhpCliShell\Addon\Dcim\Equipment\Equipment
		  */
		public function getEquipment()
		{
			return $this->_equipment;
		}

		/**
		  * @param \PhpCliShell\Addon\Dcim\Equipment\Slot $slot
		  * @return $this
		  */
		public function declareSlot(Slot $slot)
		{
			$slotKey = $slot->getSlotKey();

			if(array_key_exists($slotKey, $this->_slots)) {
				throw new Exception("Le slot '".$slot->getSlotName()."' est déjà déclaré sous la clé '".$slotKey."'", E_USER_ERROR);
			}

			/**
			  * Un slot est une interface physique!
			  *
			  * Un slot ne peut pas être lié à une interface virtuelle car
			  * il ne permet pas d'interconnecter directement deux équipements
			  *
			  * Par conséquent, un slot seul ne peut être une interface entre deux équipements
			  */

			$this->_slots[$slotKey] = $slot;
			return $this;
		}

		/**
		  * @param string|\PhpCliShell\Addon\Dcim\Equipment\Slot $name Slot object or key
		  * @return false|\PhpCliShell\Addon\Dcim\Equipment\Slot
		  */
		public function retrieveSlot($name)
		{
			if(is_object($name))
			{
				if($name instanceof Slot) {
					$name = $name->getSlotKey();
				}
				else {
					throw new Exception("Slot name must be a string or an Equipment\Slot object, '".gettype($name)."' given", E_USER_ERROR);
				}
			}

			return (array_key_exists($name, $this->_slots)) ? ($this->_slots[$name]) : (false);
		}

		/**
		  * @return \PhpCliShell\Addon\Dcim\Equipment\Slot[] Slot objects
		  */
		public function getSlots()
		{
			return $this->_slots;
		}

		/**
		  * @param \PhpCliShell\Addon\Dcim\Equipment\Slot $slot
		  * @return $this
		  */
		public function undeclareSlot(Slot $slot)
		{
			$this->unsetSlot($slot);
			return $this;
		}

		/**
		  * @param \PhpCliShell\Addon\Dcim\Equipment\Port $port
		  * @return $this
		  */
		public function declarePort(Port $port)
		{
			$portKey = $port->getPortKey();

			if(array_key_exists($portKey, $this->_ports)) {
				throw new Exception("Le port '".$port->getPortName()."' est déjà déclaré sous la clé '".$portKey."'", E_USER_ERROR);
			}

			$this->_ports[$portKey] = $port;
			return $this;
		}

		/**
		  * @param string|\PhpCliShell\Addon\Dcim\Equipment\Port $name Port object or key
		  * @return false|\PhpCliShell\Addon\Dcim\Equipment\Port
		  */
		public function retrievePort($name)
		{
			if(is_object($name))
			{
				if($name instanceof Port) {
					$name = $name->getPortKey();
				}
				else {
					throw new Exception("Port name must be a string or an Equipment\Port object, '".gettype($name)."' given", E_USER_ERROR);
				}
			}

			return (array_key_exists($name, $this->_ports)) ? ($this->_ports[$name]) : (false);
		}

		/**
		  * @return \PhpCliShell\Addon\Dcim\Equipment\Port[] Port objects
		  */
		public function getPorts()
		{
			return $this->_ports;
		}

		/**
		  * @param \PhpCliShell\Addon\Dcim\Equipment\Port $port
		  * @return $this
		  */
		public function undeclarePort(Port $port)
		{
			$this->unsetPort($port);
			return $this;
		}

		/**
		  * @param \PhpCliShell\Addon\Dcim\Equipment\Interface_ $interface
		  * @return $this
		  */
		public function declareInterface(Interface_ $interface)
		{
			$interfaceId = $interface->getInterfaceId();

			if(array_key_exists($interfaceId, $this->_interfaces)) {
				throw new Exception("L'interface '".$interface->getInterfaceName()."' est déjà déclarée sous l'ID '".$interfaceId."'", E_USER_ERROR);
			}

			$this->_interfaces[$interfaceId] = $interface;
			return $this;
		}

		/**
		  * @param string|\PhpCliShell\Addon\Dcim\Equipment\Interface_ $name Interface object or ID
		  * @param null|int $index Interface index
		  * @return false|\PhpCliShell\Addon\Dcim\Equipment\Interface_
		  */
		public function retrieveInterface($name, $index = null)
		{
			if(is_object($name))
			{
				if($name instanceof Interface_) {
					$name = $name->getInterfaceId();
				}
				else {
					throw new Exception("Interface name must be a string or an Equipment\Interface_ object, '".gettype($name)."' given", E_USER_ERROR);
				}
			}
			else
			{
				if($index !== null) {
					$name .= $this->_equipment::INT_INTERFACE_SEPARATOR.$index;
				}
			}

			return (array_key_exists($name, $this->_interfaces)) ? ($this->_interfaces[$name]) : (false);
		}

		/**
		  * @return \PhpCliShell\Addon\Dcim\Equipment\Interface_[]
		  */
		public function getInterfaces()
		{
			return $this->_interfaces;
		}

		/**
		  * @param \PhpCliShell\Addon\Dcim\Equipment\Interface_ $interface
		  * @return $this
		  */
		public function undeclareInterface(Interface_ $interface)
		{
			$this->unsetInterface($interface);
			return $this;
		}

		/**
		  * @param string|\PhpCliShell\Addon\Dcim\Equipment\Slot $name Slot object or key
		  * @return bool
		  */
		public function issetSlot($name)
		{
			return ($this->retrieveSlot($name) !== false);
		}

		/**
		  * @param string|\PhpCliShell\Addon\Dcim\Equipment\Port $name Port object or key
		  * @return bool
		  */
		public function issetPort($name)
		{
			return ($this->retrievePort($name) !== false);
		}

		/**
		  * @param string|\PhpCliShell\Addon\Dcim\Equipment\Interface_ $name Interface object or ID
		  * @param null|int $index Interface index
		  * @return bool
		  */
		public function issetInterface($name, $index = null)
		{
			return ($this->retrieveInterface($name, $index) !== false);
		}

		/**
		  * @param string|\PhpCliShell\Addon\Dcim\Equipment\Slot $name Slot object or key
		  * @return $this
		  */
		public function unsetSlot($name)
		{
			$Equipment_Slot = $this->retrieveSlot($name);

			if($Equipment_Slot !== false) {
				unset($this->_slots[$Equipment_Slot->getSlotKey()]);
			}

			return $this;
		}

		/**
		  * @param string|\PhpCliShell\Addon\Dcim\Equipment\Port $name Port object or key
		  * @return $this
		  */
		public function unsetPort($name)
		{		
			$Equipment_Port = $this->retrievePort($name);

			if($Equipment_Port !== false) {
				unset($this->_ports[$Equipment_Port->getPortKey()]);
			}

			return $this;
		}

		/**
		  * @param string|\PhpCliShell\Addon\Dcim\Equipment\Interface_ $name Interface object or ID
		  * @param null|int $index Interface index
		  * @return $this
		  */
		public function unsetInterface($name, $index = null)
		{		
			$Equipment_Interface = $this->retrieveInterface($name, $index);

			if($Equipment_Interface !== false) {
				unset($this->_interfaces[$Equipment_Interface->getInterfaceId()]);
			}

			return $this;
		}

		/**
		  * @param mixed $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'id':
				case 'moduleId':
					return $this->getModuleId();
				case 'api':
				case 'moduleApi':
					return $this->getModuleApi();
				case 'name':
				case 'moduleName':
					return $this->getModuleName();
				case 'equipment':
					return $this->getEquipment();
				case 'slots':
					return $this->getSlots();
				case 'ports':
					return $this->getPorts();
				case 'interfaces':
					return $this->getInterfaces();
				default:
					throw new Exception("This attribute '".$name."' does not exist", E_USER_ERROR);
			}
		}
	}