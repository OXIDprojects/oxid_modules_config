# Changelog for Config Importer

## 6.1.0
### changed
* use boolean syntax for module settings in export

## 6.0.0 
### changed
* settings not listed in module's metadata will not be exported

## 5.2.0 
### changed
* moved to module internals fix command (instead of oxid console)
* additinal check see https://github.com/OXIDprojects/oxid_modules_config/pull/20

## 5.1.0 
### add
* added services yaml to register console command

## 5.0.0 
### update
* changed default configuration path because most projects do not want to have configuration not within the modules folder

## 4.0.17 
### update
* symfony/yaml v3

## 4.0.16 
### fixed
* fixed php warning

## 4.0.15 
### fixed
* theme config was not imported if there was a theme that was not used in the import 

## 4.0.14
### fixed
* fixed setting module version

## 4.0.13 
* performance
### changed
* theme config handling (do not include unnecessary config)

## 4.0.12
* performance
### fixed
* fix to be compatible with new oxid console
statefixer needs output object 

## 4.0.11
sorting version field

## 4.0.10
### fixed
* debug output is working again

## 4.0.9
### changed
* do not export aModuleExtensions anymore
* unique aDisabledModules

## 4.0.8
### fixed
use correct version number in metadata.php

## 4.0.7
### fixed
use the correct shop id during fix states during the import

## 4.0.6
### changed
remove version from composer.json (to be read from composer.lock)

## 4.0.5
### fixed
catch exception for module activation
and compare versions to only run updates for new module versions

## 4.0.4
### changed
Added a getter for default exclude fields, will skip the module controllers array.

## 4.0.3

## 4.0.2

* Updating module for OXID eShop V6
* Composer-ready
* New module structure

## 0.5.1

* Export handles theme settings
* Ignore environment specific fields on export
 
## 0.4

* Use types in exported variables
 
## 0.3

* ...

## v0.2.0

* Module renamed
* Documentation adjusted
* Changelog added
* Refactoring done to match OXPS standards
* Added JSON pretty print option support
* Adjusted dashboard styles
