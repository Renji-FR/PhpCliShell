<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Config\Helper;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Network;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Core\Template;

	class Export extends AbstractHelper
	{
		const EXPORT_FORMAT_CLASS = array(
			'cisco_asa' => Template\Cisco\Asa::class,
			'cisco_asa-dap' => Template\Cisco\AsaDap::class,
			'juniper_junos' => Template\Juniper\Junos::class,
			'juniper_junos-set' => Template\Juniper\JunosSet::class,
			'web_html' => Template\Web\Html::class,
		);

		const COPY_METHOD = array(
			'scp' => null,
		);


		public function export(array $firewalls, $type, $format, $force)
		{
			$exports = $this->_export($firewalls, $type, $format, $force);
			return ($exports !== false);
		}

		public function copy(array $firewalls, $type, $format, $method, $siteName)
		{
			if(array_key_exists($siteName, $firewalls))
			{
				$firewalls = array($siteName => $firewalls[$siteName]);
				$exports = $this->_export($firewalls, $type, $format, true);

				if($exports !== false)
				{
					$this->_SHELL->EOL();

					if(array_key_exists($siteName, $exports))
					{
						$exportFile = $exports[$siteName];

						if(array_key_exists($method, self::COPY_METHOD))
						{
							$SiteManager = $this->_SHELL->program->getProgram('site');
							$sites = $SiteManager->getAvailableSites();

							if(isset($sites->{$siteName}))
							{
								$site = $sites->{$siteName};

								if($SiteManager->isSiteEnabled($siteName))
								{
									$firewallStore = $this->_SHELL->program->getProgram('store');

									if(($firewall = $firewallStore->getFirewall($siteName)) !== false)
									{
										switch($method)
										{
											case 'scp':
											{
												$remoteSSH = false;

												$bastionCallback = function(Network\Ssh $bastionSSH)
												{
													if($bastionSSH->isConnected && $bastionSSH->isAuthenticated) {
														$this->_SHELL->print('SSH tunnel is established! (PID: '.$bastionSSH->processPid.')', 'green');
													}
												};

												try {
													$remoteSSH = $firewall->ssh($bastionCallback);
												}
												catch(E\Message $e) {
													$this->_SHELL->throw($e);
												}
												catch(\Exception $e) {
													$this->_SHELL->error("An error occured during SSH connection:".PHP_EOL.$e->getMessage(), 'orange');
												}

												if($remoteSSH !== false)
												{
													$status = false;

													if($remoteSSH->isConnected && $remoteSSH->isAuthenticated)
													{
														$this->_SHELL->print('SSH session is established! (PID: '.$remoteSSH->processPid.')', 'green');

														/**
														  * Supprime ^M (\r)
														  */
														switch($site->os)
														{
															case 'juniper-junos': {
																$fileContents = file_get_contents($exportFile);
																$fileContents = str_replace("\r", '', $fileContents);
																file_put_contents($exportFile, $fileContents, LOCK_EX);
																break;
															}
														}
														
														$scpRemoteFile = $site->scp_remoteFile;

														$isAliveID = 0;

														$waitingCallback = function(C\Process $scpProcess, $stdout, $stderr) use (&$isAliveID) {
															$message = $this->_SHELL->format('Please wait for SCP transfer! (ID: '.$isAliveID++.') (PID: '.$scpProcess->pid.')', 'blue');
															$this->_SHELL->terminal->updateMessage($message);
														};

														$this->_SHELL->EOL();

														try {
															$status = $remoteSSH->putFile($exportFile, $scpRemoteFile, false, 0644, $waitingCallback, 2);
														}
														catch(E\Message $e) {
															$this->_SHELL->error($e->getMessage(), 'orange');
														}
														catch(\Exception $e) {
															$this->_SHELL->error("SCP ERROR: ".$e->getMessage(), 'red');
														}

														if($status) {
															$this->_SHELL->print("Copie configuration '".$scpRemoteFile."' terminée!", 'green');
														}
														else {
															$this->_SHELL->error("Impossible d'envoyer en SSH le fichier '".$exportFile."' au site '".$siteName."' (".$scpRemoteFile.")", 'orange');
														}
													}
													else {
														$this->_SHELL->error("Impossible d'établir la session SSH au site '".$siteName."'", 'orange');
													}

													$remoteSSH->disconnect();
													return $status;
												}

												break;
											}
											default: {
												$this->_SHELL->error("La méthode '".$method."' n'est pas supportée", 'orange');
											}
										}
									}
									else {
										$this->_SHELL->error("Le firewall du site '".$siteName."' n'est pas disponible", 'orange');
									}
								}
								else {
									$this->_SHELL->error("Veuillez activer le '".$siteName."' avant l'importation", 'orange');
								}
							}
							else {
								$this->_SHELL->error("Le configuration du site '".$siteName."' n'existe pas", 'orange');
							}
						}
						else {
							$this->_SHELL->error("La méthode '".$method."' n'est pas supportée", 'orange');
						}
					}
					else {
						$this->_SHELL->error("La configuration correspondante au site '".$siteName."' n'a pas été trouvée", 'orange');
					}
				}
				else {
					$this->_SHELL->error("Une erreur s'est produite durant l'export de la configuration", 'orange');
				}
			}
			else {
				$this->_SHELL->error("Le site '".$siteName."' n'est pas activé", 'orange');
			}

			return false;
		}

		protected function _export(array $firewalls, $type, $format, $force)
		{
			$Core_Template_Abstract = $this->_getExportClass($format);

			if($Core_Template_Abstract !== false)
			{
				$sections = $this->_getExportSections($Core_Template_Abstract, $type);

				if($sections !== false)
				{
					$fwlCounter = count($firewalls);						

					if($fwlCounter > 0)
					{
						if($this->_ORCHESTRATOR->isSaving || !$this->_ORCHESTRATOR->hasChanges || $force)
						{
							try {
								return $this->_templating($Core_Template_Abstract, $firewalls, $sections);
							}
							catch(\Exception $e) {
								$this->_SHELL->throw($e);
								return false;
							}
						}
						else {
							$this->_SHELL->error("Il est vivement recommandé de sauvegarder la configuration avant de l'exporter. Pour l'exporter malgrés tout utilisez l'argument 'force'", 'orange');
						}
					}
					else {
						$this->_SHELL->error("Aucun site n'a été déclaré, il n'y a donc pas de configuration à exporter", 'orange');
					}
				}
				else {
					$this->_SHELL->error("Section type '".$type."' non supporté", 'orange');
				}
			}
			else {
				$this->_SHELL->error("Format d'export '".$format."' non supporté", 'orange');
			}

			return false;
		}

		/**
		  * @param string $format
		  * @return string Template classname
		  */
		protected function _getExportClass($format)
		{
			if(array_key_exists($format, self::EXPORT_FORMAT_CLASS)) {
				return self::EXPORT_FORMAT_CLASS[$format];
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $Core_Template_Abstract
		  * @param null|string $type
		  * @return false|array Sections
		  */
		protected function _getExportSections($Core_Template_Abstract, $type)
		{
			if($type === null) {
				return $Core_Template_Abstract::API_TYPE_SECTION;
			}
			elseif(array_key_exists($type, $Core_Template_Abstract::API_TYPE_SECTION)) {
				return (array) $Core_Template_Abstract::API_TYPE_SECTION[$type];
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $templateClass Template classname
		  * @param \PhpCliShell\Application\Firewall\Core\Firewall[] $firewalls
		  * @param array $sections
		  * @return array Exports
		  */
		protected function _templating($templateClass, array $firewalls, array $sections)
		{
			$exports = array();
			$sites = $this->_objects[Api\Site::API_TYPE];

			$index = 0;
			$fwlCounter = count($firewalls);

			foreach($firewalls as $site => $Firewall)
			{
				$this->_SHELL->print("Préparation de la configuration pour '".$Firewall->name."'", 'green');

				// /!\ On doit passer un template clean pour éviter toutes interactions négatives
				$Core_Template_Abstract = new $templateClass($this->_SHELL, $sites);

				try {
					$status = $Core_Template_Abstract->templating($Firewall, $sections);
				}
				catch(\Exception $e) {
					$this->_SHELL->throw($e);
					$status = false;
				}

				if($status)
				{
					$exports[$site] = $Core_Template_Abstract->export;
					$this->_SHELL->print("Configuration disponible: '".$exports[$site]."'", 'green');

					if($index < $fwlCounter-1) {
						$this->_SHELL->EOL();
						$index++;
					}
				}
				else {
					$msg = "Une erreur s'est produite durant la génération du template '".$Core_Template_Abstract->template."'";
					$msg .= " pour le firewall '".$Firewall->name."'";
					throw new E\Message($msg, E_USER_ERROR);
				}
			}

			return $exports;
		}
	}