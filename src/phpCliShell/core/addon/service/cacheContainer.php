<?php
	namespace PhpCliShell\Core\Addon\Service;

	abstract class CacheContainer extends Container
	{
		public function register($id, $object)
		{
			$this->_register($id, $object);
			return $this;
		}

		public function unregister($id)
		{
			$this->_unregister($id);
			return $this;
		}
	}