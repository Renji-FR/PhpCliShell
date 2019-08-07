<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Adapter\Json;

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
	abstract class AbstractJson extends AbstractAdapter
	{
		/**
		  * @var string
		  */
		const FORMAT = Adapter\Json::FORMAT;

		/**
		  * @var string
		  */
		const EXTENSION = Adapter\Json::EXTENSION;


		/**
		  * @param string $filename File to apply
		  * @param bool $checkValidity
		  * @return bool
		  */
		abstract public function apply($filename, $checkValidity = true);

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
			if(file_exists($filename))
			{
				if(is_readable($filename))
				{
					$json = file_get_contents($filename);

					if($json !== false)
					{
						// @todo php 7.3
						//$datas = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
						$datas = json_decode($json, true);

						if($datas !== null) {
							return $datas;
						}
						else {
							throw new E\Message("Le fichier de sauvegarde '".$filename."' n'a pas une structure JSON valide", E_USER_ERROR);
						}
					}
					else {
						throw new E\Message("Le contenu du fichier de sauvegarde '".$filename."' n'a pas pu être récupéré", E_USER_ERROR);
					}
				}
				else {
					throw new E\Message("Le fichier de sauvegarde '".$filename."' ne peut être lu", E_USER_ERROR);
				}
			}
			else {
				throw new E\Message("Le fichier de sauvegarde '".$filename."' n'existe pas", E_USER_ERROR);
			}
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
				// @todo php 7.3
				//$json = json_encode($configs, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
				$json = json_encode($configs, JSON_PRETTY_PRINT);

				if($json !== false) {
					$status = file_put_contents($filename, $json, LOCK_EX);
					return ($status !== false);
				}
				else {
					throw new E\Message("Une erreur s'est produite durant la génération du JSON de la configuration", E_USER_ERROR);
				}
			}
			else {
				$eMessage = "Impossible de sauvegarder la configuration dans '".$filename."'.".PHP_EOL;
				$eMessage .= "Vérifiez les droits d'écriture du fichier et le chemin '".$pathname."'";
				throw new E\Message($eMessage, E_USER_ERROR);
			}

			return $status;
		}

		/**
		  * @todo temporaire/compatibilité
		  * Permet le changement de type à key
		  *
		  * @param string $type
		  * @param null|array $types
		  * @return false|string
		  */
		protected function _typeToKey($type, array $keys = null)
		{
			if($keys === null) {
				$keys = $this->_ORCHESTRATOR::API_INDEXES;
			}

			return (array_key_exists($type, $keys)) ? ($keys[$type]) : (false);
		}

		/**
		  * @todo temporaire/compatibilité
		  * Permet le changement de key à type
		  *
		  * @param string $key
		  * @param null|array $keys
		  * @return false|string
		  */
		protected function _keyToType($key, array $keys = null)
		{
			if($keys === null) {
				$keys = $this->_ORCHESTRATOR::API_INDEXES;
			}

			$type = array_search($key, $keys, false);
			return ($type !== false) ? ($type) : ($key);
		}
	}