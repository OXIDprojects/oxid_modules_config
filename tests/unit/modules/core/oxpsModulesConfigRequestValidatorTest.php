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
 * Class oxpsModulesConfigRequestValidatorTest
 * Tests for core class oxpsModulesConfigRequestValidator.
 *
 * @see oxpsModulesConfigRequestValidator
 */
class oxpsModulesConfigRequestValidatorTest extends OxidTestCase
{

    /**
     * Subject under the test.
     *
     * @var oxpsModulesConfigRequestValidator
     */
    protected $SUT;


    /**
     * Set SUT state before test.
     */
    public function setUp()
    {
        parent::setUp();

        $this->SUT = $this->getMock(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, array('_isReadableFile'));
        $this->SUT->expects($this->any())->method('_isReadableFile')->will($this->returnValue(true));

        // Content model mock
        $oContent = $this->getMock(\OxidProfessionalServices\ModulesConfig\Model\Content::class, array('getModulesList', 'getSettingsList'));
        $oContent->expects($this->any())->method('getModulesList')->will(
            $this->returnValue(
                array(
                    'my_module'      => (object) array('version' => '1.0.0'),
                    'good_extension' => (object) array('version' => '0.2.5'),
                )
            )
        );
        $oContent->expects($this->any())->method('getSettingsList')->will(
            $this->returnValue(
                array(
                    'version' => 'Versions',
                    'extend'  => 'Extended classes',
                )
            )
        );

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Model\Content::class, $oContent);
    }


    public function testGetErrors_nothingSet_returnEmptyArray()
    {
        $this->assertSame(array(), $this->SUT->getErrors());
    }

    public function testGetErrors_errorsAdded_returnTheErrorsAsArray()
    {
        $this->SUT->addError('ERR');
        $this->SUT->addError('FATAL_ERR');

        $this->assertSame(array('ERR', 'FATAL_ERR'), $this->SUT->getErrors());
    }

    public function testGetErrors_multipleErrorsAdded_returnTheErrorsAsArray()
    {
        $this->SUT->addError('ERR');
        $this->SUT->addError('FATAL_ERR');

        $this->SUT->addErrors(array('ERR2', 'ERR3'));

        $this->assertSame(array('ERR', 'FATAL_ERR', 'ERR2', 'ERR3'), $this->SUT->getErrors());
    }

    public function testGetErrors_errorsReset_returnEmptyArray()
    {
        $this->SUT->addError('ERR');
        $this->SUT->addError('FATAL_ERR');

        $this->SUT->addErrors(array('ERR2', 'ERR3'));

        $this->SUT->resetError();

        $this->assertSame(array(), $this->SUT->getErrors());
    }


    /**
     * @dataProvider requestDataProvider
     */
    public function testValidateRequestData($sCondition, array $aData, array $aExpectedErrors, $blReturn)
    {
        $this->assertSame($blReturn, $this->SUT->validateRequestData($aData));

        $this->assertSame($aExpectedErrors, $this->SUT->getErrors(), $sCondition);
    }

    public function requestDataProvider()
    {
        return array(
            array(
                'No request data',
                array(),
                array(
                    'OXPS_MODULESCONFIG_ERR_NO_MODULES',
                    'OXPS_MODULESCONFIG_ERR_NO_SETTINGS',
                    'OXPS_MODULESCONFIG_ERR_INVALID_ACTION'
                ),
                false,
            ),

            array(
                'Invalid action requested',
                array(
                    'modules'  => array('my_module'),
                    'settings' => array('version'),
                    'action'   => 'deploy'
                ),
                array('OXPS_MODULESCONFIG_ERR_INVALID_ACTION'),
                false,
            ),

            array(
                'No modules data',
                array(
                    'modules'  => array(),
                    'settings' => array('version'),
                    'action'   => 'export'
                ),
                array('OXPS_MODULESCONFIG_ERR_NO_MODULES'),
                false,
            ),

            array(
                'Non available module requested',
                array(
                    'modules'  => array('their_module'),
                    'settings' => array('version'),
                    'action'   => 'export'
                ),
                array('OXPS_MODULESCONFIG_ERR_INVALID_MODULE'),
                false,
            ),

            array(
                'No settings data',
                array(
                    'modules'  => array('my_module'),
                    'settings' => array(),
                    'action'   => 'export'
                ),
                array('OXPS_MODULESCONFIG_ERR_NO_SETTINGS'),
                false,
            ),

            array(
                'Non available setting requested',
                array(
                    'modules'  => array('my_module'),
                    'settings' => array('email'),
                    'action'   => 'export'
                ),
                array('OXPS_MODULESCONFIG_ERR_INVALID_SETTING'),
                false,
            ),

            array(
                'Request data is valid',
                array(
                    'modules'  => array('my_module'),
                    'settings' => array('version'),
                    'action'   => 'export'
                ),
                array(),
                true,
            ),
        );
    }


    /**
     * @dataProvider importDataProvider
     */
    public function testValidateImportData($sCondition, array $aData, array $aExpectedErrors, $blReturn)
    {
        // Modules configuration export, backup and import actions handler mock
        $oTransfer = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class,
            array('setImportDataFromFile', 'getImportDataValidationErrors')
        );
        $oTransfer->expects($this->never())->method('setImportDataFromFile');
        $oTransfer->expects($this->never())->method('getImportDataValidationErrors');

        oxTestModules::addModuleObject(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, $oTransfer);

        $this->assertSame($blReturn, $this->SUT->validateImportData($aData));

        $this->assertSame($aExpectedErrors, $this->SUT->getErrors(), $sCondition);
    }

    public function importDataProvider()
    {
        return array(
            array(
                'No import data',
                array(),
                array('OXPS_MODULESCONFIG_ERR_NO_FILE'),
                false,
            ),

            array(
                'File upload error',
                array(
                    'error'    => UPLOAD_ERR_PARTIAL,
                    'type'     => 'application/octet-stream',
                    'tmp_name' => '/path/to/file.json'
                ),
                array('OXPS_MODULESCONFIG_ERR_UPLOAD_ERROR'),
                false,
            ),

            array(
                'Invalid file type',
                array(
                    'error'    => '',
                    'type'     => 'text/csv',
                    'tmp_name' => '/path/to/file.csv'
                ),
                array('OXPS_MODULESCONFIG_ERR_FILE_TYPE'),
                false,
            ),

            array(
                'Uploaded file is not available',
                array(
                    'error'    => '',
                    'type'     => 'application/octet-stream',
                    'tmp_name' => ''
                ),
                array('OXPS_MODULESCONFIG_ERR_CANNOT_READ'),
                false,
            ),
        );
    }

    public function testValidateImportData_noOtherError_callJsonDataValidator()
    {
        $aFileData = array(
            'error'    => '',
            'type'     => 'application/octet-stream',
            'tmp_name' => '/path/to/bad_file.json'
        );

        // Modules configuration export, backup and import actions handler mock
        $oTransfer = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class,
            array('setImportDataFromFile', 'getImportDataValidationErrors')
        );
        $oTransfer->expects($this->once())->method('setImportDataFromFile')->with($aFileData);
        $oTransfer->expects($this->once())->method('getImportDataValidationErrors')->will(
            $this->returnValue(array('ERR_1', 'ERR_2'))
        );

        oxTestModules::addModuleObject(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, $oTransfer);

        $this->assertFalse($this->SUT->validateImportData($aFileData));
        $this->assertSame(array('ERR_1', 'ERR_2'), $this->SUT->getErrors());
    }

    public function testValidateImportData_noError()
    {
        $aFileData = array(
            'error'    => '',
            'type'     => 'application/octet-stream',
            'tmp_name' => '/path/to/good_file.json'
        );

        // Modules configuration export, backup and import actions handler mock
        $oTransfer = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class,
            array('setImportDataFromFile', 'getImportDataValidationErrors')
        );
        $oTransfer->expects($this->once())->method('setImportDataFromFile')->with($aFileData);
        $oTransfer->expects($this->once())->method('getImportDataValidationErrors')->will(
            $this->returnValue(array())
        );

        oxTestModules::addModuleObject(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, $oTransfer);

        $this->assertTrue($this->SUT->validateImportData($aFileData));
        $this->assertSame(array(), $this->SUT->getErrors());
    }
}
