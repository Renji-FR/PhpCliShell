<?php
	namespace PhpCliShell\Addon\Ipam\Common;

	use PhpCliShell\Core as C;
	use PhpCliShell\Core\Network;

	abstract class Tools extends Network\Tools
	{
		public static function filter(array $values, array $filters)
		{
			$result = array();

			$filters = array_map('mb_strtolower', $filters);
			$filters = array_unique($filters);

			$fields = array(
				'ip' => array('address', 'netMask', 'cidrMask'),
				'vlan' => array('vlanId', 'vlanName'),
				'subnet' => array('subnetId'),
				'physical' => array('portName'),
			);

			foreach($filters as $filter)
			{
				switch($filter)
				{
					case 'ip':
					case 'vlan':
					case 'subnet':
					case 'physical':
						$fValues = array_intersect_key($values, array_flip($fields[$filter]));
						$result = array_merge($result, $fValues);
						break;
				}
			}

			return $result;
		}
	}