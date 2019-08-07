<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall;

	use ArrayObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall as FirewallProgram;

	abstract class AbstractProgram
	{
		/**
		  * @var array
		  */
		const API_TYPES = array();

		/**
		  * @var array
		  */
		const API_INDEXES = array();

		/**
		  * @var array
		  */
		const API_CLASSES = array();

		/**
		  * @var \PhpCliShell\Cli\Shell\Main
		  */
		protected $_SHELL;

		/**
		  * @var \PhpCliShell\Cli\Terminal\Main
		  */
		protected $_TERMINAL;

		/**
		  * @var \PhpCliShell\Application\Firewall\Shell\Program\Firewall
		  */
		protected $_firewallProgram;

		/**
		  * @var \ArrayObject
		  */
		protected $_objects;


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall $firewallProgram
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, FirewallProgram $firewallProgram, ArrayObject $objects)
		{
			$this->_SHELL = $SHELL;
			$this->_TERMINAL = $SHELL->terminal;
			$this->_firewallProgram = $firewallProgram;

			$this->_objects = $objects;
		}

		/**
		  * @param mixed $name
		  * @return mixed
		  */
		public function __get($name)
		{
			throw new Exception("Attribute name '".$name."' does not exist", E_USER_ERROR);
		}

		/**
		  * @param string $type
		  * @return bool
		  */
		public static function isType($type)
		{
			return in_array($type, static::API_TYPES, true);
		}

		/**
		  * @param string $type
		  * @return false|string
		  */
		public static function getClass($type)
		{
			return static::_typeToClass($type);
		}

		/**
		  * @param string $type
		  * @return false|string
		  */
		public static function getName($type, $ucFirst = false, $strToUpper = false)
		{
			$class = static::_typeToClass($type);

			if($class !== false)
			{
				if($ucFirst) {
					return ucfirst($class::API_LABEL);
				}
				elseif($strToUpper) {
					return strtoupper($class::API_LABEL);
				}
				else {
					return $class::API_LABEL;
				}
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $type
		  * @param bool $throwException
		  * @return bool
		  */
		protected static function _typeIsAllowed($type, $throwException = true)
		{
			if(in_array($type, static::API_TYPES, true)) {
				return true;
			}
			elseif($throwException) {
				throw new Exception("Objects of type '".$type."' can not be managed by '".static::class."'", E_USER_ERROR);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $type
		  * @return false|string
		  */
		protected static function _typeToKey($type)
		{
			return (array_key_exists($type, static::API_INDEXES)) ? (static::API_INDEXES[$type]) : (false);
		}

		/**
		  * @param string $type
		  * @return false|string
		  */
		protected static function _typeToClass($type)
		{
			return (array_key_exists($type, static::API_CLASSES)) ? (static::API_CLASSES[$type]) : (false);
		}

		/**
		  * @param string $key
		  * @return false|string
		  */
		protected static function _keyToType($key)
		{
			$types = array_keys(static::API_INDEXES, $key, true);
			return (count($types) === 1) ? (current($types)) : (false);
		}

		/**
		  * @param string $class
		  * @return false|string
		  */
		protected static function _classToType($class)
		{
			$types = array_keys(static::API_CLASSES, $class, true);
			return (count($types) === 1) ? (current($types)) : (false);
		}
	}