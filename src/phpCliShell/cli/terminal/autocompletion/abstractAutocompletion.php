<?php
	namespace PhpCliShell\Cli\Terminal\Autocompletion;

	use PhpCliShell\Core as C;

	abstract class AbstractAutocompletion
	{
		protected function _crossSubStr(array $items, $default = null, $caseSensitive = true)
		{
			return C\Tools::crossSubStr($items, $default, $caseSensitive);
		}
	}