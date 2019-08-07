<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall;

	use ArrayObject;

	use PhpCliShell\Cli;

	use PhpCliShell\Addon\Ipam\Netbox;
	use PhpCliShell\Addon\Ipam\Phpipam;

	use PhpCliShell\Application\Firewall\Shell\Program;

	class Ipam
	{
		/**
		  * @var string
		  */
		const FIELD_IPAM_SERVICE = '__ipam-srv__';

		/**
		  * @var string
		  */
		const FIELD_IPAM_ATTRIBUTES = '__ipam-attrs__';

		/**
		  * Program availables
		  * [ programId => programClass ]
		  *
		  * @var array
		  */
		const PROGRAMS = array(
			Netbox\Service::SERVICE_TYPE => Ipam\Netbox::class,
			Phpipam\Service::SERVICE_TYPE => Ipam\Phpipam::class
		);

		/**
		  * Program servers registered
		  * [ programClass => $serverKeys ]
		  *
		  * @var array
		  */
		protected static $_servers = array();

		/**
		  * @var \PhpCliShell\Cli\Shell\Main
		  */
		protected $_SHELL = null;

		/**
		  * Program instances
		  *
		  * @var array
		  */
		protected $_programs = array();


		/**
		  * @param \PhpCliShell\Cli\Shell\Main $SHELL
		  * @param \PhpCliShell\Application\Firewall\Shell\Program\Firewall $firewallProgram
		  * @param \ArrayObject $objects
		  * @return $this
		  */
		public function __construct(Cli\Shell\Main $SHELL, Program\Firewall $firewallProgram, ArrayObject $objects)
		{
			$this->_SHELL = $SHELL;

			foreach(self::$_servers as $program => $serverKeys) {
				$Program_Ipam = new $program($SHELL);
				$Program_Ipam->initAddon($serverKeys);
				$this->_programs[] = $Program_Ipam;
			}
		}

		/**
		  * Pour la recherche IPAM, ne pas regrouper les objets de même nom afin d'avoir IPv4 et IPv6 joints
		  * Cela permet d'avoir une visibilité "large" pour l'utilisateur et de se rendre compte de doublons ou autres incohérences
		  *
		  * @param string $type
		  * @param string $search
		  * @return bool
		  */
		public function printSearch($type, $search)
		{
			$end = (count($this->_programs) - 1);

			foreach($this->_programs as $index => $program)
			{
				$status = $program->printSearch($type, $search);

				if($index < $end) {
					$this->_SHELL->eol();
				}

				if(!$status) {
					return false;
				}
			}

			return true;
		}

		/**
		  * @return bool
		  */
		public function isAvailable()
		{
			foreach($this->_programs as $program)
			{
				$status = $program->hasServiceAvailable();

				if($status) {
					return true;
				}
			}

			return false;
		}

		/**
		  * @param string $name
		  * @param array $arguments
		  * @return array
		  */
		public function __call($name, array $arguments)
		{
			$results = array();

			foreach($this->_programs as $program) {
				$result = call_user_func_array(array($program, $name), $arguments);
				$results = array_merge($results, $result);
			}

			return $results;
		}

		/**
		  * @param string $application
		  * @param array $serverKeys
		  * @param bool $printInfoMessages
		  * @return bool Register status
		  */
		public static function register($application, array $serverKeys, $printInfoMessages = true)
		{
			if(array_key_exists($application, self::PROGRAMS))
			{
				$ipamAppProgram = self::PROGRAMS[$application];

				if($ipamAppProgram::isAddonPresent()) {
					self::$_servers[$ipamAppProgram] = $serverKeys;
					return true;
				}
			}

			return false;
		}
	}
