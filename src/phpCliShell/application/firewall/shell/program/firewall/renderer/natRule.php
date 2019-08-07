<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer;

	use PhpCliShell\Core as C;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;

	class NatRule extends AbstractRenderer
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
			if($objectApi instanceof Api\NatRule)
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
				$rule['state'] = ($rule['state']) ? ('enable') : ('disable');

				if($layout !== self::VIEW_BRIEF)
				{
					foreach(array('terms', 'rules') as $part)
					{
						$rulePart = $rule[$part];

						foreach(array('sources', 'destinations') as $attribute)
						{
							foreach($rulePart[$attribute] as &$item) {
								$item = sprintf($listFields[$objectApi::API_TYPE][$attribute]['format'], $item->name, $item->attributeV4, $item->attributeV6);
							}
							unset($item);

							$rulePart[$attribute] = implode(PHP_EOL, $rulePart[$attribute]);
						}

						foreach($rulePart['protocols'] as &$item) {
							$item = sprintf($listFields[$objectApi::API_TYPE]['protocols']['format'], $item->name, $item->protocol);
						}
						unset($item);

						$rulePart['protocols'] = implode(PHP_EOL, $rulePart['protocols']);

						$transport = array(array($rulePart['sources'], $rulePart['destinations'], $rulePart['protocols']));
						$rule[$part] = C\Tools::formatShellTable($transport);
					}
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
						$rule['direction'],
						$rule['srcZone'],
						$rule['dstZone'],
						'Status: '.$rule['state'],
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
				throw new Exception("Object API must be an instance of '".Api\NatRule::class."'", E_USER_ERROR);
			}
		}
	}