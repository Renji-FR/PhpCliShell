<?php
	namespace PhpCliShell\Application\Firewall\Core\Api;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Application\Firewall\Core\Exception;
	use PhpCliShell\Application\Firewall\Core\Api\NatRule\Terms;
	use PhpCliShell\Application\Firewall\Core\Api\NatRule\Rules;

	class NatRule extends AbstractRule
	{
		/**
		  * @var string
		  */
		const API_TYPE = 'natRule';

		/**
		  * @var string
		  */
		const API_INDEX = 'natRule';

		/**
		  * @var string
		  */
		const API_LABEL = 'NAT rule';

		/**
		  * @var string
		  */
		const FIELD_NAME = 'name';

		/**
		  * @var string
		  */
		const DIRECTION_ONEWAY = 'one-way';

		/**
		  * @var string
		  */
		const DIRECTION_TWOWAY = 'two-way';

		/**
		  * @var array
		  */
		protected $_datas = array(
			'_id_' => null,
			'name' => null,
			'state' => false,
			'direction' => null,
			'srcZone' => '',
			'dstZone' => '',
			'terms' => null,
			'rules' => null,
			'description' => '',
			'tags' => array(),
			'timestamp' => null,
		);


		/**
		  * @param string $id ID
		  * @param string $name Name
		  * @param string $direction Direction
		  * @param string $description Description
		  * @return $this
		  */
		public function __construct($id = null, $name = null, $direction = null, $description = null)
		{
			parent::__construct($id, $name);

			$this->direction($direction);
			$this->description($description);

			$this->_datas['terms'] = new Terms();
			$this->_datas['rules'] = new Rules();
		}

		/**
		  * @param string $direction Direction
		  * @return bool
		  */
		public function direction($direction = self::DIRECTION_ONEWAY)
		{
			if($direction === null) {
				$direction = self::DIRECTION_ONEWAY;
			}

			switch($direction)
			{
				case self::DIRECTION_ONEWAY:
				case self::DIRECTION_TWOWAY: {
					$this->_datas['direction'] = $direction;
					break;
				}
				default: {
					throw new Exception("NAT rule direction '".$direction."' is not valid", E_USER_ERROR);
				}
			}

			return true;
		}

		/**
		  * @param string $zone
		  * @return bool
		  */
		public function srcZone($zone)
		{
			if(C\Tools::is('string&&!empty', $zone)) {
				$this->_datas['srcZone'] = (string) $zone;
				return true;
			}

			return false;
		}

		/**
		  * @param string $zone
		  * @return bool
		  */
		public function dstZone($zone)
		{
			if(C\Tools::is('string&&!empty', $zone)) {
				$this->_datas['dstZone'] = (string) $zone;
				return true;
			}

			return false;
		}

		/**
		  * @param null|string $attribute
		  * @param null|string $type
		  * @param null|\PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @return bool
		  */
		public function reset($attribute = null, $type = null, AbstractApi $objectApi = null)
		{
			$status = parent::reset($attribute, $type, $objectApi);

			if(!$status)
			{
				switch(true)
				{
					case ($attribute === 'terms'): {
						$this->terms->reset();
						break;
					}
					case ($attribute === 'rules'): {
						$this->rules->reset();
						break;
					}
					case ($attribute === null):
					case ($attribute === true): {
						$this->terms->reset();
						$this->rules->reset();
						break;
					}
					default: {
						return parent::reset($attribute, $type, $objectApi);
					}
				}
			}

			return true;
		}

		/**
		  * @return $this
		  */
		public function refresh()
		{
			$this->terms->refresh();
			$this->rules->refresh();
			return parent::refresh();
		}

		/**
		  * @param bool $returnInvalidAttributes
		  * @return bool
		  */
		public function isValid($returnInvalidAttributes = false)
		{
			$parentIsValid = parent::isValid($returnInvalidAttributes);

			$tests = array(
				array('direction' => 'string&&!empty'),
				array('srcZone' => 'string&&!empty'),
				array('dstZone' => 'string&&!empty'),
			);

			$selfIsValid = $this->_isValid($tests, $returnInvalidAttributes);

			$termsAreValid = $this->terms->isValid($returnInvalidAttributes);
			$rulesAreValid = $this->rules->isValid($returnInvalidAttributes);

			if($returnInvalidAttributes) {
				return array_merge($parentIsValid, $selfIsValid, $termsAreValid, $rulesAreValid);
			}
			else {
				return ($parentIsValid && $selfIsValid && $termsAreValid && $rulesAreValid);
			}
		}

		/**
		  * @param string $name
		  * @return bool
		  */
		public function partExists($name)
		{
			return ($name === 'terms' || $name === 'rules');
		}

		/**
		  * @param string $name
		  * @return false|\PhpCliShell\Application\Firewall\Core\Api\NatRule\AbstractPart
		  */
		public function getPart($name)
		{
			if($name === 'terms') {
				return $this->_datas['terms'];
			}
			elseif($name === 'rules') {
				return $this->_datas['rules'];
			}
			else {
				return false;
			}
		}

		/**
		  * @param mixed $name
		  * @return mixed
		  */
		public function __get($name)
		{
			switch($name)
			{
				case 'oneway': {
					return ($this->_datas['direction'] === self::DIRECTION_ONEWAY);
				}
				case 'twoway': {
					return ($this->_datas['direction'] === self::DIRECTION_TWOWAY);
				}
				case 'terms': {
					return $this->_datas['terms'];
				}
				case 'rules': {
					return $this->_datas['rules'];
				}
				default: {
					return parent::__get($name);
				}
			}
		}

		/**
		  * @return array
		  */
		public function sleep()
		{
			$datas = parent::sleep();

			$datas['terms'] = $this->terms->sleep();
			$datas['rules'] = $this->rules->sleep();

			return $datas;
		}

		/**
		  * @param $datas array
		  * @return bool
		  */
		public function wakeup(array $datas)
		{
			parent::wakeup($datas);

			// /!\ Permets de s'assurer que les traitements spéciaux sont bien appliqués
			$this->direction($datas['direction']);
			$this->srcZone($datas['srcZone']);
			$this->dstZone($datas['dstZone']);

			$this->terms->wakeup($datas['terms']);
			$this->rules->wakeup($datas['rules']);
			
			return true;
		}
	}