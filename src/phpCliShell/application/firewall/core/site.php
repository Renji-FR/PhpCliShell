<?php
	namespace PhpCliShell\Application\Firewall\Core;

	use PhpCliShell\Core as C;

	class Site extends C\ArrayItem
	{
		/**
		  * @return \PhpCliShell\Core\Config
		  */
		public function getConfig()
		{
			return $this->_arrayObject;
		}

		/**
		  * @return false|string
		  */
		public function getGuiProtocol()
		{
			return ($this->_arrayObject->key_exists('gui')) ? ($this->_arrayObject['gui']) : (false);
		}

		/**
		  * @return false|string
		  */
		public function getGuiAddress()
		{
			$guiProtocol = $this->getGuiProtocol();

			if($guiProtocol !== false && $this->_arrayObject->key_exists($guiProtocol)) {
				return $this->_arrayObject[$guiProtocol];
			}
			else {
				return false;
			}
		}

		/**
		  * @return array Zones
		  */
		public function getZones()
		{
			$topology = $this->_arrayObject->topology->toArray();

			$onPremise = $topology['onPremise'];
			$interSite = $topology['interSite'];
			$internet = $topology['internet'];

			$zones = array_merge($onPremise, $internet);

			foreach($interSite as $interZones) {
				$zones = array_merge($zones, $interZones);
			}

			$zones = array_unique($zones);
			natsort($zones);
			return $zones;
		}
	}