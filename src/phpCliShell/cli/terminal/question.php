<?php
	namespace PhpCliShell\Cli\Terminal;

	use Closure;

	use PhpCliShell\Core as C;

	class Question extends Main
	{
		/**
		  * @var \PhpCliShell\Cli\Terminal\Console
		  */
		protected $_console = null;

		/**
		  * @var string
		  */
		protected $_hideAnswer = null;


		public function __construct()
		{
			$this->_console = new Console();
		}

		public function readLine($prompt)
		{
			if(!function_exists('readline_callback_handler_install')) {
				throw new Exception("Readline is not available, please compile your PHP with this option", E_USER_ERROR);
			}

			$exit = false;
			$answer = null;

			/**
			  * readline_callback_handler_install ne permet pas d'afficher un prompt formaté
			  * workaround, faire un echo et passer '' à readline_callback_handler_install
			  * Souci avec un backspace, le prompt est effacé, workaround PHP_EOL
			  */
			echo $prompt.PHP_EOL;

			/**
			  * readline_callback_handler_install n'exécutera la fonction
			  * que lorsque l'utilisateur appuyera sur la touche entrée
			  */
			readline_callback_handler_install('', function($input) use (&$answer, &$exit) {
				readline_callback_handler_remove();
				$answer = $input;
				$exit = true;
			});

			while(!$exit)
			{
				$r = array(STDIN);
				$n = $this->_streamSelect($r);

				if($n && in_array(STDIN, $r)) {
					readline_callback_read_char();
				}
			}

			return $answer;
		}

		public function question($prompt)
		{
			try {
				$this->_ask($prompt);
				return $this->_answer();
			}
			catch(\Exception $e) {
				$this->_quit();
				throw $e;
			}
		}

		public function textQuestion($prompt, $exitRegex = null)
		{
			if(!C\Tools::is('string&&!empty', $exitRegex)) {
				throw new Exception("Text question exit regex is required", E_USER_ERROR);
			}

			try {
				$this->_askText($prompt);
				return $this->_answerText($exitRegex);
			}
			catch(\Exception $e) {
				$this->_quit();
				throw $e;
			}
		}

		public function confirmQuestion($prompt, $trueAnswer = 'yes', $falseAnswer = 'no', $defaultAnswer = 'no')
		{
			// @todo a coder
			/**
			  * ajouter [yes|NO]
			  * default --> majuscule
			  * si default !== $trueAnswer & $falseAnswer --> Exception
			  * si pas d'input default --> bool
			  * si input ne permet pas de savoir yes/no alors ne rien faire --> bloquer
			  */
		}

		public function assistQuestion($prompt, Closure $helper)
		{
			try {
				$this->_askHelper($prompt);
				return $this->_answerHelper($helper);
			}
			catch(\Exception $e) {
				$this->_quit();
				throw $e;
			}
		}

		public function password($prompt, $hideAnswer = '*')
		{
			if(C\Tools::is('string&&!empty', $hideAnswer)) {
				$this->_hideAnswer = substr($hideAnswer, 0, 1);
			}

			try {
				$this->_ask($prompt);
				$answer = $this->_answer();
			}
			catch(\Exception $e) {
				$this->_hideAnswer = null;
				$this->_quit();
				throw $e;
			}

			$this->_hideAnswer = null;
			return $answer;
		}

		protected function _ask($prompt)
		{
			$this->setShellPrompt($prompt);
			$this->_open()->_prepare();
			return $this;
		}

		protected function _askText($prompt)
		{
			$this->_ask($prompt);
			echo PHP_EOL;
			return $this;
		}

		protected function _askHelper($prompt)
		{
			$this->_ask($prompt);
			return $this;
		}

		protected function _getStdin()
		{
			$pipes = $this->_pipes;	// copy
			$n = $this->_streamSelect($pipes);

			if($n !== false)
			{
				if($n > 0 && in_array(STDIN, $pipes)) {
					return stream_get_contents(STDIN, -1);
				}
				else {
					return null;	// Do nothing
				}
			}
			else {
				return false;		// Error occured
			}
		}

		protected function _answer()
		{
			$exit = false;
			$answer = '';

			while(!$exit)
			{
				$input = $this->_getStdin();

				if($input === false) {
					$this->_cleaner()->_quit();
					return false;
				}
				elseif($input !== null)
				{
					$inputs = explode(PHP_EOL, $input, 2);
					$counterEOL = (count($inputs)-1);
					unset($inputs[1]);

					foreach($inputs as $index => $input)
					{
						$this->_exec($answer, $input);

						if($index < $counterEOL)
						{
							$exit = $this->_exec($answer, PHP_EOL);

							if($exit) {
								$this->_cleaner()->_quit();
							}
						}
					}
				}
			}

			return $answer;
		}

		protected function _answerText($exitRegex = '#\R#u')
		{
			$exit = false;
			$answer = '';
			$answers = array();

			while(!$exit)
			{
				$input = $this->_getStdin();

				if($input === false) {
					$this->_cleaner()->_quit();
					return false;
				}
				elseif($input !== null)
				{
					$inputs = explode(PHP_EOL, $input);
					$counterEOL = (count($inputs)-1);

					foreach($inputs as $index => $input)
					{
						$this->_execText($answer, $answers, $input, $exitRegex);

						if($index < $counterEOL)
						{
							$exit = $this->_execText($answer, $answers, PHP_EOL, $exitRegex);

							if($exit) {
								$this->_cleaner()->_quit();
							}
						}
					}
				}
			}

			return $answer;
		}

		protected function _answerHelper(Closure $helper)
		{
			$exit = false;
			$answer = '';

			while(!$exit)
			{
				$input = $this->_getStdin();

				if($input === false) {
					$this->_cleaner()->_quit();
					return false;
				}
				elseif($input !== null)
				{
					$inputs = explode(PHP_EOL, $input, 2);
					$counterEOL = (count($inputs)-1);
					unset($inputs[1]);

					foreach($inputs as $index => $input)
					{
						$this->_execHelper($answer, $input, $helper);

						if($index < $counterEOL)
						{
							$exit = $this->_exec($answer, PHP_EOL);

							if($exit) {
								$this->_cleaner()->_quit();
							}
						}
					}
				}
			}

			return $answer;
		}

		protected function _exec(&$answer, $input)
		{
			$isRunning = $this->_execCommon($answer, $input);

			if(!$isRunning)
			{
				switch($input)
				{
					case PHP_EOL: {
						echo PHP_EOL;
						return true;
					}
					default:
					{
						$input = preg_replace('<\s>i', ' ', $input);

						//https://www.regular-expressions.info/posixbrackets.html#class
						if(preg_match("<[[:cntrl:]]>i", $input)) {
							// do nothing
						}
						elseif(preg_match("<^([\S ]+)$>i", $input)) {
							$answer .= $input;
							$this->_position += mb_strlen($input);
							$this->_update($input, 'white', false, false);
						}
					}
				}
			}

			return false;
		}

		protected function _execText(&$answer, array &$answers, $input, $exitRegex)
		{
			switch($input)
			{
				case PHP_EOL:
				{
					$answers[] = $answer;
					
					if(!preg_match($exitRegex, $answer)) {
						$this->_position = 0;
						$answer = '';
						echo PHP_EOL;
						return false;
					}
					else {
						$answer = implode(PHP_EOL, $answers);
					}

					echo PHP_EOL;
					return true;
				}
				default: {
					return $this->_exec($answer, $input);
				}
			}

			return false;
		}

		protected function _execHelper(&$answer, $input, Closure $helper)
		{
			switch($input)
			{
				case "\t":
				case "\x9":		// tab
				{
					$StatusValue = $helper($answer);
					$options = $StatusValue->options;

					switch(count($options))
					{
						case 0: {
							return false;
						}
						case 1: {
							$options = array_keys($options);
							$answer = current($options);
							break;
						}
						default:
						{
							$optionsA = array_unique($options);
							$columns = $this->_console->getColumns();

							if($columns !== false) {
								$msg = C\Tools::cutShellTable($optionsA, $columns, false, false, false);
							}
							else {
								$msg = C\Tools::formatShellTable(array($optionsA), false, false, false, true);
							}

							$this->_print($msg, 'cyan');

							$optionsU = array_keys($options);
							$answer = C\Tools::crossSubStr($optionsU, $answer, false);
						}
					}

					$this->_position = mb_strlen($answer);
					$this->_printPrompt($answer);
					$this->_move($this->_position);
					break;
				}
				default: {
					return $this->_exec($answer, $input);
				}
			}

			return false;
		}

		protected function _execCommon(&$answer, $input)
		{
			$answerLen = mb_strlen($answer);

			switch($input)
			{
				/**
				  * Ordre des tests importants
				  * "\x7f" est accepté par [\S ]
				  */
				case "\033[C":	// RIGHT
				{
					if($this->_position < $answerLen) {
						$this->_position++;
						echo $input;
					}
					break;
				}
				case "\033[D":	// LEFT
				{
					if($this->_position > 0) {
						$this->_position--;
						echo $input;
					}
					break;
				}
				case "\033[H":	// home
				case "\033[1~":
				case "\x1":		// CTRL+a (MacOS)
				{
					if($this->_position > 0) {
						$this->_position = 0;
						$this->_move($this->_position);
					}
					break;
				}
				case "\033[F":	// end
				case "\033[4~":
				case "\x5":		// CTRL+e (MacOS)
				{
					if($this->_position < $answerLen) {
						$this->_position = $answerLen;
						$this->_move($this->_position);
					}
					break;
				}
				case "\x7f":	// backspace
				{
					if($this->_position > 0) {
						$this->_position--;			// /!\ Reculer avant de supprimer
						$answer = $this->_delete($answer);
					}
					break;
				}
				default: {
					return false;
				}
			}

			return true;
		}

		/**
		  * /!\ text ne doit pas comporter de formatage (couleur, style, ...)
		  */
		protected function _print($text = "", $textColor = 'white', $bgColor = false, $textStyle = false)
		{
			if($this->_hideAnswer !== null) {
				$text = str_repeat($this->_hideAnswer, mb_strlen($text));
			}

			C\Tools::e(PHP_EOL.$text, $textColor, $bgColor, $textStyle);
			return $this;
		}

		/**
		  * /!\ text ne doit pas comporter de formatage (couleur, style, ...)
		  */
		protected function _update($text = "", $textColor = 'white', $bgColor = false, $textStyle = false)
		{
			if($this->_hideAnswer !== null) {
				$text = str_repeat($this->_hideAnswer, mb_strlen($text));
			}

			C\Tools::e($text, $textColor, $bgColor, $textStyle);
			return $this;
		}

		protected function _printPrompt($text = "")
		{
			$prompt = $this->_getPrintedPrompt().' ';
			C\Tools::e(PHP_EOL.$prompt, 'white', false, false);
			$this->_update($text, 'white', false, false);			// /!\ Ne pas utiliser _insert à cause du PHP_EOL
			return $this;
		}

		protected function _updatePrompt($text = "")
		{
			$prompt = $this->_getPrintedPrompt().' ';
			C\Tools::e($prompt, 'white', false, false);
			$this->_update($text, 'white', false, false);
			return $this;
		}
	}