# DOCUMENTATION

#### SOURCES
__*https://www.phpdoc.org/*__

#### HOW TO
__*Afficher la documentation:*__
* cd ./development/DOC
* xdg-open index.html
* google-chrome index.html
* firefox index.html
* ...

__*Générer une nouvelle documentation*__
* php -i | grep intl
* sudo apt install php7.3-intl
* php -i | grep intl
* sudo apt install graphviz
* cd ./development/DOC

__PHPDOC 2.9__
* cd phpdoc-2.9
* php phpDocumentor.phar --template clean

__PHPDOC 3.0__
* cd phpdoc-3.0
* php phpDocumentor.phar -d [toolCliRootDir] --template clean

/!\ PhpDoc 3.0 ne fonctionne pas avec:
* option -d ../../, il faut un chemin absolu
* les fichiers PHP non encodés en UTF-8 (erreur trim)  
  _debuguer petit à petit en isolant les répectoires avec -d_
* tous les templates XSL (Zend, new-black)  
  _Warning: XSLTProcessor::transformToUri(): No stylesheet associated to this object_

__*Liste des templates*__
* https://github.com/phpDocumentor/phpDocumentor2/tree/develop/data/templates
