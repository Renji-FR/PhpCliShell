<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer;

	use PhpCliShell\Core as C;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;

	class Site extends AbstractRenderer
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
			if($objectApi instanceof Api\Site)
			{
				switch($format)
				{
					case self::FORMAT_OBJECT:
					case self::FORMAT_TABLE: {
						$site = $objectApi->toObject();
						break;
					}
					case self::FORMAT_ARRAY: {
						$site = $objectApi->toArray();
						break;
					}
					default: {
						throw new Exception("Format '".$format."' is not valid", E_USER_ERROR);
					}
				}

				if($layout === self::VIEW_EXTENSIVE)
				{
					// @todo tester sans $objectApi->toObject() --> iterator reference error, a debuguer
					foreach($site['zones'] as $key => &$zones)
					{
						foreach($zones as $IPv => &$item) {
							$item = sprintf($listFields['site']['zones']['format'], $key, $IPv, implode(', ', $item));
						}
						unset($item);

						$zones = implode(PHP_EOL, $zones);
					}
					unset($zones);

					$site['zones'] = implode(PHP_EOL, $site['zones']);
				}
				else {
					unset($site['zones']);
				}

				if($format === self::FORMAT_TABLE)
				{
					// @todo a coder
					//return C\Tools::formatShellTable(array($table), false, false, '/');
					return $site;
				}
				else {
					return $site;
				}
			}
			else {
				throw new Exception("Object API must be an instance of '".Api\Site::class."'", E_USER_ERROR);
			}
		}
	}