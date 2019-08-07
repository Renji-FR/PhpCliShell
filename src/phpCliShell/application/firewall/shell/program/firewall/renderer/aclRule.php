<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer;

	use PhpCliShell\Core as C;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;

	class AclRule extends AbstractRenderer
	{
		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param array $listFields
		  * @param string $layout
		  * @param string $format
		  * @return mixed
		  */
		protected function _format(Api\AbstractApi $objectApi, array $listFields, $layout, $format)
		{
			if($objectApi instanceof Api\AclRule)
			{
				switch($format)
				{
					case self::FORMAT_OBJECT:
					case self::FORMAT_TABLE: {
						$rule = $objectApi->toObject();
						break;
					}
					case self::FORMAT_ARRAY: {
						$rule = $objectApi->toArray();
						break;
					}
					default: {
						throw new Exception("Format '".$format."' is not valid", E_USER_ERROR);
					}
				}

				$rule['id'] = $rule['name'];
				$rule['date'] = date('Y-m-d H:i:s', $rule['timestamp']).' ('.$rule['timestamp'].')';
				$rule['fullmesh'] = ($rule['fullmesh']) ? ('yes') : ('no');
				$rule['state'] = ($rule['state']) ? ('enable') : ('disable');
				$rule['action'] = ($rule['action']) ? ('permit') : ('deny');

				if($layout !== self::VIEW_BRIEF)
				{
					foreach(array('sources', 'destinations') as $attribute)
					{
						foreach($rule[$attribute] as &$item) {
							$item = sprintf($listFields[$objectApi::API_TYPE][$attribute]['format'], $item->name, $item->attributeV4, $item->attributeV6);
						}
						unset($item);

						$rule[$attribute] = implode(PHP_EOL, $rule[$attribute]);
					}

					foreach($rule['protocols'] as &$item) {
						$item = sprintf($listFields[$objectApi::API_TYPE]['protocols']['format'], $item->name, $item->protocol);
					}
					unset($item);

					$rule['protocols'] = implode(PHP_EOL, $rule['protocols']);

					$acl = array(array($rule['sources'], $rule['destinations'], $rule['protocols']));
					$rule['acl'] = C\Tools::formatShellTable($acl);
				}

				if($layout === self::VIEW_EXTENSIVE)
				{
					foreach($rule['tags'] as &$item) {
						$item = sprintf($listFields[$objectApi::API_TYPE]['tags']['format'], $item->name, $item->tag);
					}
					unset($item);

					$rule['tags'] = implode(PHP_EOL, $rule['tags']);
				}
				else
				{
					foreach($rule['tags'] as &$item) {
						$item = '#'.$item->tag;
					}
					unset($item);

					$rule['tags'] = implode(' ', $rule['tags']);
				}

				if($format === self::FORMAT_TABLE)
				{
					$table = array(
						$rule['name'],
						$rule['category'],
						'Fullmesh: '.$rule['fullmesh'],
						'Status: '.$rule['state'],
						'Action: '.$rule['action'],
						$rule['description'],
						$rule['tags']
					);

					return C\Tools::formatShellTable(array($table), false, false, '/');
				}
				else {
					return $rule;
				}
			}
			else {
				throw new Exception("Object API must be an instance of '".Api\AclRule::class."'", E_USER_ERROR);
			}
		}
	}