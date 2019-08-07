<?php
	namespace PhpCliShell\Core;

	class Xml
	{
		/**
		  * @var string
		  */
		protected $_xml = null;

		/**
		  * @var resource
		  */
		protected $_parser = null; 

		/**
		  * @var array
		  */
		protected $_datas = array();

		/**
		  * @var array
		  */
		protected $_datasPtr = array();


		public function __construct($xml)
		{ 
			$this->_xml  = $xml; 
			$this->_parse(); 
		} 

		protected function _parse() 
		{ 
			$data = ''; 
			$this->_parser = xml_parser_create();
			xml_set_object($this->_parser, $this);
			xml_set_element_handler($this->_parser, 'startXML', 'endXML');
			xml_set_character_data_handler($this->_parser, 'charXML');

			xml_parser_set_option($this->_parser, XML_OPTION_SKIP_WHITE, 1);
			xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 0);

			$lines = explode("\n", $this->_xml);
			$lineCounter = count($lines);

			foreach($lines as $index => $val)
			{ 
				if(trim($val) === '') {
					continue;
				}

				if($index === $lineCounter-1) {
					$data = $val;
					$isFinal = true;
				}
				else {
					$data = $val."\n";
					$isFinal = false;
				}

				if(!xml_parse($this->_parser, $data, $isFinal))
				{
					$msg = sprintf('XML error at line %d column %d', 
							xml_get_current_line_number($this->_parser), 
							xml_get_current_column_number($this->_parser)
					);

					throw new Exception($msg, E_USER_ERROR);
				} 
			}
		}

		public function startXML($parser, $name, $attr)
		{
			$datasPtr = &$this->_getPtr();

			if(array_key_exists($name, $datasPtr))
			{
				/*if(array_key_exists('__attrs__', $datasPtr[$name])) {
					$datas = $datasPtr[$name];
					$datasPtr[$name] = array();
					$datasPtr[$name][] = $datas;
				}*/

				$datasPtr[$name][] = array('__attrs__' => $attr);

				$lastIndex = (count($datasPtr[$name])-1);
				$this->_setPtr($datasPtr[$name][$lastIndex]);
			}
			else {
				$datasPtr[$name] = array();
				$datasPtr[$name][0] = array('__attrs__' => $attr);
				$this->_setPtr($datasPtr[$name][0]);
			}
		}

		protected function &_getPtr()
		{
			if(count($this->_datasPtr) === 0) {
				return $this->_datas;
			}
			else {
				/* NE FONCTIONNE PAS
				$temp = end($this->_datasPtr);
				return $temp;*/
				$lastIndex = count($this->_datasPtr)-1;
				return $this->_datasPtr[$lastIndex];
			}
		}

		protected function _setPtr(&$datas)
		{
			$this->_datasPtr[] = &$datas;
		}

		public function endXML($parser, $name)
		{
			array_pop($this->_datasPtr);
		}

		public function charXML($parser, $data)
		{ 
			if(($data = trim($data)) !== '') {
				$datasPtr = &$this->_getPtr();
				$datasPtr['value'] = $data;
			}
		}

		/**
		  * @return array
		  */
		public function toArray()
		{
			return $this->_datas;
		}
	}