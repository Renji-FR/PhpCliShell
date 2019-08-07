<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	use PhpCliShell\Core as C;

	class Tag extends AbstractApi
	{
		/**
		  * @var string
		  */
		const API_TYPE = 'tag';

		/**
		  * @var string
		  */
		const API_INDEX = 'Firewall_Api_Tag';

		/**
		  * @var string
		  */
		const API_LABEL = 'tag';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var array
		  */
		const FIELD_ATTRS = array(
			'tag'
		);

		/**
		  * @var array
		  */
		protected $_datas = array(
			'_id_' => null,
			'name' => null,
			'tag' => null,
		);


		/**
		  * @param string $id ID
		  * @param string $name Name
		  * @param string $tag Tag
		  * @return $this
		  */
		public function __construct($id = null, $name = null, $tag = null)
		{
			$this->id($id);
			$this->name($name);
			$this->tag($tag);
		}

		/**
		  * Sets tag
		  *
		  * @param string $tag Tag
		  * @return bool
		  */
		public function tag($tag)
		{
			if(C\Tools::is('string&&!empty', $tag)) {
				$this->_datas['tag'] = $tag;
				return true;
			}

			return false;
		}

		public function isValid($returnInvalidAttributes = false)
		{		
			$tests = array(
				array(self::FIELD_NAME => 'string&&!empty'),
				array('tag' => 'string&&!empty'),
			);

			return $this->_isValid($tests, $returnInvalidAttributes);
		}

		/**
		  * @return array
		  */
		public function sleep()
		{
			$datas = parent::sleep();
			$datas['tag'] = $this->tag;

			return $datas;
		}

		/**
		  * @param $datas array
		  * @return bool
		  */
		public function wakeup(array $datas)
		{
			$parentStatus = parent::wakeup($datas);
			$tagStatus = $this->tag($datas['tag']);

			return ($parentStatus && $tagStatus);
		}
	}