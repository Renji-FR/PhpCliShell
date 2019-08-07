# PHAR

#### SOURCES
__*https://github.com/humbug/box*__

#### HOW TO
__*Configuration*__
[here](../../box.json)

__*Générer le PHAR*__
* Récupérer la clé PEM depuis le repository certificate
* La passphrase de la clé PEM est dans le keystore
* php box.phar validate "../../box.json"
* php box.phar compile -c "../../box.json"
* php box.phar verify "../../build/tools-cli.phar  "
[Optionnel, seulement si phar non chiffré]
* php box.phar info "../../build/tools-cli.phar"
* php box.phar info "../../build/tools-cli.phar" --list

#### FAQ
Erreur "Could not find a Composer executable."  
Il faut installer composer en copiant composer.phar vers PATH (/usr/local/bin/)

> You can place the Composer PHAR anywhere you wish.
> If you put it in a directory that is part of your PATH, you can access it globally.
> On Unix systems you can even make it executable and invoke it without directly using the php interpreter.


Erreur pour commande info "Could not read the file"  
Le phar est certainement chiffré et ne peut donc être lu
