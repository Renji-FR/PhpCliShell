<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\Csv;

	use SplFileObject;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Manager;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter;
	use PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\AbstractAdapter;

	/**
	  * Pour toutes les méthodes publiques d'importation:
	  * Throw Exception	: permet d'indiquer que les données sont corrompues
	  * Return false	: permet d'indique une erreur mais sans donnée corrompue
	  */
	abstract class AbstractCsv extends AbstractAdapter
	{
		/**
		  * @var string
		  */
		const FORMAT = Adapter\Csv::FORMAT;

		/**
		  * @var string
		  */
		const EXTENSION = Adapter\Csv::EXTENSION;

		/**
		  * @var string
		  */
		const CSV_DELIMITER = ';';


		/**
		  * @param array $configItems Configurations to export
		  * @return false|array Configuration datas
		  */
		protected function _export(array $configs)
		{
			$export = array();

			foreach($configs as $type => $config) {
				$key = $this->_typeToKey($type);
				$export[$key] = $config;
			}

			return $export;
		}

		/**
		  * @param string $filename Configuration filename
		  * @return array Configuration datas
		  */
		protected function _load($filename)
		{
			$configs = array();

			if(file_exists($filename) && is_readable($filename))
			{
				$SplFileObject = new SplFileObject($filename, 'r');
				//$SplFileObject->setFlags(SplFileObject::DROP_NEW_LINE);

				foreach($SplFileObject as $index => $line)
				{
					if(C\Tools::is('string&&!empty', $line)) {
						$configs[] = str_getcsv($line, static::CSV_DELIMITER);
					}
				}
			}

			return $configs;
		}

		/**
		  * @param array $configs Configurations to save
		  * @param string $filename Configuration filename
		  * @return bool Save status
		  */
		protected function _save(array $configs, $filename)
		{
			$status = false;

			$fileExists = file_exists($filename);
			$pathname = pathinfo($filename, PATHINFO_DIRNAME);
			$pathname = C\Tools::pathname($pathname, true, true);		// Permet juste le mkdir

			if((!$fileExists && is_writable($pathname)) || ($fileExists && is_writable($filename)))
			{
				$fp = fopen($filename, 'w');

				if($fp !== false)
				{
					foreach($configs as $config)
					{
						$status = fputcsv($fp, $config, static::CSV_DELIMITER);

						if($status === false) {
							break;
						}
					}

					fclose($fp);
				}
			}

			return $status;
		}
	}