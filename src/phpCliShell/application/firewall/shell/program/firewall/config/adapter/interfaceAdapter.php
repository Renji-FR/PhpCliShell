<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter;

	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config;

	/**
	  * Pour toutes les méthodes publiques d'importation:
	  * Throw Exception	: permet d'indiquer que les données sont corrompues
	  * Return false	: permet d'indique une erreur mais sans donnée corrompue
	  */
	interface InterfaceAdapter
	{
		/**
		  * Throw Exception pour indiquer que les données sont corrompues
		  * Return false pour indique une erreur mais sans donnée corrompue
		  *
		  * @param string $filename File to load
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @return false|array Number of objects loaded
		  */
		public function load($filename, $prefix = null, $suffix = null);

		/**
		  * @param string $filename File to save
		  * @param array $configs Configurations to save
		  * @return false|array Number of objects saved
		  */
		public function save($filename, array $configs);

		/**
		  * Throw Exception pour indiquer que les données sont corrompues
		  * Return false pour indique une erreur mais sans donnée corrompue
		  *
		  * @param string $filename File to import
		  * @param string $prefix Rule name prefix
		  * @param string $suffix Rule name suffix
		  * @return false|array Number of objects imported
		  */
		public function import($filename, $prefix = null, $suffix = null);

		/**
		  * @param string $filename File to export
		  * @param array $configs Configurations to export
		  * @return false|array Number of objects exported
		  */
		public function export($filename, array $configs);
	}