<?php
	namespace PhpCliShell\Addon\Ipam\Common\Connector;

	interface InterfaceConnector
	{
		/**
		  * @return false|string Gateway IP address
		  */
		public function getGatewayBySubnetId($subnetId);

		/**
		  * @param array $vlanIds VLAN number IDs
		  * @return array VLAN names
		  */
		public function getVlanNamesByVlanIds(array $vlanIds);
	}