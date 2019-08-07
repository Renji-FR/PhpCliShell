<?php
	namespace PhpCliShell\Launcher;

	require_once(PROJECT_ROOT_DIR . '/core/autoloader.php');

	use PhpCliShell\Core as C;

	class Autoloader extends C\Autoloader
	{
		protected static $_replacements = array(
			'#^PhpCliShell$#i' => null,
			'#^App$#i' => 'Application',
		);

		protected static $_ROOT_DIR = null;


		protected static function _load($class)
		{
			$class .= static::PHP_FILE_EXT;
			$rootDir = static::_getRootDir();

			if(file_exists($rootDir.'/'.$class)) {
				require_once($rootDir.'/'.$class);
				return;
			}
		}

		protected static function _getRootDir()
		{
			if(defined('PROJECT_ROOT_DIR')) {
				return PROJECT_ROOT_DIR;
			}
			elseif(($phar = Phar::running()) !== '') {
				return $phar;
			}
			else
			{
				if(static::$_ROOT_DIR === null) {
					static::$_ROOT_DIR = dirname(dirname(__FILE__));
				}

				return static::$_ROOT_DIR;
			}
		}
	}