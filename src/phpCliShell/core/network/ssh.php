<?php
	namespace PhpCliShell\Core\Network;

	use Closure;

	use PhpCliShell\Core as C;

	class Ssh
	{
		/**
		  * @var array
		  */
		const METHODS = array(
			'kex' => 'diffie-hellman-group-exchange-sha256,diffie-hellman-group-exchange-sha1,diffie-hellman-group14-sha1',
			'hostkey' => 'ssh-rsa,ssh-dss',
			'client_to_server' => array(
				'crypt' => 'aes128-ctr,aes192-ctr,aes256-ctr,aes128-cbc,aes192-cbc,aes256-cbc',
				'comp' => 'none,zlib@openssh.com,zlib',
				'mac' => 'hmac-sha2-256,hmac-sha2-512,hmac-sha1'
			),
			'server_to_client' => array(
				'crypt' => 'aes128-ctr,aes192-ctr,aes256-ctr,aes128-cbc,aes192-cbc,aes256-cbc',
				'comp' => 'none,zlib@openssh.com,zlib',
				'mac' => 'hmac-sha2-256,hmac-sha2-512,hmac-sha1'
			)
		);

		/**
		  * @var string
		  */
		const AUTH__SSH_AGENT = 'sshAgent';

		/**
		  * @var string
		  */
		const AUTH__CREDENTIALS = 'credentials';

		/**
		  * @var bool
		  */
		protected $_usePhpSshLib = true;

		/**
		  * @var string
		  */
		protected $_remoteHost = false;

		/**
		  * @var int
		  */
		protected $_remotePort = 22;

		/**
		  * @var string
		  */
		protected $_bastionHost = false;

		/**
		  * @var int
		  */
		protected $_bastionPort = 22;

		/**
		  * @var bool
		  */
		protected $_portForwarding = false;

		/**
		  * @var array
		  */
		protected $_options = array();

		/**
		  * @var string
		  */
		protected $_authMethod = null;

		/**
		  * @var string
		  */
		protected $_username = null;

		/**
		  * @var string
		  */
		protected $_password = null;

		/**
		  * @var \PhpCliShell\Core\Ssh
		  */
		protected $_bastionSSH = null;

		/**
		  * @var \PhpCliShell\Core\Process
		  */
		protected $_sshProcess = null;

		/**
		  * @var resource
		  */
		protected $_sshSession = null;

		/**
		  * @var bool
		  */
		protected $_isAuthenticated = false;

		/**
		  * @var string
		  */
		protected $_remotePrompt = null;

		/**
		  * Debug mode
		  * @var bool
		  */
		protected $_debug = false;


		public function __construct($remoteHost, $remotePort = 22, $bastionHost = false, $bastionPort = 22, $portForwarding = false, $usePhpSshLib = true)
		{
			$this->remoteHost($remoteHost);
			$this->remotePort($remotePort);
			$this->bastionHost($bastionHost);
			$this->bastionPort($bastionPort);
			$this->portForwarding($portForwarding);

			$this->_usePhpSshLib = ($usePhpSshLib && function_exists('ssh2_connect'));
		}

		public function remoteHost($remoteHost)
		{
			if(C\Tools::is('string&&!empty', $remoteHost)) {
				$this->disconnect();
				$this->_remoteHost = $remoteHost;
			}

			return $this;
		}

		public function remotePort($remotePort)
		{
			if(C\Tools::is('int&&>0', $remotePort) && $remotePort <= 65535) {
				$this->disconnect();
				$this->_remotePort = $remotePort;
			}

			return $this;
		}

		public function bastionHost($bastionHost = false)
		{
			if(C\Tools::is('string&&!empty', $bastionHost) || $bastionHost === false) {
				$this->disconnect();
				$this->_bastionHost = $bastionHost;
			}

			return $this;
		}

		public function bastionPort($bastionPort)
		{
			if(C\Tools::is('int&&>0', $bastionPort) && $bastionPort <= 65535) {
				$this->disconnect();
				$this->_bastionPort = $bastionPort;
			}

			return $this;
		}

		public function portForwarding($portForwarding)
		{
			if(C\Tools::is('int&&>0', $portForwarding) && $portForwarding <= 65535) {
				$this->disconnect();
				$this->_portForwarding = $portForwarding;
			}

			return $this;
		}

		/**
		  * @param \PhpCliShell\Core\Ssh $bastionSSH
		  * @return $this
		  */
		public function bastionSSH(Ssh $bastionSSH)
		{
			$this->disconnect();
			$this->_bastionSSH = $bastionSSH;
			return $this;
		}

		/**
		  * @return false|\PhpCliShell\Core\Ssh Bastion SSH
		  */
		public function getBastionSSH()
		{
			return ($this->_bastionSSH !== null) ? ($this->_bastionSSH) : (false);
		}

		public function getHost()
		{
			return ($this->_bastionHost !== false) ? ($this->_bastionHost) : ($this->_remoteHost);
		}

		public function getPort()
		{
			return ($this->_bastionPort !== false) ? ($this->_bastionPort) : ($this->_remotePort);
		}

		public function getRemotePrompt()
		{
			return ($this->_remotePrompt !== null) ? ($this->_remotePrompt) : (false);
		}

		public function addOption($option)
		{
			$option = preg_replace('#^(-o +)#i', '', $option);

			if(C\Tools::is('string&&!empty', $option)) {
				$this->_options[] = $option;
			}

			return $this;
		}

		public function addOptions(array $options)
		{
			foreach($options as $option) {
				$this->addOption($option);
			}

			return $this;
		}

		public function setOptions(array $options = null)
		{
			$this->_options = array();
			$this->addOptions($options);
			return $this;
		}

		public function useSshAgent($username)
		{
			if(C\Tools::is('string&&!empty', $username)) {
				$this->_authMethod = self::AUTH__SSH_AGENT;
				$this->_username = $username;
				$this->_password = null;
			}

			return $this;
		}

		public function setCredentials($username, $password)
		{
			if(C\Tools::is('string&&!empty', $username) && C\Tools::is('string&&!empty', $password)) {
				$this->_authMethod = self::AUTH__CREDENTIALS;
				$this->_username = $username;
				$this->_password = $password;
			}

			return $this;
		}

		protected function _usePhpSshLib()
		{
			return ($this->_usePhpSshLib && !$this->isTunneling());
		}

		protected function _getOptions()
		{
			if(count($this->_options) > 0) {
				return '-o '.implode(' -o ', $this->_options);
			}
			else {
				return '';
			}
		}

		public function isReady()
		{
			return (
				$this->_authMethod !== null && $this->_remoteHost !== false &&
				($this->_bastionHost === false || $this->_portForwarding !== false) &&
				$this->_sshProcess === null && $this->_sshSession === null
			);
		}

		public function isRunning()
		{
			return ($this->_sshProcess !== null) ? ($this->_sshProcess->isRunning()) : (false);
		}

		public function isTunneling()
		{
			return ($this->_bastionHost !== false && $this->_portForwarding !== false);
		}

		public function isConnected()
		{
			return ($this->_sshProcess !== null || $this->_sshSession !== null);
		}

		public function isAuthenticated()
		{
			return $this->_isAuthenticated;
		}

		public function getProcessPid()
		{
			return ($this->isRunning()) ? ($this->_sshProcess->pid) : (false);
		}

		public function connect()
		{
			if($this->isReady())
			{
				if($this->_bastionSSH !== null)
				{
					if(!$this->_bastionSSH->isRunning) {
						throw new Exception("Bastion SSH session is not running", E_USER_ERROR);
					}
					elseif(!$this->_bastionSSH->isConnected) {
						throw new Exception("Bastion SSH session is not connected", E_USER_ERROR);
					}
					elseif(!$this->_bastionSSH->isAuthenticated) {
						throw new Exception("Bastion SSH session is not authenticated", E_USER_ERROR);
					}
				}

				if($this->_usePhpSshLib())
				{
					$callbacks = array(
						'debug' => array($this, '_cb_debug'),
						'ignore' => array($this, '_cb_ignore'),
						'disconnect' => array($this, '_cb_disconnect')
					);

					try {
						$result = ssh2_connect($this->_remoteHost, $this->_remotePort, self::METHODS, $callbacks);
					}
					catch(\Exception $e) {
						$result = false;
					}

					if($result !== false)
					{
						$this->_sshSession = $result;

						try {
							$authStatus = $this->_sshSessionAuth();
						}
						catch(\Exception $e) {
							$this->disconnect();
							$authStatus = false;
						}

						return $authStatus;
					}
					else {
						throw new Exception("Unable SSH to remote host '".$this->_remoteHost.":".$this->_remotePort."'", E_USER_ERROR);
					}
				}
				else {
					return $this->_startSshProcess();
				}
			}

			return false;
		}

		protected function _startSshProcess()
		{
			if(!$this->isRunning())
			{
				if($this->_authMethod === self::AUTH__CREDENTIALS)
				{
					$setEnvStatus = putenv("SSHPASS=".$this->_password);

					if(!$setEnvStatus) {
						throw new Exception("Unable to set environment variable for SSH password", E_USER_ERROR);
					}

					$cmdPrefix = "sshpass -e ";
					$cmdSuffix = "";
				}
				else {
					$cmdPrefix = "";
					$cmdSuffix = "";
				}

				/**
				  * -q : Ne pas afficher la bannière SSH "Quiet mode"
				  * https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=134589
				  *
				  *
				  * Error: Pseudo-terminal will not be allocated because stdin is not a terminal.
				  *
				  * -T : Disable pseudo-terminal allocation
				  * Avec PHP, le STDIN n'est pas un terminal, donc il faut désactiver le PTY lors de la session SSH
				  * Sans cette option (et donc avec -t -t) un PTY est créé et l'interraction entre STDIN et le PTY produit
				  * des résultats non souhaités qui apparaissent dans STDOUT comme par example un désordre dans des outputs
				  *
				  * -t -t : Force pseudo-terminal allocation
				  * https://stackoverflow.com/questions/48648572/how-to-deal-with-pseudo-terminal-will-not-be-allocated-because-stdin-is-not-a-t
				  *
				  * Utiliser -T et non -t -t afin de ne pas forcer l'allocation d'un PTY
				  */
				if(!$this->isTunneling()) {
					$command = $cmdPrefix."ssh -T -q ".$this->_getOptions()." -p ".$this->_remotePort." ".$this->_username."@".$this->_remoteHost.$cmdSuffix;
				}
				else {
					$tunnelCmd = "-L ".$this->_portForwarding.":".$this->_remoteHost.":".$this->_remotePort;
					$command = $cmdPrefix."ssh -T -q ".$this->_getOptions()." ".$tunnelCmd." -p ".$this->_bastionPort." ".$this->_username."@".$this->_bastionHost.$cmdSuffix;
				}

				$this->_sshProcess = new C\Process($command);
				$sshProcessStatus = $this->_sshProcess->start();

				if($sshProcessStatus)
				{
					$callback = Closure::fromCallable(array($this, '_sshProcessIsReady'));

					try {
						$sshProcessIsReady = $this->_sshProcess->waitingPipes($callback, 10);
					}
					catch(\Exception $exception)
					{
						if($this->_debug) {
							throw $e;
						}

						$sshProcessIsReady = false;
					}

					if($sshProcessIsReady)
					{
						/**
						  * Pour déterminer le prompt on envoie un EOL ce qui
						  * permet de garantir que la fin de STDOUT contient le prompt
						  */
						$this->_sshProcess->exec(null);

						/**
						  * On attend 2 secondes depuis le dernier output provenant du pipe STDOUT 
						  * avant de considérer la session comme établie et de récupérer le prompt
						  */
						$std = $this->_sshProcess->flushPipes(null, 2);
						$this->_registerPrompt($std[C\Process::STDOUT]);

						$this->_isAuthenticated = true;
						return true;
					}
					else
					{
						try {
							$isClosed = $this->_sshProcess->waitingClose(5);
						}
						catch(\Exception $e)
						{
							if($this->_debug) {
								throw $e;
							}

							$isClosed = false;
						}

						if($isClosed) {
							$stderr = $this->_sshProcess->stderr;
							$exitCode = $this->_sshProcess->exitCode;
							throw new Exception("SSH process returned exit code ".$exitCode." with message: ".$stderr, E_USER_ERROR);
						}
						elseif(isset($exception)) {
							throw $exception;
						}
					}
				}
			}
			else {
				return true;
			}

			return false;
		}

		protected function _sshProcessIsReady(C\Process $process, $stdout, $stderr)
		{
			if($stderr !== "") {
				throw new Exception("[SSH ERROR] ".$stderr, E_USER_ERROR);
			}
			else {
				return ($this->isRunning() && $stdout !== "");
			}
		}

		protected function _sshSessionAuth()
		{
			if($this->isConnected() && !$this->isAuthenticated() && $this->_usePhpSshLib())
			{
				switch($this->_authMethod)
				{
					case self::AUTH__CREDENTIALS:
					{
						if(function_exists('ssh2_auth_password')) {
							$result = ssh2_auth_password($this->_sshSession, $this->_username, $this->_password);
						}
						else {
							throw new Exception("SSH function 'ssh2_auth_password' is missing", E_USER_ERROR);
						}

						break;
					}
					case self::AUTH__SSH_AGENT:
					{
						if(function_exists('ssh2_auth_agent')) {
							$result = ssh2_auth_agent($this->_sshSession, $this->_username);
						}
						else {
							throw new Exception("SSH function 'ssh2_auth_agent' is missing", E_USER_ERROR);
						}

						break;
					}
					default: {
						throw new Exception("Authentication method '".$this->_authMethod."' is not supported", E_USER_ERROR);
					}
				}
			}

			if($result === true) {
				$this->_isAuthenticated = true;
			}

			return $result;
		}

		/**
		  * @param string $stdout
		  * @return void
		  */
		protected function _registerPrompt($stdout)
		{
			$lines = preg_split("#[\r\n]+#i", $stdout);
			$prompt = end($lines);

			if($prompt !== false) {
				$this->_remotePrompt = trim($prompt, ' ');
			}
			else {
				throw new Exception("Unable to retrieve SSH prompt for remote host '".$this->getHost()."'", E_USER_ERROR);
			}
		}

		protected function _stopSshProcess()
		{
			$status = $this->_sshProcess->stop();

			if($status) {
				$this->_sshProcess = null;
				$this->_isAuthenticated = false;
			}

			return $status;
		}

		/**
		  * @param string $command Command
		  * @return false|array Returns STDOUT and STDERR pipes
		  */
		public function command($command, $cleanStdout = false)
		{
			$stdout = $stderr = null;
			$status = $this->exec($command, $stdout, $stderr);

			if($status)
			{
				if($cleanStdout) {
					$stdout = $this->stdoutCleaner($command, $stdout);
				}

				return array(
					C\Process::STDOUT => $stdout,
					C\Process::STDERR => $stderr,
				);
			}
			else {
				return false;
			}
		}

		/**
		  * @param string $stdout STDOUT
		  * @param null|string $command Command
		  * @return false|array Returns STDOUT
		  */
		public function stdoutCleaner($stdout, $command = null)
		{
			/**
			  * On convertit tous les sauts
			  * Any unicode newline sequence
			  */
			$stdout = preg_replace("#\R#u", PHP_EOL, $stdout);

			/**
			  * La commande doit apparaitre tout au début de STDOUT
			  * Dans le cas contraire cela signifie
			  * - qu'une partie de l'output n'a pas été capturé depuis le pipe STDOUT
			  * - qu'avant d'exécuter la commande, la variable stdout n'a pas été réinitialisée
			  */
			if($command !== null) {
				$command = preg_quote($command, '#');
				$stdout = preg_replace('#^'.$command.'( )*'.PHP_EOL.'#', '', $stdout);
			}

			$remotePrompt = $this->getRemotePrompt();

			/**
			  * Le prompt peut apparaitre soit tout au début soit tout à la fin
			  * En mode non multiligne (option m) le ^ et $ match le début et fin de STDOUT
			  */
			if($remotePrompt !== false) {
				$remotePrompt = preg_quote($remotePrompt, '#');
				$stdout = preg_replace("#(^|".PHP_EOL.")".$remotePrompt.".*(".PHP_EOL."|$)#", '$1', $stdout);
			}

			/**
			  * Seul les EOL du début et de fin doivent être nettoyés
			  * Ceux éparpillés dans STDOUT doivent être concervés 
			  */
			$stdout = trim($stdout, PHP_EOL);
			return $stdout;
		}

		/**
		  * @param string $command Command
		  * @param string $stdout STDOUT
		  * @param string $stderr STDERR
		  * @return bool Status
		  */
		public function exec($command, &$stdout = '', &$stderr = '')
		{
			$stdout = '';
			$stderr = '';

			if($this->isConnected() && $this->isAuthenticated() && !$this->isTunneling() && C\Tools::is('string&&!empty', $command))
			{
				if($this->_usePhpSshLib())
				{
					$sshStream = ssh2_exec($this->_sshSession , $command);

					if($sshStream !== false)
					{
						$stdoutS = ssh2_fetch_stream($sshStream, SSH2_STREAM_STDIO);
						$stderrS = ssh2_fetch_stream($sshStream, SSH2_STREAM_STDERR);
						stream_set_blocking($stdoutS, true);
						stream_set_blocking($stderrS, true);
						$stdout = stream_get_contents($stdoutS);
						$stderr = stream_get_contents($stderrS);
						fclose($stdoutS);
						fclose($stderrS);
						return true;
					}
				}
				else
				{
					$status = $this->_sshProcess->exec($command);

					if($status) {
						$remotePromptRegex = '#'.preg_quote($this->_remotePrompt, '#').'( )*$#';
						$this->_sshProcess->waitingPipes(null, 10, false, $remotePromptRegex);
						$stdout = $this->_sshProcess->stdout;
						$stderr = $this->_sshProcess->stderr;
						return true;
					}
				}
			}

			return false;
		}

		public function putFile($localFile, $remoteFile, $recursively = false, $fileMode = 0644, Closure $waitingCallback = null, $waitingTimeRate = 2)
		{
			if($this->isConnected() && $this->isAuthenticated() && !$this->isTunneling() &&
				C\Tools::is('string&&!empty', $localFile) && C\Tools::is('string&&!empty', $remoteFile) && C\Tools::is('int&&>0', $fileMode))
			{
				if($this->_usePhpSshLib()) {
					return ssh2_scp_send($this->_sshSession, $localFile, $remoteFile, $fileMode);
				}
				else {
					$recursively = ($recursively) ? ('-r') : ('');
					$command = "scp ".$this->_getOptions()." ".$recursively." -P ".$this->_remotePort." ".$localFile." ".$this->_username."@".$this->_remoteHost.":".$remoteFile;
					return $this->_scp($command, $waitingCallback, $waitingTimeRate);
				}
			}

			return false;
		}

		public function getFile($localFile, $remoteFile, $recursively = false, Closure $waitingCallback = null, $waitingTimeRate = 2)
		{
			if($this->isConnected() && $this->isAuthenticated() && !$this->isTunneling() &&
				C\Tools::is('string&&!empty', $localFile) && C\Tools::is('string&&!empty', $remoteFile))
			{
				if($this->_usePhpSshLib()) {
					return ssh2_scp_recv($this->_sshSession, $remoteFile, $localFile);
				}
				else {
					$recursively = ($recursively) ? ('-r') : ('');
					$command = "scp ".$this->_getOptions()." ".$recursively." -P ".$this->_remotePort." ".$this->_username."@".$this->_remoteHost.":".$remoteFile." ".$localFile;
					return $this->_scp($command, $waitingCallback, $waitingTimeRate);
				}
			}

			return false;
		}

		protected function _scp($command, Closure $waitingCallback = null, $waitingTimeRate = null)
		{
			$cmdPrefix = ($this->_authMethod === self::AUTH__CREDENTIALS) ? ("sshpass -e ") : ("");

			$scpProcess = new C\Process($cmdPrefix.$command);
			$scpProcessStatus = $scpProcess->start();

			if($scpProcessStatus)
			{
				if($waitingCallback !== null && C\Tools::is('int&&>0', $waitingTimeRate)) {
					$scpProcessIsClosed = $scpProcess->waitingCustomClose($waitingCallback, $waitingTimeRate);
				}
				else {
					$scpProcessIsClosed = $scpProcess->waitingClose();
				}

				$scpProcessIsStopped = $scpProcess->stop();

				if($scpProcess->exitCode === 0)
				{
					/**
					  * DEBUG: certains process peuvent retourner des informations en STDERR mais avec un code de sortie OK 0
					  * Par exemple, SCP "Connection to 127.0.0.1 closed by remote host" retourne un code de sortie OK 0
					  *
					  * CELA NE SIGNIFIE PAS QUE QUELQUE CHOSE S'EST MAL PASSÉ, ne prendre en compte que le code de sortie!
					  */
					/*if($scpProcess->stderr !== '') {
						throw new Exception("An error occurred while executing SCP: ".$scpProcess->stderr, E_USER_ERROR);
					}*/

					if($scpProcessIsClosed && $scpProcessIsStopped) {
						return true;
					}
				}
				elseif($scpProcess->stderr !== '') {
					throw new Exception("An error occurred while executing SCP: ".$scpProcess->stderr, E_USER_ERROR);
				}
				else {
					throw new Exception("Gets exit code '".$scpProcess->exitCode."' while executing SCP", E_USER_ERROR);
				}
			}

			return false;
		}

		public function disconnect()
		{
			if($this->isConnected())
			{
				if($this->_usePhpSshLib())
				{
					$status = ssh2_disconnect($this->_sshSession);

					if($status !== false) {
						$this->_sshSession = null;
						$this->_isAuthenticated = false;
					}
				}
				else {
					$status = $this->_stopSshProcess();
				}

				if($status === false) {
					$host = ($this->isTunneling()) ? ($this->_bastionHost) : ($this->_remoteHost);
					throw new Exception("Unable to disconnect to remote host '".$host."'", E_USER_ERROR);
				}
			}

			if($this->_bastionSSH !== null) {
				$this->_bastionSSH->disconnect();
			}

			return true;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'isRunning': {
					return $this->isRunning();
				}
				case 'isTunneling': {
					return $this->isTunneling();
				}
				case 'isConnected': {
					return $this->isConnected();
				}
				case 'isDisconnected': {
					return !$this->isConnected();
				}
				case 'isAuthenticated': {
					return $this->isAuthenticated();
				}
				case 'bastionSSH': {
					return $this->getBastionSSH();
				}
				case 'processPid': {
					return $this->getProcessPid();
				}
				case 'remotePrompt': {
					return $this->getRemotePrompt();
				}
				default: {
					throw new Exception("Attribute name '".$name."' does not exist", E_USER_ERROR);
				}
			}
		}

		public function __destruct()
		{
			try {
				$this->disconnect();
			}
			catch(\Exception $e) {
				var_dump($e->getMessage());
			}
		}

		protected function _cb_ignore($message)
		{
			throw new Exception("Server ignore with message: ".$message, E_USER_ERROR);
		}

		protected function _cb_debug($message, $language, $always_display)
		{
			throw new Exception("SSH debug: [".$message."] {".$language."} (".$always_display.")", E_USER_ERROR);
		}

		protected function _cb_disconnect($reason, $message, $language)
		{
			throw new Exception("Server disconnected with reason code [".$reason."] and message: ".$message, E_USER_ERROR);
		}
	}