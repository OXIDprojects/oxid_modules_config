<?php
/**
 * This file is part of OXID Module Configuration Im-/Exporter module.
 *
 * OXID Module Configuration Im-/Exporter module is free software:
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option] any later version.
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
 * @copyright (C] OXID eSales AG 2003-2014
 */

/**
 * Metadata version
 */
$sMetadataVersion = '2.0';

/**
 * Module information
 */
$aModule = [
    'id'          => 'oxps/modulesconfig',
    'title'       => 'OXID Module Configuration Im-/Exporter',
    'description' => [
        'de' => 'Tools, um OXID eShop Modulkonfigurationsdaten zu exportieren, importieren oder zu sichern.',
        'en' => 'Tools to export, backup and import OXID eShop modules configuration data.',
    ],
    'thumbnail'   => 'out/pictures/oxpsmodulesconfig.png',
    'version'     => '6.0.0',
    'author'      => 'OXID Professional Services',
    'url'         => 'http://www.oxid-esales.com',
    'email'       => 'info@oxid-esales.com',
    'extend'      => [
        'shop_main'  => OxidProfessionalServices\ModulesConfig\Controller\Admin\ShopMain::class,
    ],
    'controllers' => [
        'admin_oxpsmodulesconfigdashboard'  => OxidProfessionalServices\ModulesConfig\Controller\Admin\Dashboard::class,
    ],
    'templates'   => [
        'admin_oxpsmodulesconfigdashboard.tpl' => 'oxps/modulesconfig/views/admin/admin_oxpsmodulesconfigdashboard.tpl',
    ],
    'events'      => [
        'onActivate'   => 'OxidProfessionalServices\\ModulesConfig\\Core\\Module::onActivate',
        'onDeactivate' => 'OxidProfessionalServices\\ModulesConfig\\Core\\Module::onDeactivate',
    ],
    'settings' => [
        [ 'group' => 'main', 'name' => 'OXPS_MODULESCONFIG_SETTING_CONFIGURATION_DIRECTORY', 'type' => 'str', 'value' => '../../../../configurations' ]
    ]
];
