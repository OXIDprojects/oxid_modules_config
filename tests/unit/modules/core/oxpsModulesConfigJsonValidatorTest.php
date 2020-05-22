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

/**
 * Class oxpsModulesConfigJsonValidatorTest
 * Tests for core class oxpsModulesConfigJsonValidator.
 *
 * @see oxpsModulesConfigJsonValidator
 */
class oxpsModulesConfigJsonValidatorTest extends OxidTestCase
{

    /**
     * Subject under the test.
     *
     * @var oxpsModulesConfigJsonValidator
     */
    protected $SUT;


    /**
     * Set SUT state before test.
     */
    public function setUp()
    {
        parent::setUp();
        // No need to mock the SUT
        $this->SUT = new \OxidProfessionalServices\ModulesConfig\Core\JsonValidator();
    }

    /**
     * Import data validation data provider.
     *
     * @return array
     */
    public function modulesConfigurationValidationDataProvider()
    {
        $aSettingsDataHeader = array(
            '_OXID_ESHOP_MODULES_CONFIGURATION_' => array(
                'sShopVersion' => '5.2.0',
                'sShopEdition' => 'EE',
                'sShopId'      => 1,
                'aModules'     => array(),
            )
        );

        return array(
            array(array(), $aSettingsDataHeader, array('OXPS_MODULESCONFIG_ERR_EMPTY_DATA')),
            array(array(1), $aSettingsDataHeader, array('OXPS_MODULESCONFIG_ERR_INVALID_FORMAT')),
            array(array('some_setting' => 1), $aSettingsDataHeader, array('OXPS_MODULESCONFIG_ERR_INVALID_FORMAT')),
            array(array('_OXID_ESHOP_MODULES_CONFIGURATION_' => array()), $aSettingsDataHeader, array('OXPS_MODULESCONFIG_ERR_INVALID_FORMAT')),
            array(
                array('_OXID_ESHOP_MODULES_CONFIGURATION_' => array('5.2.0', 'EE', 1, array())),
                $aSettingsDataHeader,
                array('OXPS_MODULESCONFIG_ERR_INVALID_FORMAT')
            ),
            array(
                array(
                    '_OXID_ESHOP_MODULES_CONFIGURATION_' => array(
                        'sShopVersion', 'sShopEdition', 'sShopId', 'aModules' => array()
                    )
                ),
                $aSettingsDataHeader,
                array('OXPS_MODULESCONFIG_ERR_INVALID_FORMAT')
            ),
            array(
                array(
                    '_OXID_ESHOP_MODULES_CONFIGURATION_' => array(
                        'sShopVersion' => '5.2.0',
                        'sShopEdition' => 'EE',
                        'sShopId'      => 1,
                    )
                ),
                $aSettingsDataHeader,
                array('OXPS_MODULESCONFIG_ERR_INVALID_FORMAT')
            ),
            array(
                array(
                    '_OXID_ESHOP_MODULES_CONFIGURATION_' => array(
                        'sShopVersion' => '4.4.4',
                        'sShopEdition' => 'CE',
                        'sShopId'      => 'baseshop',
                        'aModules'     => array()
                    )
                ),
                $aSettingsDataHeader,
                array(
                    'OXPS_MODULESCONFIG_ERR_SHOP_VERSION',
                    'OXPS_MODULESCONFIG_ERR_SHOP_EDITION',
                    'OXPS_MODULESCONFIG_ERR_WRONG_SUBSHOP'
                )
            ),
            array(
                array(
                    '_OXID_ESHOP_MODULES_CONFIGURATION_' => array(
                        'sShopVersion' => '5.2.0',
                        'sShopEdition' => 'CE',
                        'sShopId'      => 'baseshop',
                        'aModules'     => array()
                    )
                ),
                $aSettingsDataHeader,
                array('OXPS_MODULESCONFIG_ERR_SHOP_EDITION', 'OXPS_MODULESCONFIG_ERR_WRONG_SUBSHOP')
            ),
            array(
                array(
                    '_OXID_ESHOP_MODULES_CONFIGURATION_' => array(
                        'sShopVersion' => '5.2.0',
                        'sShopEdition' => 'EE',
                        'sShopId'      => 'baseshop',
                        'aModules'     => array()
                    )
                ),
                $aSettingsDataHeader,
                array('OXPS_MODULESCONFIG_ERR_WRONG_SUBSHOP')
            ),
            array(
                array(
                    '_OXID_ESHOP_MODULES_CONFIGURATION_' => array(
                        'sShopVersion' => '5.2.0',
                        'sShopEdition' => 'EE',
                        'sShopId'      => 1,
                        'aModules'     => array()
                    )
                ),
                $aSettingsDataHeader,
                array()
            ),
        );
    }

    /**
     * @dataProvider modulesConfigurationValidationDataProvider
     */
    public function testValidateJsonData(array $aImportData, array $aSettingsDataHeader, array $aExpectedErrors)
    {
        $this->SUT->init($aImportData, $aSettingsDataHeader);

        $this->assertSame($aExpectedErrors, $this->SUT->validateJsonData());
    }

    public function testValidateJsonData_nothingInitialized_returnEmptyDataError()
    {
        $this->assertSame(array('OXPS_MODULESCONFIG_ERR_EMPTY_DATA'), $this->SUT->validateJsonData());
    }

    public function testValidateJsonData_noSettingHeaderInitialized_returnInvalidFormatError()
    {
        $this->SUT->init(
            array(
                '_OXID_ESHOP_MODULES_CONFIGURATION_' => array(
                    'sShopVersion' => '5.2.0',
                    'sShopEdition' => 'EE',
                    'sShopId'      => 1,
                    'aModules'     => array()
                )
            ),
            array()
        );

        $this->assertSame(array('OXPS_MODULESCONFIG_ERR_INVALID_FORMAT'), $this->SUT->validateJsonData());
    }
}
