<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	interface InterfaceRule
	{
		/**
		  * Sets state
		  *
		  * @param bool $state
		  * @return bool
		  */
		public function state($state = false);

		/**
		  * Adds description
		  *
		  * @param string $description
		  * @return bool
		  */
		public function description($description = '');

		/**
		  * Adds tag
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Tag $tagApi
		  * @return bool
		  */
		public function tag(Tag $tagApi);

		/**
		  * Configures this rule
		  *
		  * @param string $attrName
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @return bool
		  */
		public function configure($attrName, AbstractApi $objectApi);

		/**
		  * @param null|string $attribute
		  * @param null|string $type
		  * @param null|\PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @return bool
		  */
		public function reset($attribute = null, $type = null, AbstractApi $objectApi = null);

		/**
		  * @param int $timestamp
		  * @return bool
		  */
		public function timestamp($timestamp);

		/**
		  * @return $this
		  */
		public function touch();

		/**
		  * @return $this
		  */
		public function refresh();

		/**
		  * @param string $search
		  * @param bool $strict
		  * @return bool
		  */
		public function match($search, $strict = false);

		/**
		  * Checks the tag argument is present for this rule
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Tag $tagApi Tag object to test
		  * @return bool Tag is present for this rule
		  */
		public function tagIsPresent(Tag $tagApi);

		/**
		  * Checks the tag argument is used for this rule
		  *
		  * Do not test name because the test must be
		  * about Tag attributes and not the object
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Tag $tagApi Tag object to test
		  * @return bool Tag is used for this rule
		  */
		public function tagIsInUse(Tag $tagApi, $strict = true);

		/**
		  * Gets the tag argument is used for this rule
		  *
		  * Do not test name because the test must be
		  * about Tag attributes and not the object
		  *
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Tag $tagApi Tag object to test
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\Tag Tag is used for this rule
		  */
		public function getTagIsInUse(Tag $tagApi, $strict = true);

		/**
		  * @param bool $returnInvalidAttributes
		  * @return bool
		  */
		public function isValid($returnInvalidAttributes = false);

		/**
		  * @param mixed $name
		  * @return mixed
		  */
		public function __get($name);

		/**
		  * @return void
		  */
		public function __clone();

		/**
		  * @return array
		  */
		public function sleep();

		/**
		  * @param $datas array
		  * @return bool
		  */
		public function wakeup(array $datas);
	}