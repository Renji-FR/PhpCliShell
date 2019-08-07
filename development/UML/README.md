# UML

#### SOURCES
__*https://montealegreluis.com/phuml/*__
__*https://github.com/MontealegreLuis/phuml*__

#### HOW TO
__*Afficher les diagrammes*__
Les fichiers images sont stockés dans ./diagrams
* Core: core.png
* CLI: cli.png
* Addon DCIM: addon_dcim.png
* Addon IPAM: addon_ipam.png
* Application DCIM: application_dcim.png
* Application IPAM: application_ipam.png
* Application Firewall: application_firewall.png
* Application Metadata: application_metadata.png

__*Générer les diagrammes*__
* php phuml.phar phuml:diagram -a -i -o -p dot -e phuml -r ../../src/phpCliShell/core diagrams/core.png
* php phuml.phar phuml:diagram -a -i -o -p dot -e phuml -r ../../src/phpCliShell/cli diagrams/cli.png
* php phuml.phar phuml:diagram -a -i -o -p dot -e phuml -r ../../src/phpCliShell/addons/dcim diagrams/addon_dcim.png
* php phuml.phar phuml:diagram -a -i -o -p dot -e phuml -r ../../src/phpCliShell/addons/ipam diagrams/addon_ipam.png 
* php phuml.phar phuml:diagram -a -i -o -p dot -e phuml -r ../../src/phpCliShell/applications/dcim diagrams/application_dcim.png
* php phuml.phar phuml:diagram -a -i -o -p dot -e phuml -r ../../src/phpCliShell/applications/ipam diagrams/application_ipam.png
* php phuml.phar phuml:diagram -a -i -o -p dot -e phuml -r ../../src/phpCliShell/applications/firewall diagrams/application_firewall.png
* php phuml.phar phuml:diagram -a -i -o -p dot -e phuml -r ../../src/phpCliShell/applications/metadata diagrams/application_metadata.png
