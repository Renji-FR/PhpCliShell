<?php
	namespace PhpCliShell\Addon\Ipam\Phpipam\Connector;

	use ArrayObject;

	use PhpCliShell\Core as C;

	use PhpCliShell\Addon\Ipam\Phpipam\Service;
	use PhpCliShell\Addon\Ipam\Phpipam\Exception;

	abstract class AbstractRestConnector extends AbstractConnector implements InterfaceRestConnector
	{
		/**
		  * @var string
		  */
		const LABEL = 'UNKNOWN';

		/**
		  * @var string
		  */
		const METHOD = 'REST';

		/**
		  * @var array
		  */
		const REST_URN = array();

		/**
		  * @var string
		  */
		const SECTION_ROOT_ID = '0';

		/**
		  * @var string
		  */
		const FOLDER_ROOT_ID = '0';

		/**
		  * @var string
		  */
		const SUBNET_ROOT_ID = '0';

		/**
		  * @var string
		  */
		const SUBNET_IS_FOLDER = '1';

		/**
		  * @var string
		  */
		const SUBNET_IS_NOT_FOLDER = '0';

		/**
		  * IPAM server URL
		  * @var string
		  */
		protected $_server;

		/**
		  * @var string
		  */
		protected $_application;

		/**
		  * Core\Rest API
		  * @var \ArrayObject
		  */
		protected $_restAPI;


		/**
		  * @param \PhpCliShell\Addon\Ipam\Phpipam\Service $service
		  * @param \PhpCliShell\Core\Config $config
		  * @param string $server
		  * @param string $application
		  * @param string $token
		  * @param string $login
		  * @param string $password
		  * @param bool $debug
		  * @return $this
		  */
		public function __construct(Service $service, C\Config $config, $server, $application, $token, $login, $password, $debug = false)
		{
			parent::__construct($service, $config, $debug);

			$this->_server = rtrim($server, '/');
			$this->_application = $application;
			$this->_restAPI = new ArrayObject();

			$httpProxy = getenv('http_proxy');
			$httpsProxy = getenv('https_proxy');

			if($token === false)
			{
				if($login !== false && $password !== false) {
					$this->_initRestAPI('user', $this->_server, $this->_application, static::REST_URN['user'], $httpProxy, $httpsProxy);
					$this->_restAPI->user->setHttpAuthCredentials($login, $password);
					$response = $this->_restAPI->user->post();
					$response = $this->_getCallResponse($response);

					if($response !== false && isset($response['token'])) {
						$token = $response['token'];
					}
					else {
						throw new Exception("Le token d'autorisation pour l'accès à phpIPAM n'a pas pu être récupéré", E_USER_ERROR);
					}
				}
				else {
					throw new Exception("Le token ou les identifiants pour phpIPAM sont manquants", E_USER_ERROR);
				}
			}

			// Exécuter ce qui suit même pour user!
			foreach(static::REST_URN as $key => $urn) {
				$this->_initRestAPI($key, $this->_server, $this->_application, $urn, $httpProxy, $httpsProxy);
				$this->_restAPI->{$key}->addHeader('token: '.$token);
			}
		}

		/**
		  * @param string $key
		  * @param string $server
		  * @param string $application
		  * @param string $urn
		  * @param string $httpProxy
		  * @param string $httpsProxy
		  * @return void
		  */
		protected function _initRestAPI($key, $server, $application, $urn, $httpProxy, $httpsProxy)
		{
			$server = $server.'/api/'.$application.'/'.$urn;
			$this->_restAPI->{$key} = new C\Rest($server, 'PHPIPAM_'.$key, $this->_debug);

			$this->_restAPI->{$key}
					->setOpt(CURLOPT_HEADER, false)
					->setOpt(CURLOPT_RETURNTRANSFER, true)
					->setOpt(CURLOPT_FOLLOWLOCATION, true)
					->addHeader('Content-Type: application/json');

			switch(substr($server, 0, 6))
			{
				case 'http:/':
				{
					if(C\Tools::is('string&&!empty', $httpProxy))
					{
						$this->_restAPI->{$key}
								//->setHttpAuthMethods(true)	// NE PAS UTILISER EN HTTP!
								->setOpt(CURLOPT_HTTPPROXYTUNNEL, true)
								->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_HTTP)
								->setOpt(CURLOPT_PROXY, $httpProxy);
					}
					break;
				}
				case 'https:':
				{
					if(C\Tools::is('string&&!empty', $httpsProxy))
					{
						$this->_restAPI->{$key}
								->setHttpAuthMethods(true)
								->setOpt(CURLOPT_HTTPPROXYTUNNEL, true)
								->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_HTTP)
								->setOpt(CURLOPT_PROXY, $httpsProxy);
					}
					break;
				}
				default: {
					throw new Exception("L'adresse du serveur phpIPAM doit commencer par http ou https", E_USER_ERROR);
				}
			}
		}

		// ============ TOOL =============
		/**
		  * @param string $json
		  * @return false|string|array
		  */
		protected function _getCallResponse($json)
		{
			$response = json_decode($json, true);

			if($this->_isValidResponse($response))
			{
				if(array_key_exists('data', $response)) {
					return (array) $response['data'];		// Must return array for detection
				}
				elseif(array_key_exists('message', $response)) {
					return (string) $response['message'];	// Must return string for detection
				}
			}

			return false;
		}

		protected function _isValidResponse($response)
		{
			return (!$this->_isEmptyResponse($response) && !$this->_isErrorResponse($response));
		}

		protected function _isEmptyResponse($response)
		{
			return (C\Tools::is('string&&empty', $response) || C\Tools::is('array&&count==0', $response));
		}

		protected function _isErrorResponse($response)
		{
			return (
				!is_array($response) || (array_key_exists('success', $response) && $response['success'] !== true) ||
				!array_key_exists('code', $response) || !($response['code'] >= 200 && $response['code'] <= 299)
			);
		}
		// ===============================

		/**
		 * @param bool $debug
		 * @return $this
		 */
		public function debug($debug = true)
		{
			$this->_debug = (bool) $debug;

			foreach(static::REST_URN as $key => $urn)
			{
				if(isset($this->_restAPI->{$key})) {
					$this->_restAPI->{$key}->debug($this->_debug);
				}
			}

			return $this;
		}

		public function close()
		{
			foreach(static::REST_URN as $key => $urn)
			{
				if(isset($this->_restAPI->{$key})) {
					$this->_restAPI->{$key}->close();
					unset($this->_restAPI->{$key});
				}
			}
			return $this;
		}

		public function __destruct()
		{
			$this->close();
		}
	}