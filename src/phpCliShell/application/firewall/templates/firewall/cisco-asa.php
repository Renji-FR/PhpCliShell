<?php
/**
  * /!\ La balise de fermeture de PHP '?>' supprime le saut de ligne qui la suit immédiatement
  * Un double saut de ligne est le workaround le plus simple à mettre en place
  */

	namespace PhpCliShell\Application\Firewall\Core\Template\Cisco;

	use PhpCliShell\Application\Firewall\Core\Api;
?>
<?php
	foreach($this->addressBook as $addresses)
	{
		// IPv4 & IPv6
		foreach($addresses as $address)
		{
			if(array_key_exists('__doNotCreateAdd__', $address) && $address['__doNotCreateAdd__']) {
				continue;
			}

			switch($address['type'])
			{
				case Api\Host::API_TYPE: {
					$type = 'host';
					break;
				}
				case Api\Subnet::API_TYPE: {
					$type = 'subnet';
					$address['address'] = implode(' ', $address['address']);
					break;
				}
				case Api\Network::API_TYPE: {
					$type = 'range';
					$address['address'] = implode(' ', $address['address']);
					break;
				}
				default: {
					throw new Exception("Address type '".$address['type']."' is not valid", E_USER_ERROR);
				}
			}
?>

object network <?php echo $address['name']; ?>

<?php echo $type; ?> <?php echo $address['address']; ?>

exit
<?php
		}
	}
?>

<?php
	$interfaces = array();

	foreach($this->accessLists as $accessList)
	{
		if(!in_array($accessList['interface'], $interfaces, true))
		{
			$interfaces[] = $accessList['interface'];
?>

clear configure access-list ACL_NEW_<?php echo $accessList['interface']; ?>_access_in
<?php
		}

		$action = ($accessList['action']) ? ('permit') : ('deny');

		foreach($accessList['srcAdds'] as $srcAdd)
		{
			foreach($accessList['dstAdds'] as $dstAdd)
			{
				if($srcAdd['IPv'] !== $dstAdd['IPv']) {
					continue;
				}

				foreach($accessList['protoApps'] as $protoApp)
				{
					$acl = array();

					$acl[] = 'extended';
					$acl[] = $action;

					switch($protoApp['protocol'])
					{
						case 'icmp': {
							$acl[] = ($srcAdd['IPv'] === 4) ? ('icmp') : ('icmp6');
							break;
						}
						case 'icmp4':
						{
							if($srcAdd['IPv'] === 4) {
								$acl[] = 'icmp';
							}
							else {
								continue(2);
							}

							break;
						}
						case 'icmp6':
						{
							if($srcAdd['IPv'] === 6) {
								$acl[] = 'icmp6';
							}
							else {
								continue(2);
							}

							break;
						}
						default: {
							$acl[] = $protoApp['protocol'];
						}
					}

					foreach(array($srcAdd, $dstAdd) as $address)
					{
						switch($address['name'])
						{
							case 'any':
							case 'any4':
							case 'any6': {
								$acl[] = $address['name'];
								break;
							}
							default: {
								$acl[] = 'object';
								$acl[] = $address['name'];
							}
						}
					}
					
					if(array_key_exists('options', $protoApp))
					{
						switch($protoApp['protocol'])
						{
							case 'tcp':
							case 'udp': {
								$acl[] = (count($protoApp['options']) > 1) ? ('range') : ('eq');
								$acl[] = implode(' ', $protoApp['options']);
								break;
							}
							case 'icmp':
							case 'icmp4':
							case 'icmp6':
							{
								if(array_key_exists('type', $protoApp['options']))
								{
									$acl[] = $protoApp['options']['type'];

									if(array_key_exists('code', $protoApp['options'])) {
										$acl[] = $protoApp['options']['code'];
									}
								}
							}
						}
					}

					if(!$accessList['state']) {
						$acl[] = 'inactive';
					}

					if($accessList['description'] !== '')
					{
?>
access-list ACL_NEW_<?php echo $accessList['interface']; ?>_access_in remark <?php echo $accessList['description']; ?>  
<?php
					}
?>
access-list ACL_NEW_<?php echo $accessList['interface']; ?>_access_in <?php echo implode(' ', $acl); ?>  
<?php
				}
			}
		}

		echo PHP_EOL;
	}

	echo PHP_EOL.PHP_EOL;

	foreach($interfaces as $interface)
	{
		if($this->globalZone === $interface)
		{
?>

access-group ACL_NEW_<?php echo $interface; ?>_access_in global
<?php
		}
		else
		{
?>

access-group ACL_NEW_<?php echo $interface; ?>_access_in in interface <?php echo $interface; ?>  
<?php
		}
?>

clear configure access-list ACL_OLD_<?php echo $interface; ?>  
access-list <?php echo $interface; ?>_access_in rename ACL_OLD_<?php echo $interface; ?>  
access-list ACL_NEW_<?php echo $interface; ?>_access_in rename <?php echo $interface; ?>_access_in

<?php
	}
?>