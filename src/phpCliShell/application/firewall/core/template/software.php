<?php
	namespace PhpCliShell\Application\Firewall\Core\Template;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Exception as E;

	use PhpCliShell\Cli as Cli;

	use PhpCliShell\Application\Firewall\Core\Api;

	abstract class Software extends AbstractTemplate
	{
		/**
		  * Addresses
		  * @var array
		  */
		protected $_addresses = array();

		/**
		  * Applications
		  * @var array
		  */
		protected $_protocols = array();

		/**
		  * Access control lists
		  * @var array
		  */
		protected $_aclRules = array();

		/**
		  * Network address translations
		  * @var array
		  */
		protected $_natRules = array();


		/**
		  * @return array Variables for rendering template
		  */
		protected function _getTemplateVars()
		{
			return array(
				'addresses' => $this->_addresses,
				'protocols' => $this->_protocols,
				'aclRules' => $this->_aclRules,
				'natRules' => $this->_natRules,
			);
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Site[] $sites
		  * @param array $objects
		  * @return bool
		  */
		protected function _processing(array $sites, array $objects)
		{
			$ruleSection = self::API_TYPE_SECTION[Api\AclRule::API_TYPE];

			if(array_key_exists($ruleSection, $objects))
			{
				foreach($objects[$ruleSection] as $Api_Rule) {
					$this->_prepareToPolicyAcl($Api_Rule);
				}

				return true;
			}

			return false;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi
		  * @param string $zone
		  * @return array|\ArrayObject|false Address datas
		  */
		protected function _getObjectAdd(Api\Address $addressApi)
		{
			$addressName = $addressApi->name;

			if(array_key_exists($addressName, $this->_addresses)) {
				return $this->_addresses[$addressName];
			}
			else {
				return false;
			}
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi
		  * @return array|\ArrayObject Object address datas
		  */
		protected function _prepareToObjectAdd(Api\Address $addressApi)
		{
			return $this->_toObjectAdd($addressApi);
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi
		  * @return array|\ArrayObject Object address datas
		  */
		abstract protected function _toObjectAdd(Api\Address $addressApi);

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi
		  * @return array|\ArrayObject|false Protocol datas
		  */
		protected function _getProtocolApp(Api\Protocol $protocolApi)
		{
			$protocolName = $protocolApi->name;

			if(array_key_exists($protocolName, $this->_protocols)) {
				return $this->_protocols[$protocolName];
			}
			else {
				return false;
			}
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi
		  * @return array|\ArrayObject Protocol application datas
		  */
		protected function _prepareToProtocolApp(Api\Protocol $protocolApi)
		{
			return $this->_toProtocolApp($protocolApi);
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi
		  * @return array|\ArrayObject Protocol application datas
		  */
		abstract protected function _toProtocolApp(Api\Protocol $protocolApi);

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AclRule $ruleApi
		  * @return array|\ArrayObject|false ACL rule datas
		  */
		protected function _getPolicyAcl(Api\AclRule $ruleApi)
		{
			$ruleName = $ruleApi->name;

			if(array_key_exists($ruleName, $this->_aclRules)) {
				return $this->_aclRules[$ruleName];
			}
			else {
				return false;
			}
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AclRule $ruleApi
		  * @return array|\ArrayObject Policy accesslist datas
		  */
		protected function _prepareToPolicyAcl(Api\AclRule $ruleApi)
		{
			return $this->_toPolicyAcl($ruleApi);
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AclRule $ruleApi
		  * @return array|\ArrayObject Policy accesslist datas
		  */
		abstract protected function _toPolicyAcl(Api\AclRule $ruleApi);
	}