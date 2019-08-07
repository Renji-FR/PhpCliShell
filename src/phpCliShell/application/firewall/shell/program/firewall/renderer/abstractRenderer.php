<?php
	namespace PhpCliShell\Application\Firewall\Shell\Program\Firewall\Renderer;

	use PhpCliShell\Core as C;

	use PhpCliShell\Application\Firewall\Core\Api;
	use PhpCliShell\Application\Firewall\Shell\Exception;

	abstract class AbstractRenderer
	{
		const VIEW_EXTENSIVE = 'extensive';
		const VIEW_SUMMARY = 'summary';
		const VIEW_DETAILS = 'summary';
		const VIEW_BRIEF = 'brief';
		const VIEW_TERSE = 'brief';

		const FORMAT_OBJECT = 'object';
		const FORMAT_ARRAY = 'array';
		const FORMAT_TABLE = 'table';


		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param array $listFields
		  * @param string $layout
		  * @param string $format
		  * @return mixed
		  */
		public function render(Api\AbstractApi $objectApi, array $listFields, $layout, $format)
		{
			return $this->_format($objectApi, $listFields, $layout, $format);
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param array $listFields
		  * @param string $layout
		  * @return mixed
		  */
		public function formatToObject(Api\AbstractApi $objectApi, array $listFields, $layout)
		{
			return $this->_format($objectApi, $listFields, $layout, static::FORMAT_OBJECT);
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param array $listFields
		  * @param string $layout
		  * @return mixed
		  */
		public function formatToArray(Api\AbstractApi $objectApi, array $listFields, $layout)
		{
			return $this->_format($objectApi, $listFields, $layout, static::FORMAT_ARRAY);
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param array $listFields
		  * @param string $layout
		  * @return mixed
		  */
		public function formatToTable(Api\AbstractApi $objectApi, array $listFields, $layout)
		{
			return $this->_format($objectApi, $listFields, $layout, static::FORMAT_TABLE);
		}

		/**
		  * @param \PhpCliShell\Application\Firewall\Core\Api\AbstractApi $objectApi
		  * @param array $listFields
		  * @param string $layout
		  * @param string $format
		  * @return mixed
		  */
		protected function _format(Api\AbstractApi $objectApi, array $listFields, $layout, $format)
		{
			switch($format)
			{
				case static::FORMAT_OBJECT: {
					return $objectApi->toObject();
				}
				case static::FORMAT_ARRAY: {
					return $objectApi->toArray();
				}
				case static::FORMAT_TABLE: {
					$item = $objectApi->toArray();
					return C\Tools::formatShellTable(array($item));
				}
				default: {
					throw new Exception("Format '".$format."' is not valid", E_USER_ERROR);
				}
			}
		}
	}