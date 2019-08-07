<?php
	namespace PhpCliShell\Core;

	class Rest
	{
		/**
		  * @var string
		  */
		const BUILDER_IMPLODE = 'implode';

		/**
		  * @var string
		  */
		const BUILDER_URL_ENCODE = 'urlencode';

		/**
		  * @var string
		  */
		const BUILDER_HTTP_BUILD_QUERY = 'http_build_query';

		/**
		  * Service name
		  * @var string
		  */
		protected $_service;

		/**
		  * Server address
		  * @var string
		  */
		protected $_server;

		/**
		  * Curl ressource
		  * @var ressource
		  */
		protected $_handle;

		/**
		  * Universal ressource locator
		  * @var string
		  */
		protected $_url = null;

		/**
		  * Universal ressource name
		  * @var string
		  */
		protected $_urn = null;

		/**
		  * Universal ressource request
		  * @var string
		  */
		protected $_urr = null;

		/**
		  * Universal ressource identifier
		  * @var string
		  */
		protected $_uri = null;

		/**
		  * @var bool
		  */
		protected $_slashEndUrl = false;

		/**
		  * @var bool
		  */
		protected $_slashEndUrn = false;

		/**
		  * @var bool
		  */
		protected $_slashEndUrr = false;

		/**
		  * @var array
		  */
		protected $_headers = array();

		/**
		  * @var mixed
		  */
		protected $_response = null;

		/**
		 * @var bool
		 */
		protected $_debug = false;


		public function __construct($server, $service = null, $debug = false)
		{
			$this->_server = $server;
			$this->_service = (Tools::is('string&&!empty', $service)) ? ($service) : ('Unknown');

			$this->debug($debug);

			$this->_init(false);
		}

		public function getService()
		{
			return $this->_service;
		}

		protected function _init($close = true)
		{
			if($close === true && $this->_handle !== null) {
				curl_close($this->_handle);
			}

			$this->_handle = curl_init();

			if($this->_handle === false) {
				throw new Exception("Une erreur s'est produit lors de l'initialisation de la ressource CURL pour l'API REST '".$this->_service."'", E_USER_ERROR);
			}

			$this->setUrl($this->_server);
		}

		public function resetOpts()
		{
			curl_reset($this->_handle);
			$this->_response = null;
			return $this;
		}

		public function getOpt($optName)
		{
			return curl_getinfo($this->_handle, $optName);
		}

		public function getOpts()
		{
			return curl_getinfo($this->_handle);
		}

		public function setUrl($url)
		{
			if(Tools::is('string&&!empty', $url)) {
				$this->_url = trim($url, '/');
			}
			return $this;
		}

		public function getUrl()
		{
			return $this->_url;
		}

		public function setUrn($urn)
		{
			if(Tools::is('string&&!empty', $urn)) {
				$this->_urn = trim($urn, '/');
			}
			return $this;
		}

		public function getUrn()
		{
			return $this->_urn;
		}

		public function setUrr($urr)
		{
			if(Tools::is('string&&!empty', $urr)) {
				$this->_urr = trim($urr, '/');
			}
			elseif($urr === null) {
				$this->_urr = null;
			}
			return $this;
		}

		public function getUrr()
		{
			return $this->_urr;
		}

		public function getUri()
		{
			return $this->_uri;
		}

		public function setHttpAuthMethods($safe = true)
		{
			$safe = ($safe) ? (CURLAUTH_ANYSAFE) : (CURLAUTH_ANY);
			return $this->_setOpt(CURLOPT_HTTPAUTH, $safe);
		}

		public function setHttpAuthCredentials($login, $password)
		{
			return $this->_setOpt(CURLOPT_USERPWD, $login.':'.$password);
		}

		public function disableHttpAuth()
		{
			$this->_setOpt(CURLOPT_HTTPAUTH, null);
			$this->_setOpt(CURLOPT_USERPWD, null);
			return $this;
		}

		public function addHeader($header)
		{
			$this->_headers[] = $header;
			return $this->setOpt(CURLOPT_HTTPHEADER, $this->_headers);
		}

		public function getHeaders()
		{
			return $this->_headers;
		}

		public function forceSlashEndUrl($state = true)
		{
			$this->_slashEndUrl = (bool) $state;
			return $this;
		}

		public function forceSlashEndUrn($state = true)
		{
			$this->_slashEndUrn = (bool) $state;
			return $this;
		}

		public function forceSlashEndUrr($state = true)
		{
			$this->_slashEndUrr = (bool) $state;
			return $this;
		}

		public function setOpt($optName, $optValue)
		{
			switch($optName)
			{
				case CURLOPT_URL: {
					$this->setUrl($optValue);
					break;
				}
				default: {
					$this->_setOpt($optName, $optValue);
				}
			}

			return $this;
		}

		protected function _setOpt($optName, $optValue)
		{
			$result = curl_setopt($this->_handle, $optName, $optValue);

			if($result === false) {
				throw new Exception("Une erreur s'est produit lors de la déclaration de l'option '".$optName."' avec comme valeur '".$optValue."' pour l'API REST '".$this->_service."'", E_USER_ERROR);
			}

			return $this;
		}

		public function setOpts(array $options)
		{
			foreach(array_keys($options) as $key)
			{
				switch($key)
				{
					case CURLOPT_URL: {
						$this->setUrl($options[$key]);
						break;
					}
					default: {
						continue(2);
					}
				}

				unset($options[$key]);
			}

			$this->_setOpts($options);
			return $this;
		}

		protected function _setOpts(array $options)
		{
			$result = curl_setopt_array($this->_handle, $options);

			if($result === false) {
				throw new Exception("Une erreur s'est produit lors de la déclaration des options pour l'API REST '".$this->_service."'", E_USER_ERROR);
			}

			return $this;
		}

		/**
		  * @param null|string|array $query
		  * @param null|string|array $value
		  * @return mixed Request response
		  */
		public function get($query = null, $value = null)
		{
			if(Tools::is('array&&count>0', $query))
			{
				if(Tools::is('array', $value))
				{
					if(count($query) === count($value)) {
						$query = array_combine($query, $value);
					}
					else {
						throw new Exception("GET query arguments are not valid, keys and values has not the same number of elements (API REST '".$this->_service."')", E_USER_ERROR);
					}
				}
			}
			elseif(Tools::is('string&&!empty', $query))
			{
				if(Tools::is('string&&!empty', $value)) {
					$query = array($query => $value);
				}
			}
			else {
				$query = null;
			}

			$this->_setOpt(CURLOPT_FAILONERROR, false);
			$this->_setOpt(CURLOPT_RETURNTRANSFER, true);
			$this->_setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
			$this->_setOpt(CURLOPT_POSTFIELDS, null);

			return $this->_prepareUri($query)->_exec();
		}

		/**
		  * @param null|string|array $query
		  * @param null|array $datas
		  * @return mixed Call response
		  */
		public function post($query = null, array $datas = null)
		{
			return $this->_httpCall('POST', $query, $datas);
		}

		/**
		  * @param null|string|array $query
		  * @param null|array $datas
		  * @return mixed Call response
		  */
		public function patch($query = null, array $datas = null)
		{
			return $this->_httpCall('PATCH', $query, $datas);
		}

		/**
		  * @param null|string|array $query
		  * @param null|array $datas
		  * @return mixed Call response
		  */
		public function put($query = null, array $datas = null)
		{
			return $this->_httpCall('PUT', $query, $datas);
		}

		/**
		  * @param null|string|array $query
		  * @param null|array $datas
		  * @return mixed Call response
		  */
		public function delete($query = null, array $datas = null)
		{
			return $this->_httpCall('DELETE', $query, $datas);
		}

		/**
		  * @param string $method
		  * @param null|string|array $query
		  * @param null|array $datas
		  * @return mixed Call response
		  */
		protected function _httpCall($method, $query = null, array $datas = null)
		{
			$this->_setOpt(CURLOPT_FAILONERROR, false);
			$this->_setOpt(CURLOPT_RETURNTRANSFER, true);
			$this->_setOpt(CURLOPT_CUSTOMREQUEST, $method);

			if(Tools::is('array&&count>0', $datas))
			{
				//$fields = http_build_query($datas);
				$this->_setOpt(CURLOPT_POSTFIELDS, $datas);

				if($this->_debug) {
					Tools::e(PHP_EOL.'DEBUG [REST] '.__LINE__.' [DELETE] {datas}:', 'orange');
					print_r($datas);
				}
			}			

			return $this->_prepareUri($query)->_exec();
		}

		/**
		  * @param null|string|array $query
		  * @return $this
		  */
		protected function _prepareUri($query = null)
		{
			$url = $this->getUrl();
			$urn = $this->getUrn();
			$urr = $this->getUrr();

			if($this->_debug) {
				$query = var_export($query, true);
				Tools::e(PHP_EOL.'DEBUG [REST] '.__LINE__.' [PREPARE URI] {url}: '.$url, 'orange');
				Tools::e(PHP_EOL.'DEBUG [REST] '.__LINE__.' [PREPARE URI] {urn}: '.$urn, 'orange');
				Tools::e(PHP_EOL.'DEBUG [REST] '.__LINE__.' [PREPARE URI] {urr}: '.$urr, 'orange');
			}

			$uri = $url;

			if($urn !== null) {
				$uri .= '/'.$urn;
			}
			elseif($this->_slashEndUrl) {
				$uri .= '/';
			}

			if($urr !== null) {
				$uri .= '/'.$urr;
			}
			elseif($this->_slashEndUrn) {
				$uri .= '/';
			}

			if($this->_slashEndUrr) {
				$uri .= '/';
			}

			$uri = preg_replace('#(?<!:)\/{2,}#i', '/', $uri);
			$this->_uri = $uri;

			if(Tools::is('array&&count>0', $query)) {
				$uri .= '?'.http_build_query($query);
			}
			elseif(Tools::is('string&&!empty', $query)) {
				$uri .= '?'.$query;
			}

			if($this->_debug) {
				$query = var_export($query, true);
				Tools::e(PHP_EOL.'DEBUG [REST] '.__LINE__.' [PREPARE URI] {uri}: '.$uri, 'orange');
				Tools::e(PHP_EOL.'DEBUG [REST] '.__LINE__.' [PREPARE URI] {query}:'.PHP_EOL.$query.PHP_EOL, 'orange');
			}

			return $this->_setOpt(CURLOPT_URL, $uri);
		}

		/**
		  * @param array $query
		  * @param string $method
		  * @return false|string
		  */
		public function prepareQuery(array $query, $method = self::BUILDER_HTTP_BUILD_QUERY)
		{
			switch($method)
			{
				case self::BUILDER_IMPLODE: {
					return implode('&', $query);
				}
				case self::BUILDER_URL_ENCODE:
				{
					array_walk($query, function(&$value, $key) {
						$value = $key.'='.urlencode($value);
					});
					unset($value);

					return implode('&', $query);
				}
				case self::BUILDER_HTTP_BUILD_QUERY: {
					return http_build_query($query);
				}
				default: {
					return false;
				}
			}
		}

		protected function _exec()
		{
			if($this->_debug) {
				Tools::e(PHP_EOL.'DEBUG [REST] '.__LINE__.' [EXEC] {url}: '.curl_getinfo($this->_handle, CURLINFO_EFFECTIVE_URL), 'orange');
				$this->setOpt(CURLOPT_VERBOSE, true);
				$time1 = microtime(true);
			}

			$this->_response = curl_exec($this->_handle);

			if($this->_debug) {
				$time2 = microtime(true);
				Tools::e(PHP_EOL.'DEBUG [REST] '.__LINE__.' [EXEC] {microtime}: '.round($time2-$time1).'s', 'orange');
				Tools::e(PHP_EOL.'DEBUG [REST] '.__LINE__.' [EXEC] {curlinfo_total_time}: '.curl_getinfo($this->_handle, CURLINFO_TOTAL_TIME), 'orange');
			}

			if($this->_debug) {
				$debug = print_r(curl_getinfo($this->_handle), true);
				Tools::e(PHP_EOL.'DEBUG [REST] '.__LINE__.' [EXEC] {curl_getinfo}: '.$debug, 'orange');
			}

			/**
			  * Always reset URR for next call
			  * Exec it before throw exception
			  */
			$this->setUrr(null);

			if(($error = $this->getError()) !== false)
			{
				if($this->_debug) {
					Tools::e(PHP_EOL."DEBUG [REST] .__LINE__.' [RESPONSE] {var_dump}:".PHP_EOL, 'orange');
					var_dump($this->_response);
				}

				throw new Exception("L'erreur '".$error."' s'est produit lors de l'appel CURL '".$this->getUri()."' pour l'API REST '".$this->_service."'", E_USER_ERROR);
			}

			return $this->_response;
		}

		public function getError()
		{
			$errno = curl_errno($this->_handle);
			$error = curl_error($this->_handle);
			$httpCode = curl_getinfo($this->_handle, CURLINFO_RESPONSE_CODE);

			if($error !== "") {
				return $error;
			}
			elseif($errno !== 0) {
				return "PHP curl command returns ".$errno." error code";
			}
			elseif(!Tools::is('int', $httpCode)) {
				return "HTTP request does not return a numeric code";
			}
			elseif($httpCode < 200 || $httpCode > 299) {
				return "HTTP request returns ".$httpCode." error code";
			}
			else {
				return false;
			}
		}

		public function getHttpCode()
		{
			return curl_getinfo($this->_handle, CURLINFO_RESPONSE_CODE);
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'url': {
					return $this->getUrl();
				}
				case 'urn': {
					return $this->getUrn();
				}
				case 'urr': {
					return $this->getUrr();
				}
				case 'uri': {
					return $this->getUri();
				}
				default: {							// Methode magique: $rest->path1/path2/path3/..
					$urr = $this->getUrr().'/';
					$urr .= trim($name, '/');
					$this->setUrr($urr);
					return $this;
				}
			}

			//throw new Exception("Cet attribut '".$name."' n'existe pas", E_USER_ERROR);
		}

		public function __set($name, $value)
		{
			switch($name)
			{
				case 'url': {
					return $this->setUrl($value);
				}
				case 'urn': {
					return $this->setUrn($value);
				}
				case 'urr': {
					return $this->setUrr($value);
				}
				default: {
					throw new Exception("Cet attribut '".$name."' n'existe pas", E_USER_ERROR);
				}
			}
		}

		/**
		 * @param bool $debug
		 * @return $this
		 */
		public function debug($debug = true)
		{
			$this->_debug = (bool) $debug;
			return $this;
		}

		public function close()
		{
			if($this->_handle !== null) {
				curl_close($this->_handle);
				$this->_handle = null;
			}
			return $this;
		}

		public function __destruct()
		{
			$this->close();
		}
	}