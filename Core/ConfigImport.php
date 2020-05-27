<?php
/**
 * This file is part of OXID Module Configuration Im-/Exporter module.
 *
 * OXID Module Configuration Im-/Exporter module is free software:
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * OXID Module Configuration Im-/Exporter module is distributed in
 * the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID Module Configuration Im-/Exporter module.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @category      module
 * @package       modulesconfig
 * @author        OXID Professional services
 * @link          http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2014
 */

namespace OxidProfessionalServices\ModulesConfig\Core;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidCommunity\ModuleInternals\Core\ModuleStateFixer;
use OxidProfessionalServices\OxidConsole\Core\ShopConfig;
use OxidEsales\Eshop\Application\Controller\Admin\ShopLicense;
use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Console\Logger\ConsoleLogger;
use OxidProfessionalServices\ModulesConfig\Controller\Admin\ShopMain;
use OxidEsales\Facts\Facts;
use OxidEsales\Eshop\Application\Model\Shop;

/**
 * Class ConfigImport
 * Implements functionality for the ImportCommand
 */
class ConfigImport extends CommandBase
{

    /**
     * @var ShopConfig $oConfig
     */
    protected $oConfig;

    /**
     * @var int The shop id to load the config for.
     */
    protected $sShopId;

    /** @var ConsoleLogger $logger */
    protected $logger;

    /**
     * @var array "name+module" => type
     * used to check if the imported config value type matches the stored type in the oxconfig table
     * if not the type must be overridden.
     * it helps to avoid unnecessary db rights on deployment
     */
    protected $storedVarTypes = [];

    protected $storedDisplayConfigHash = [];


    public function init(){
        parent::init();
        $db = DatabaseProvider::getDb();
        $hashValues = $db->getCol("SELECT md5(CONCAT(oxcfgmodule,'#', oxcfgvarname,'#', oxgrouping,'#', oxvarconstraint,'#', oxpos)) FROM oxconfigdisplay");
        $this->storedDisplayConfigHash = array_fill_keys($hashValues, true);
        $this->logger = new ConsoleLogger($this->output);
    }

    /*
     * executes all functionality which is necessary for a call of OXID console config:import
     *
     */
    public function executeConsoleCommand()
    {
        try {
            $this->init();
            // import environment specific config values

            $aMetaConfig = $this->readConfigValues($this->getShopsConfigFileName());
            $aShops = $aMetaConfig['shops'];
            $this->runShopConfigImportForAllShops($aShops);
            $this->generateOxserial();
            $this->output->writeLn("done");
        } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
            $this->output->writeLn("Could not parse a YAML File.");
            $this->output->writeLn($e->getMessage());
            exit(1);
        } catch (\oxFileException $oEx) {
            $this->output->writeLn("Could not complete");
            $this->output->writeLn($oEx->getMessage());
            exit(2);
        } catch (\RuntimeException $e) {
            $this->output->writeLn("Could not complete.");
            $this->output->writeLn($e->getMessage());
            $this->output->writeLn($e->getTraceAsString());
            exit(3);
        }
    }

    /**
     * runShopConfigImportForOneShop
     *
     * @param $sShop
     * @param $sRelativeFileName
     *
     * @throws \Exception
     */
    protected function runShopConfigImportForOneShop($sShop, $sRelativeFileName)
    {

        $sFileName = $this->getConfigDir() . $sRelativeFileName;
        $aResult = $this->readConfigValues($sFileName);

        $aResult = $this->merge_config($this->aDefaultConfig, $aResult);

        if ($this->sEnv) {
            $sEnvDirName = $this->getEnvironmentConfigDir();
            $sFileName = $sEnvDirName . $sRelativeFileName;
            $aEnvConfig = $this->readConfigValues($sFileName);
            $aResult = $this->merge_config($aResult, $aEnvConfig);
        }

        $this->output->writeLn("Importing config for shop $sShop");

        $this->importConfigValues($aResult);
    }

    /**
     * Generates OXSERIAL stored in oxshops table from aSerials (from oxconfig table)
     */
    protected function generateOxserial()
    {
        $licence = oxNew(ShopLicense::class);
        Registry::getSession()->setVariable('malladmin', true);

        $licence->updateShopSerial();

        $this->output->writeLn('generated OXSERIAL');
    }

    /**
     * merge tow config arrays
     *
     * @param $aBase
     * @param $aOverride
     *
     * @return
     */
    protected function merge_config($aBase, $aOverride)
    {
        foreach ($aOverride as $key => $mOverriderValue) {
            if (is_array($mOverriderValue)) {
                $aBaseValue = isset($aBase[$key]) ? $aBase[$key] : null;
                if ($aBaseValue) {
                    if (is_array($aBaseValue)) {
                        if ($key == 'module') {
                            foreach ($mOverriderValue as $sModuleId => $aModuleInfo) {
                                if (isset($aBaseValue[$sModuleId])) {
                                    $aBaseValue[$sModuleId] = array_merge($aBaseValue[$sModuleId], $aModuleInfo);
                                } else {
                                    $aBaseValue[$sModuleId] = $aModuleInfo;
                                }
                            }
                            $mOverriderValue = $aBaseValue;
                        } else {
                            $mOverriderValue = array_merge($aBaseValue, $mOverriderValue);
                        }
                    } else {
                        $this->output->writeLn(
                            "ERROR: Ignoring corrupted common config value '$key':'$aBaseValue' for shop " . $this->sShopId
                        );
                    }
                }
            } else {
                $this->output->writeLn(
                    "ERROR: Skipping corrupted config value '$key':'$mOverriderValue' for shop " . $this->sShopId
                );
                continue;
            }
            $aBase[$key] = $mOverriderValue;
        }

        return $aBase;
    }

    /**
     * @param $aConfigValues
     */
    protected function importShopsConfig($aConfigValues)
    {
        /**
         * @var \oxShop $oShop
         */
        $oShop = oxNew(Shop::class);
        $sShopId = $this->sShopId;
        if (!$oShop->load($sShopId)) {
            $this->output->writeLn("[WARN] Creating new shop $sShopId");
            $this->createNewShop($aConfigValues, $sShopId, $oShop);
            $oShop->setId($sShopId);
            $oShop->init();
            $oConfig = ShopConfig::get(1);
            $oConfig->saveShopConfVar(
                'arr',
                'aModules',
                array(),
                $sShopId,
                ""
            );
        }

        $oShop->setShopId($sShopId);
        $aOxShopSettings = $aConfigValues['oxshops'];
        if ($aOxShopSettings) {
            $db = \oxDb::getDb(\oxDb::FETCH_MODE_ASSOC);
            $old = $db->select("select * from oxshops where oxid = ?",[$sShopId])->fetchAll();
            $old = $old ? $old[0] : [];
            $diff = array_diff_assoc($aOxShopSettings,$old);
            if ($diff) {
                $oShop->assign($aOxShopSettings);
                //fake active shopid to allow derived update
                $oShop->getConfig()->setShopId($sShopId);
                $oShop->save();
                for ($i = 1; $i <= 3; $i++) {
                    $oShop->setLanguage($i);
                    foreach ($aOxShopSettings as $sVarName => $mVarValue) {
                        $iPosLastChar = strlen($sVarName) - 1;
                        $iPosUnderscore = $iPosLastChar - 1;
                        if ($sVarName[$iPosUnderscore] == '_' && $sVarName[$iPosLastChar] == $i) {
                            $sFiledName = substr($sVarName, 0, strlen($sVarName) - 2);
                            $aOxShopSettings[$sFiledName] = $mVarValue;
                        }
                    }
                    $oShop->assign($aOxShopSettings);
                    $oShop->save();
                }
            }
        }
    }

    /**
     * Create new sub-shop through config file
     *
     * @param array     $aConfigValues  ConfigValues array
     * @param string    $sShopId        Shop Id
     * @param object    $oShop          OxidEsales\Eshop\Application\Model\Shop object
     */
    protected function createNewShop($aConfigValues, $sShopId, $oShop)
    {
        $parentShopId = isset($aConfigValues['oxshops']['OXPARENTID']) ?? '';

        $shopMain = oxNew(ShopMain::class);
        $facts = new Facts();
        if ($parentShopId && !$facts->isEnterprise()) {
            $this->logger->error('Shop has to be an EE to add a subshop.');
        } else {
            //Set new shop parameters
            if (is_array($aConfigValues['oxshops']) && count($aConfigValues['oxshops']) > 0) {
                $parameters = array(
                    'oxshops__oxid' => $sShopId
                );
                $subjLang = null;
                foreach ($aConfigValues['oxshops'] as $configKey => $configValue) {
                    $configKey = strtolower($configKey);
                    $parameters['oxshops__' . $configKey] = trim($configValue);

                    //set default language
                    if ($configKey == 'oxdeflanguage') {
                        $subjLang = trim($configValue);
                    }
                }

                $shopLanguageId = ($subjLang && $subjLang > 0) ? $subjLang : 0;
                $config = Registry::getConfig();
                $oShop->loadInLang($shopLanguageId, $sShopId);
                $oShop->setLanguage(0);
                $oShop->assign($parameters);
                $oShop->setLanguage($shopLanguageId);

                $canCreateShop = $shopMain->canCreateShop($sShopId, $oShop, $config);
                if ($canCreateShop) {
                    try {
                        $oShop->save();
                        $shopMain->updateShopInformation($config, $oShop, $sShopId);
                        Registry::getSession()->setVariable("actshop", $sShopId);
                    } catch (\Exception $exception) {
                        $this->logger->error(
                            "There is error encountered while creating shop $sShopId." .
                            $exception->getMessage()
                        );
                    }
                } else {
                    $this->logger->error("Shop $sShopId can not create.");
                }
            }
        }
    }

    /*
     * @param string $sShopId
     * @param array $aConfigValues
     * @param bool $blRestoreModuleDefaults
     */
    protected function importConfigValues($aConfigValues)
    {
        $sShopId = $this->sShopId;
        $this->importShopsConfig($aConfigValues);

        $oConfig = ShopConfig::get($sShopId);
        $this->oConfig = $oConfig;
        Registry::set('oxConfig',$oConfig);
        //the oxutilsobject hold the shop id indirectly within the shopid calculator
        //and so will cause reading the wrong cache and so cause errors during the fix states
        $_POST['shp'] = $sShopId;
        //just setting the correct shopId on this object because it is defaults to one load by config init.
        //doing so does not having any known effect.
        $shop = $oConfig->getActiveShop();
        $shop->setShopId($sShopId);
        //set the global config object in oxid 6.1
        $shop->setConfig($oConfig);
        //we need a fresh instance here because
        //shopId calculator is private
        $freshUtilsObject = new \OxidEsales\Eshop\Core\UtilsObject();
        Registry::set(\OxidEsales\Eshop\Core\UtilsObject::class, $freshUtilsObject);
        //clear oxnew cache that may hold objects like Shop class from the first run 
        $freshUtilsObject->resetInstanceCache();

        $ouo = Registry::get(\OxidEsales\Eshop\Core\UtilsObject::class);
        if($oConfig->getShopId() != $sShopId ||
            $oConfig->getActiveShop()->getShopId() != $sShopId ||
            $ouo->getShopId() != $sShopId) {
            throw new \Exception("ShopId was not set correctly, this means shop internal have changed and import must be adapted");
        }



        $aDisabledModules = $oConfig->getConfigParam('aDisabledModules');
        if ($aDisabledModules == null){
            //if the oxconfig database was cleared (eg in case of error)
            //fall back to a empty array
            $aDisabledModules = [];
        }
        $disabledModulesBeforeImport = array_fill_keys($aDisabledModules, true);
        $modulesKnownBeforeImport = $oConfig->getConfigParam('aModuleVersions');

        $aModuleVersions = $this->getConfigValue($aConfigValues,'aModuleVersions');

        /** @var ModuleStateFixer $oModuleStateFixer */
        $oModuleStateFixer = \oxRegistry::get(ModuleStateFixer::class);
        $oModuleStateFixer->setConfig($oConfig);
        /** @var \oxModule $oModule */
        $oModule = oxNew('oxModule');
        $oModule->setConfig($oConfig);

        $updatedModules = [];
        $notLoadedModules = [];
        foreach ($aModuleVersions as $sModuleId => $sVersion) {
            $oldVersion = $modulesKnownBeforeImport[$sModuleId];
            $versionToImport = $aModuleVersions[$sModuleId];

            $gt = version_compare($versionToImport, $oldVersion,'>');
            if ($gt) {
                $updatedModules[$sModuleId] = $sModuleId;
                if (isset($oldVersion)) {
                    $this->output->writeLn(
                        "[INFO] {$sModuleId} has a different version (db:$oldVersion vs import:$versionToImport) disabling it, so it can do updates"
                    );
                } else {
                    $this->output->writeLn(
                        "[NOTE] {$sModuleId} $versionToImport is new"
                    );
                }
                if (!$oModule->load($sModuleId)) {
                    //maybe fine for new modules because shop can not load them before
                    //modules directory was scanned (by "loadModulesFromDir"-method)
                    //later we will check if there is anything left that can still not be loaded
                    $notLoadedModules[] = $sModuleId;
                    continue;
                } else {
                    //$cv = $oModule->getInfo('version');
                    //$this->output->writeLn(
                    //    "[NOTE] {$sModuleId} current version is $cv"
                    //);
                }
                $disabledModulesBeforeImport[$sModuleId] = 'disabledByUpdate';
                if ($oModule->isActive()) {
                    $oModuleStateFixer->deactivate($oModule);
                }
            }
        }

        $this->importModuleConfig($aConfigValues);

        $modulesKnownByPath = $oConfig->getConfigParam('aModulePaths');

        foreach ($notLoadedModules as $sModuleId) {
            if (!$oModule->load($sModuleId)) {
                $this->output->writeLn(
                    "[WARN] can not load {$sModuleId} given in yaml, please make a fresh export without that module"
                );
            }
        }

        $this->restoreGeneralShopSettings($aConfigValues);

        $this->importThemeConfig($aConfigValues['theme']);

        $aDisabledModules = $oConfig->getConfigParam('aDisabledModules');

        $aModulePathsClean = $modulesKnownByPath;
        foreach ($modulesKnownByPath as $sModuleId => $path) {
            if (!isset($aModuleVersions[$sModuleId])) {
                if (!$oModule->load($sModuleId)) {
                    unset ($aModulePathsClean[$sModuleId]);
                    $this->output->writeLn(
                        "[WARN] {$sModuleId} it is not part of the import but, not installed physically, but somehow registered; removing it from modulePath array."
                    );
                    $oConfig->saveShopConfVar('aarr','aModulePaths',$aModulePathsClean);
                }
                $isDisabled = in_array($sModuleId, $aDisabledModules);
                if (!$isDisabled) {
                    if ($disabledModulesBeforeImport[$sModuleId]) {
                        $aDisabledModules[] = $sModuleId;
                    }
                    $this->output->writeLn(
                        "[WARN] {$sModuleId} it is not part of the import but installed on this system, please create a new export"
                    );
                }
            }
        }
        $this->saveShopVar('aDisabledModules', $aDisabledModules, '','arr');

        foreach ($aModuleVersions as $sModuleId => $sVersion) {
            if (!$oModule->load($sModuleId)) {
                $this->output->writeLn(
                    "[ERROR] can not load {$sModuleId} given in importfile shop{$sShopId}.yaml in aModuleVersions please check if it is installed and working"
                );
                continue;
            }

            //execute activate event
            if ($this->aConfiguration['executeModuleActivationEvents'] && $oModule->isActive()) {
                $wasDeactivatedBeforeImport = isset($modulesKnownBeforeImport[$sModuleId]) && isset($disabledModulesBeforeImport[$sModuleId]);
                $wasUnknownBeforeImport = !isset($modulesKnownBeforeImport[$sModuleId]);
                if ($wasDeactivatedBeforeImport || $wasUnknownBeforeImport) {
                    $this->output->writeLn(
                        "[INFO] activating module $sModuleId"
                    );
                    try {
                        /** Support for the key 'controllers' was added in MetaData version 2.0 */
                        $oModuleStateFixer->activate($oModule);
                    } catch (\Exception $exception) {
                        print "[ERROR] $sModuleId was NOT activated again.";
                    }

                }
            }

            //fix state again because class chain was reset by the import above
            //also onActivate call event can cause duplicate tpl blocks
            $oModuleStateFixer->setDebugOutput($this->getDebugOutput());
            $oModuleStateFixer->setOutput($this->output);
            $oModuleStateFixer->fix($oModule);

            $sCurrentVersion = $oModule->getInfo("version");
            if ($sCurrentVersion != $sVersion) {
                $this->output->writeLn(
                    "[WARN] {$sModuleId} version on export" .
                    " $sVersion vs current version $sCurrentVersion please create a fresh export"
                );
                $aModuleVersions[$sModuleId] = $sCurrentVersion;
                $this->saveShopVar('aModuleVersions', $aModuleVersions, '', 'arr');
            }
        }
    }

    protected function getConfigValue($aConfigValues, $name)
    {
        $TypeAndValue = $aConfigValues[$this->sNameForGeneralShopSettings][$name];
        $TypeAndValue = $this->getTypeAndValue($name, $TypeAndValue);
        $value = $TypeAndValue[1];
        return $value;
    }

    /**
     * Restore module defaults and import modul config
     * This will scan the module directory and add all modules (module paths).
     * This must be done before aDisabledModules is restored because this function deactivates modules.
     *
     * @param oxModule $aModules
     *
     * @return null
     */
    protected function importModuleConfig(&$aConfigValues)
    {
        $allModulesConfigFromYaml = $aConfigValues['module'];

        $oConfig = $this->oConfig;
        $oxModuleList = oxNew('oxModuleList');
        $oxModuleList->setConfig($oConfig);

        $exclude = $this->aConfiguration['excludeFields'];
        $excludeDeep = $this->aConfiguration['excludeDeep'];
        $excludeFlat = array_flip(array_filter($exclude,'is_string'));

        /**
         * @var \oxModuleList $oxModuleList
         * //it is important to call this method to load new module into the shop
         */
        $aModules = $oxModuleList->getModulesFromDir($oConfig->getModulesDir());

        $aGeneralSettings = &$aConfigValues[$this->sNameForGeneralShopSettings];
        $aModuleExtensions = &$aGeneralSettings['aModules'];
        foreach ($aModules as $sModuleId => $oModule) {
            if ($oModule->hasExtendClass()) {
                $aAddModules = $oModule->getExtensions();
                foreach ($aAddModules as $key => $ext) {
                    if(!isset($aModuleExtensions[$key])) {
                        $aModuleExtensions[$key][] = $ext;
                    }
                }
            }

            // restore default module settings
            /** @var \oxModule $oModule */
            $aDefaultModuleSettings = $oModule->getInfo("settings");

            // Ensure both arrays are array/not null
            $aTmp = is_null($aDefaultModuleSettings) ? array() : $aDefaultModuleSettings;
            $aDefaultModuleSettings = array();
            foreach ($aTmp as $nr => $aSetting) {
                if(!is_array($aSetting)) {
                    throw new \Exception("Error in metadata.php settings of $sModuleId in setting nr/key '$nr'. Value '" . print_r($aSetting,true) ."' is not an array!");
                }
                $aDefaultModuleSettings[$aSetting['name']] = $aSetting;
            }
            // array ($key => $value)
            $aModuleOverride = isset($allModulesConfigFromYaml[$sModuleId]) && is_array($allModulesConfigFromYaml[$sModuleId]) ? $allModulesConfigFromYaml[$sModuleId] : array();

            // merge from aModulesOverwrite into aDefaultModuleSettings
            $aMergedModuleSettings = array();
            foreach ($aDefaultModuleSettings as $sName => $aDefaultModuleSetting) {
                if (array_key_exists($sName, $aModuleOverride)) {
                    //print "module:$sModuleId $sName $aModuleOverride\n";
                    $aDefaultModuleSetting['value'] = $aModuleOverride[$sName];
                    unset($aModuleOverride[$sName]);
                }
                $aMergedModuleSettings[$sName] = $aDefaultModuleSetting;
            }

            foreach ($aModuleOverride as $sName => $mValue) {
                $type = 'str';
                if ($this->is_assoc_array($mValue)) {
                    $type = 'aarr';
                } elseif(is_array($mValue)) {
                    $type = 'arr';
                }
                $aMergedModuleSettings[$sName] = array('value' => $mValue, 'type' => $type);
            }

            // Save all that is not part of $this->aConfiguration['excludeFields'])
            foreach ($aMergedModuleSettings as $sVarName => $aVarValue) {
                // We do not want to override with default values of fields which
                // excluded from configuration export
                // as this will override those values with every config import.
                if (isset($excludeFlat[$sVarName])) {
                    continue;
                }
                if ($aVarValue["type"] == 'aarr') {
                    if(isset($excludeDeep[$sVarName])) {
                        $innerExcludes = $excludeDeep[$sVarName];
                        if (!is_array( $innerExcludes )) {
                            $innerExcludes = [$innerExcludes];
                        }
                        $value         = &$aVarValue['value'];
                        $original      = $this->oConfig->getConfigParam( $sVarName );
                        if ($original) {
                            foreach ($innerExcludes as $exclude) {
                                $value[$exclude] = $original[$exclude];
                            }
                        }//else if the value is included in the dump but not avail in the database,
                        // that use it as default, that may be handy to have values that are imported once but not
                        // overwritten after that
                    }
                }


                $this->saveShopVar($sVarName, $aVarValue['value'], "module:$sModuleId", $aVarValue["type"]);
            }

        }
    }

    protected function getTypeAndValue($sVarName, $mVarValue)
    {
        if ($this->is_assoc_array($mVarValue)) {
            $iCount = count($mVarValue);
            if ($iCount == 0) {
                $sVarType = 'arr';
            } elseif ($iCount > 1) {
                $sVarType = 'aarr';
            } else {
                $sVarType = key($mVarValue);
                $mVarValue = $mVarValue[$sVarType];
            }
        } elseif (is_array($mVarValue)) {
            $sVarType = 'arr';
        } elseif(is_bool($mVarValue)) {
            $sVarType = 'bool';
        } else {
            //deprecated check for 'bl'
            if (substr($sVarName, 0, 2) === "bl") {
                $sVarType = 'bool';
            } else {
                $sVarType = 'str';
            }
        }

        return array($sVarType, $mVarValue);
    }

    protected function getStoredVarTypes()
    {
        $db = \oxDb::getDb(\oxDb::FETCH_MODE_ASSOC);
        $sQ = "select CONCAT(oxvarname,'+',oxmodule) as mapkey, oxvartype from oxconfig where oxshopid = ?";
        $allRows = $db->getAll($sQ, [$this->sShopId]);
        $map = [];
        foreach($allRows as $row) {
            $map[$row['mapkey']] = $row['oxvartype'];
        }
        return $map;
    }

    public function getShopConfType($sVarName,$sSectionModule)
    {

        $cachekey = $sVarName . '+' . $sSectionModule;
        return isset($this->storedVarTypes[$cachekey]) ? $this->storedVarTypes[$cachekey] : null;
    }


    protected function saveShopVarWithTypeInfo($sVarName, $mVarValue, $sSectionModule){
        list($sVarType, $mVarValue) = $this->getTypeAndValue($sVarName, $mVarValue);
        $this->saveShopVar($sVarName, $mVarValue, $sSectionModule, $sVarType);
    }


    protected function saveShopVar($sVarName, $mVarValue, $sSectionModule, $sVarType)
    {
        $sShopId = $this->sShopId;
        $oConfig = $this->oConfig;

        if ($sShopId != 1) {
            $aOnlyMainShopVars = array_fill_keys(['blMallUsers', 'aSerials', 'IMD', 'IMA', 'IMS'],true);
            if ($aOnlyMainShopVars[$sVarName]){
                return;
            }
        }

        $oConfig->saveShopConfVar(
            $sVarType,
            $sVarName,
            $mVarValue,
            $sShopId,
            $sSectionModule
        );

        if(strpos($sSectionModule,'module') === 0) {
            if($existsAlsoInGlobalNameSpace = $this->getShopConfType($sVarName,'')) {
                $db = \oxDb::getDb();
                $db->execute("DELETE FROM oxconfig WHERE oxshopid = ? AND oxvarname = ? AND oxmodule = ''",[$this->sShopId,$sVarName]);
                $this->output->writeLn("the config value $sVarName from module $sSectionModule was delete from global namespace");
            }
        }
    }

    protected function is_assoc_array($arr)
    {
        return is_array($arr) && (array_keys($arr) !== range(0, count($arr) - 1));
    }



    /**
     * @param $aSectionData
     * @param $sShopId
     * @param $oConfig
     */
    protected function importThemeConfig($aThemes)
    {
        if ($aThemes == null) {
            return;
        }
        $parentTheme = $this->oConfig->getConfigParam('sTheme');
        $theme = $this->oConfig->getConfigParam('sCustomTheme');
        foreach ($aThemes as $sThemeId => $aSettings) {
            if ($sThemeId != $parentTheme && $theme != $sThemeId) {
                $this->output->writeln("Theme $sThemeId from import from config file ignored because it is not active");
                continue;
            }
            $sSectionModule = "theme:$sThemeId";
            foreach ($aSettings as $sVarName => $mVarValue) {
                if(isset($mVarValue['value'])) {
                    $this->saveShopVarWithTypeInfo($sVarName, $mVarValue['value'], $sSectionModule);
                    $this->saveThemeDisplayVars($sVarName, $mVarValue, $sSectionModule);
                }
                else {
                    $this->saveShopVarWithTypeInfo($sVarName, $mVarValue, $sSectionModule);
                }
            }
        }
    }

    /**
     * @param $aShops
     */
    protected function runShopConfigImportForAllShops($aShops)
    {
        foreach ($aShops as $sShop => $sFileName) {
            if (!$this->allShops && !in_array($sShop, $this->shops)) {
                continue;
            }
            $this->sShopId = $sShop;
            $this->storedVarTypes = $this->getStoredVarTypes();
            $this->runShopConfigImportForOneShop($sShop, $sFileName);
        }
    }

    /**
     * @param $aConfigValues
     * @return array
     */
    protected function restoreGeneralShopSettings($aConfigValues)
    {
        $aModuleVersions = array();
        $aGeneralSettings = $aConfigValues[$this->sNameForGeneralShopSettings];
        $sSectionModule = '';
        foreach ($aGeneralSettings as $sVarName => $mTypedVarValue) {
            list($sType, $mVarValue) = $this->getTypeAndValue($sVarName, $mTypedVarValue);
            if ($sVarName == 'aModules') {
                $aModulesTmp = array();
                foreach ($mVarValue as $sBaseClass => $aClassNames) {
                    $sAmpSeparatedClassNames = join('&', $aClassNames);
                    $aModulesTmp[$sBaseClass] = $sAmpSeparatedClassNames;
                }
                $mTypedVarValue = $aModulesTmp;
            } elseif ($sVarName == 'aModuleVersions') {
                $aModuleVersions = $mVarValue;
            }
            $this->saveShopVarWithTypeInfo($sVarName, $mTypedVarValue, $sSectionModule);
        }
        return $aModuleVersions;
    }

    protected function saveThemeDisplayVars($sVarName, $mVarValue, $sModule)
    {
        $oDb = \oxDb::getDb();
        $sModuleQuoted = $oDb->quote($sModule);
        $sVarNameQuoted = $oDb->quote($sVarName);
        $constraints = isset($mVarValue['constraints']) ? $mVarValue['constraints'] : null;
        $sVarConstraintsQuoted = isset($constraints) ? $oDb->quote($constraints) : '\'\'';
        $grouping = $mVarValue['grouping'];
        $sVarGroupingQuoted = isset($grouping) ? $oDb->quote($grouping) : '\'\'';
        $pos = isset($mVarValue['pos']) ? $mVarValue['pos'] : null;
        $sVarPosQuoted = isset($pos) ? $oDb->quote($pos) : '\'\'';

        $sNewOXIDdQuoted = $oDb->quote(\oxUtilsObject::getInstance()->generateUID());

        if (isset($this->storedDisplayConfigHash[md5($sModule . '#' . $sVarName . '#' .  $grouping. '#'. $constraints .'#'. $pos)])){
            return;
        }

        $sQ = "delete from oxconfigdisplay WHERE OXCFGVARNAME = $sVarNameQuoted and OXCFGMODULE = $sModuleQuoted";
        $oDb->execute($sQ);

        $sQ = "insert into oxconfigdisplay (oxid, oxcfgmodule, oxcfgvarname, oxgrouping, oxvarconstraint, oxpos )
               values($sNewOXIDdQuoted, $sModuleQuoted, $sVarNameQuoted, $sVarGroupingQuoted, $sVarConstraintsQuoted, $sVarPosQuoted)";
        $oDb->execute($sQ);


    }

}
