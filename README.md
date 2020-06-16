# OXID Core and Module Configurations Import and Export module

Tools to export, backup and import OXID eShop modules, module settings, shop settings and module activation.
There are 2 ways to use the functionality:
* via OXID console commands
* via a GUI based dashboard in eShop administration area under _Service -> Module Configuration Im-/Exporter_. 

All relevant modules configuration is being used including: Versions, Extended classes, Module classes, Templates, Blocks, Settings and Events. 

# Install

## Install using a local repository

* Create a local directory for repositories in your project, e.g. `oxideshop/extensions`.
* Check-out this module and move it to the directory you just created
* Add the repository to your project's compser.json, e.g. like this:

  ```json
    "repositories": {
        "oxid-professional-services/oxid-modules-config": {
            "type": "path",
            "url": "extensions/oxps_modulesconfig/"
        }
    }
  ```
## Install from VCS

* Require `oxid-professional-services/oxid-modules-config`

# Compatibility table


| OXID Eshop Version| OXID Modules Config Version | 
|-------------------|-----------------------------|
|6.1                | 6.1                         |
|6.2                | Not yet supported           | 


