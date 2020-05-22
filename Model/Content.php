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

namespace OxidProfessionalServices\ModulesConfig\Model;

/**
 * Class Content
 * Module configuration import and export content handler defines what data is used in the processes.
 */
class Content extends \oxSuperCfg
{

    /**
     * List of modules to exclude from export and import.
     *
     * @var array
     */
    protected $_aExcludeModules = array('oxpsmodulesconfig');

    /**
     * List of module related configuration parameters to export or import.
     * These are parts of module metadata which are registered stored in database.
     *
     * @var array
     */
    protected $_aModuleSettings = array('version', 'extend', 'files', 'templates', 'blocks', 'settings', 'events');


    /**
     * Get a list of all shop modules.
     *
     * @return array
     */
    public function getModulesList()
    {
        /** @var \oxModuleList $oModuleList */
        $oModuleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);

        // Get all modules data
        $aAllModules = $oModuleList->getModulesFromDir($this->getConfig()->getModulesDir());

        // Exclude system modules like the OXID Module Configuration Im-/Exporter itself
        $aModules = array_diff_key($aAllModules, array_combine($this->_aExcludeModules, $this->_aExcludeModules));

        return $aModules;
    }

    /**
     * Get a list of settings available to export and import.
     *
     * @return array
     */
    public function getSettingsList()
    {
        $aSettings = array();

        foreach ($this->_aModuleSettings as $sSetting) {
            $aSettings[$sSetting] = sprintf('OXPS_MODULESCONFIG_SETTING_%s', \oxStr::getStr()->strtoupper($sSetting));
        }

        return $aSettings;
    }
}
