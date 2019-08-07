PhpCliShell for PatchManager
-------------------

This application PatchManager use PatchManager addon and permit to browse DCIM objects.<br />
It is possible to show location, cabinet, equipment and cable. You can do some research.


REQUIREMENTS
-------------------

#### PatchManager
* Import profiles which are in src/phpCliShell/addon/dcim/patchmanager/ressources
    * formats: ressources/formats
	* Reports: ressources/reports
	* Searches: ressources/searches  

:bangbang: __*Do not rename custom profiles*__  
:bangbang: __*Version 2.0 add new profiles!*__


INSTALLATION
-------------------

#### PHP
Ubuntu only, you can get last PHP version from this PPA:<br />
__*https://launchpad.net/~ondrej/+archive/ubuntu/php*__
* add-apt-repository ppa:ondrej/php
* apt update

You have to install a PHP version >= 7.1:
* apt install php7.3-cli php7.3-mbstring php7.3-readline pphp7.3-soap php7.3-curl

For MacOS users which use PHP 7.3, there is an issue with PCRE.
You have to add this configuration in your php.ini:
```ini
pcre.jit=0
```
> To locate your php.ini, use this command: php -i | grep "Configuration File" *

#### PHAR
Download last PHAR release and its public key from [releases](https://github.com/Renji-FR/PhpCliShell/releases)<br />
> Be careful to keep public key filename same as PHAR filename with ".phar" extension

Print console help: `$ php phpCliShell.phar --help`
> The PHAR contains all PhpCliShell applications and addons

#### WIZARD

![wizard](documentation/readme/wizard.gif)

Create PatchManager application configuration with command:<br />
`$ php phpCliShell.phar configuration:addon:factory dcim`<br />

Create PatchManager application launcher with command:<br />
`$ php phpCliShell.phar launcher:application:factory dcim`


EXECUTION
-------------------

#### CREDENTIALS FILE
/!\ For security reason, you can use a read only account!  
__*Change informations which are between []*__
* vim credentialsFile
    * read -sr USER_PASSWORD_INPUT
    * export DCIM_[DCIM_SERVER_KEY]_LOGIN=[YourLoginHere]
    * export DCIM_[DCIM_SERVER_KEY]_PASSWORD=$USER_PASSWORD_INPUT  
      __Change [DCIM_SERVER_KEY] with the key of your PatchManager server in configuration file__

#### SHELL
Launch PhpCliShell for PatchManager service
* source credentialsFile
* php patchmanager.[key].php

#### COMMAND
Get command result in order to handle with your OS shell.  
/!\ The result is JSON so you can use JQ https://stedolan.github.io/jq/  
__*Change informations which are between []*__
* source credentialsFile
* php patchmanager.[key].php "[myCommandHere]"