<?php
	namespace PhpCliShell\Core\Addon\Orchestrator;

	use PhpCliShell\Core as C;

	interface InterfaceOrchestrator
	{
		/**
		  * @return bool
		  */
		public static function hasInstance();

		/**
		  * @param \PhpCliShell\Core\Config $config
		  * @return \PhpCliShell\Core\Addon\Orchestrator\InterfaceOrchestrator
		  */
		public static function getInstance(C\Config $config = null);
	}