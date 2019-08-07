<?php
	namespace PhpCliShell\Cli\Shell\Program;

	use PhpCliShell\Core as C;

	abstract class Main
	{
		/**
		  * @var \PhpCliShell\Core\Config
		  */
		protected $_CONFIG;


		public function __construct()
		{
			$this->_CONFIG = C\Config::getInstance();
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'config':
				case 'configuration': {
					return $this->_CONFIG;
				}
				default: {
					throw new Exception('Attribute "'.$name.'" does not exist', E_USER_ERROR);
				}
			}
		}
	}