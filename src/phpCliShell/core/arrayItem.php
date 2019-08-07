<?php
	namespace PhpCliShell\Core;

	class ArrayItem extends ArrayObject
	{
		/**
		  * @var string
		  */
		protected $_name = null;

		/**
		  * @var \PhpCliShell\Core\ArrayObject[]
		  */
		protected $_arrayObject = null;


		/**
		  * @param string $name
		  * @param \PhpCliShell\Core\ArrayObject $objects
		  * @return $this
		  */
		public function __construct($name, ArrayObject $objects)
		{
			$this->_name = $name;

			/**
			  * Permet de garder l'original intact, sert aux classes filles
			  * Les modifications, suppressions, ne doivent pas être propagées
			  */
			$this->_arrayObject = $objects;

			parent::__construct($objects->toArray());
		}

		/**
		  * @return string Name
		  */
		public function getName()
		{
			return $this->_name;
		}

		/**
		  * @return string Name
		  */
		public function __toString()
		{
			$this->getName();
		}
	}