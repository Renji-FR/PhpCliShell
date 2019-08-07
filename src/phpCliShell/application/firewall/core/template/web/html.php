<?php
	namespace PhpCliShell\Application\Firewall\Core\Template\Web;

	use ArrayObject;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Core\Template\Software;

	class Html extends Software
	{
		const VENDOR = 'web';
		const PLATFORM = 'html';
		const TEMPLATE = null;

		const TEMPLATE_EXT = 'html';


		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Address $addressApi
		  * @return array|\ArrayObject Object address datas
		  */
		protected function _toObjectAdd(Api\Address $addressApi)
		{
			$objectAdd = $this->_getObjectAdd($addressApi);

			if($objectAdd !== false) {
				return $objectAdd;
			}

			$addressName = $addressApi->name;
			$address = $addressApi->toObject();

			$this->_addresses[$addressName] = $address;
			return $address;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\Protocol $protocolApi
		  * @return array|\ArrayObject Protocol application datas
		  */
		protected function _toProtocolApp(Api\Protocol $protocolApi)
		{
			$protocolApp = $this->_getProtocolApp($protocolApi);

			if($protocolApp !== false) {
				return $protocolApp;
			}

			$protocolName = $protocolApi->name;
			$protocol = $protocolApi->toObject();

			$this->_protocols[$protocolName] = $protocol;
			return $protocol;
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AclRule $ruleApi
		  * @return array|\ArrayObject Policy accesslist datas
		  */
		protected function _toPolicyAcl(Api\AclRule $ruleApi)
		{
			$policyAcl = $this->_getPolicyAcl($ruleApi);

			if($policyAcl !== false) {
				return $policyAcl;
			}

			$ruleName = $ruleApi->name;
			$rule = $ruleApi->toObject();

			$rule['fullmesh'] = ($rule['fullmesh']) ? ('yes') : ('no');
			$rule['state'] = ($rule['state']) ? ('enabled') : ('disabled');
			$rule['action'] = ($rule['action']) ? ('permit') : ('deny');

			foreach(array('sources', 'destinations') as $attributes)
			{
				foreach($rule[$attributes] as &$Api_Address)
				{
					$address = array(
						'name' => $Api_Address->name,
						'attributeV4' => $Api_Address->attributeV4,
						'attributeV6' => $Api_Address->attributeV6,
					);

					$Api_Address = $address;
				}
				unset($Api_Address);
			}

			foreach($rule['protocols'] as &$Api_Protocol) {
				$Api_Protocol = $Api_Protocol->protocol;
			}
			unset($Api_Protocol);

			foreach($rule['tags'] as &$Api_Tag) {
				$Api_Tag = $Api_Tag->tag;
			}
			unset($Api_Tag);

			$rule['date'] = date('Y-m-d H:i:s', $rule['timestamp']);

			$this->_aclRules[$ruleName] = $rule;
			return $rule;
		}
	}