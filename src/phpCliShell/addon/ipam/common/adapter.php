<?php
	namespace PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Core as C;

	abstract class Adapter extends C\Addon\Adapter
	{
		/**
		  * @return string
		  */
		public function getServerId()
		{
			return $this->getServiceId();
		}

		/**
		  * @return string Server address/host (PHP_URL_HOST)
		  */
		public function getServerAdd()
		{
			return parse_url($this->_server, PHP_URL_HOST);
		}

		/**
		  * @return string Server URL
		  */
		public function getServerUrl()
		{
			return $this->_server;
		}

		/**
		  * @return string WEB URL
		  */
		public function getWebUrl()
		{
			return $this->_server;
		}
    }