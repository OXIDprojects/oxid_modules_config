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
 * Class Admin_oxpsModulesConfigDashboardTest
 * Tests for admin controller Admin_oxpsModulesConfigDashboard.
 *
 * @see Admin_oxpsModulesConfigDashboard
 */
class Admin_oxpsModulesConfigDashboardTest extends OxidTestCase
{

    /**
     * Subject under the test.
     *
     * @var Admin_oxpsModulesConfigDashboard
     */
    protected $SUT;


    /**
     * Set SUT state before test.
     */
    public function setUp()
    {
        parent::setUp();
        // No need to mock the SUT.
        $this->SUT = new \OxidProfessionalServices\ModulesConfig\Controller\Admin\Dashboard();
    }


    public function testGetModulesList()
    {
        // Content model mock
        $oContent = $this->getMock(\OxidProfessionalServices\ModulesConfig\Model\Content::class, array( 'getModulesList'));
        $oContent->expects($this->once())->method('getModulesList')->will(
            $this->returnValue(
                array(
                    'my_module'      => (object) array('version' => '1.0.0'),
                    'good_extension' => (object) array('version' => '0.2.5'),
                )
            )
        );

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Model\Content::class, $oContent);

        $this->assertEquals(
            array(
                'my_module'      => (object) array('version' => '1.0.0'),
                'good_extension' => (object) array('version' => '0.2.5'),
            ),
            $this->SUT->getModulesList()
        );
    }


    public function testGetSettingsList()
    {
        // Content model mock
        $oContent = $this->getMock(\OxidProfessionalServices\ModulesConfig\Model\Content::class, array( 'getSettingsList'));
        $oContent->expects($this->once())->method('getSettingsList')->will(
            $this->returnValue(
                array(
                    'version' => 'Versions',
                    'extend'  => 'Extended classes',
                )
            )
        );

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Model\Content::class, $oContent);

        $this->assertSame(
            array(
                'version' => 'Versions',
                'extend'  => 'Extended classes',
            ),
            $this->SUT->getSettingsList()
        );
    }


    public function testGetAction_nothingSet_returnEmptyString()
    {
        $this->assertSame('', $this->SUT->getAction());
    }

    public function testGetAction_actionNameSet_returnTheValue()
    {
        $this->SUT->setAction('import');

        $this->assertSame('import', $this->SUT->getAction());
    }


    public function testGetMessages_nothingSet_returnEmptyArray()
    {
        $this->assertSame(array(), $this->SUT->getMessages());
    }

    public function testGetMessages_messagesAdded_returnTheMessagesAsArray()
    {
        $this->SUT->addMessage('MSG');

        $this->assertSame(array('MSG'), $this->SUT->getMessages());
    }


    public function testGetValidator()
    {
        // Request validator instance mock
        $oValidator = $this->getMock(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class);

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, $oValidator);

        $this->assertSame($oValidator, $this->SUT->getValidator());
    }


    public function testGetErrors()
    {
        // Request validator instance mock
        $oValidator = $this->getMock(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, array( 'getErrors'));
        $oValidator->expects($this->once())->method('getErrors')->will($this->returnValue(array('ERR_A', 'OTHER_ERR')));

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, $oValidator);

        $this->assertSame(array('ERR_A', 'OTHER_ERR'), $this->SUT->getErrors());
    }


    public function testActionSubmit_invalidRequestData()
    {
        // Request validator instance mock
        $oValidator = $this->getMock(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, array( 'validateRequestData'));
        $oValidator->expects($this->once())->method('validateRequestData')
            ->with(array('modules' => array(), 'settings' => array(), 'action' => ''))
            ->will($this->returnValue(false));

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, $oValidator);

        $this->SUT->actionSubmit();
        $this->assertSame(array(), $this->SUT->getMessages());
    }

    public function testActionSubmit_validExportRequest_callExportAndDownloadHandler()
    {
        /*
        modConfig::setRequestParameter('oxpsmodulesconfig_modules', array('my_module'));
        modConfig::setRequestParameter('oxpsmodulesconfig_settings', array('version' => 1, 'extend' => 1));
        modConfig::setRequestParameter('oxpsmodulesconfig_export', 1);
        */
        $this->setRequestParameter('oxpsmodulesconfig_modules', array('my_module'));
        $this->setRequestParameter('oxpsmodulesconfig_settings', array('version' => 1, 'extend' => 1));
        $this->setRequestParameter('oxpsmodulesconfig_export', 1);



        // Request validator instance mock
        $oValidator = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class,
            array( 'validateRequestData', 'addError')
        );
        $oValidator->expects($this->once())->method('validateRequestData')
            ->with(
                array('modules' => array('my_module'), 'settings' => array('version', 'extend'), 'action' => 'export')
            )
            ->will($this->returnValue(true));

        // Anyway this is called inside test (because download is mocked)
        $oValidator->expects($this->once())->method('addError')->with('OXPS_MODULESCONFIG_ERR_EXPORT_FAILED');

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, $oValidator);

        // Configuration data transfer handler mock
        $oConfigTransfer = $this->getMock(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, array( 'exportForDownload'));
        $oConfigTransfer->expects($this->once())->method('exportForDownload')->with(
            array(
                'modules'  => array('my_module'),
                'settings' => array('version', 'extend'),
                'action'   => 'export'
            )
        );

        oxTestModules::addModuleObject(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, $oConfigTransfer);

        $this->SUT->actionSubmit();
        $this->assertSame('export', $this->SUT->getAction());
        $this->assertSame(array(), $this->SUT->getMessages());
    }

    public function testActionSubmit_backupFailedToSaveFile_setBackupError()
    {
        /**
        modConfig::setRequestParameter('oxpsmodulesconfig_modules', array('my_module', 'good_extension'));
        modConfig::setRequestParameter('oxpsmodulesconfig_settings', array('version' => 1, 'extend' => 1));
        modConfig::setRequestParameter('oxpsmodulesconfig_backup', 1);
         */
        $this->setRequestParameter('oxpsmodulesconfig_modules', array('my_module', 'good_extension'));
        $this->setRequestParameter('oxpsmodulesconfig_settings', array('version' => 1, 'extend' => 1));
        $this->setRequestParameter('oxpsmodulesconfig_backup', 1);

        // Request validator instance mock
        $oValidator = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class,
            array( 'validateRequestData', 'addError')
        );
        $oValidator->expects($this->once())->method('validateRequestData')
            ->with(
                array(
                    'modules'  => array('my_module', 'good_extension'),
                    'settings' => array('version', 'extend'),
                    'action'   => 'backup'
                )
            )
            ->will($this->returnValue(true));
        $oValidator->expects($this->once())->method('addError')->with('OXPS_MODULESCONFIG_ERR_BACKUP_FAILED');

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, $oValidator);

        // Configuration data transfer handler mock
        $oConfigTransfer = $this->getMock(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, array( 'backupToFile'));
        $oConfigTransfer->expects($this->once())->method('backupToFile')->with(
            array(
                'modules'  => array('my_module', 'good_extension'),
                'settings' => array('version', 'extend'),
                'action'   => 'backup'
            ),
            ''
        )->will($this->returnValue(0));

        oxTestModules::addModuleObject(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, $oConfigTransfer);

        $this->SUT->actionSubmit();
        $this->assertSame('backup', $this->SUT->getAction());
        $this->assertSame(array(), $this->SUT->getMessages());
    }

    public function testActionSubmit_validBackupRequest_callExportAndSaveToFileHandler()
    {
        /**
        modConfig::setRequestParameter('oxpsmodulesconfig_modules', array('my_module', 'good_extension'));
        modConfig::setRequestParameter('oxpsmodulesconfig_settings', array('version' => 1, 'extend' => 1));
        modConfig::setRequestParameter('oxpsmodulesconfig_backup', 1);
         */
        $this->setRequestParameter('oxpsmodulesconfig_modules', array('my_module', 'good_extension'));
        $this->setRequestParameter('oxpsmodulesconfig_settings', array('version' => 1, 'extend' => 1));
        $this->setRequestParameter('oxpsmodulesconfig_backup', 1);

        // Request validator instance mock
        $oValidator = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class,
            array( 'validateRequestData', 'addError')
        );
        $oValidator->expects($this->once())->method('validateRequestData')
            ->with(
                array(
                    'modules'  => array('my_module', 'good_extension'),
                    'settings' => array('version', 'extend'),
                    'action'   => 'backup'
                )
            )
            ->will($this->returnValue(true));
        $oValidator->expects($this->never())->method('addError');

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, $oValidator);

        // Configuration data transfer handler mock
        $oConfigTransfer = $this->getMock(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, array( 'backupToFile'));
        $oConfigTransfer->expects($this->once())->method('backupToFile')->with(
            array(
                'modules'  => array('my_module', 'good_extension'),
                'settings' => array('version', 'extend'),
                'action'   => 'backup'
            ),
            ''
        )->will($this->returnValue(888));

        oxTestModules::addModuleObject(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, $oConfigTransfer);

        $this->SUT->actionSubmit();
        $this->assertSame('backup', $this->SUT->getAction());
        $this->assertSame(array('OXPS_MODULESCONFIG_MSG_BACKUP_SUCCESS'), $this->SUT->getMessages());
    }

    public function testActionSubmit_importDataInvalid_returnFalse()
    {
        // Config mock
        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array('getUploadedFile'));
        $oConfig->expects($this->once())->method('getUploadedFile')->with('oxpsmodulesconfig_file')->will(
            $this->returnValue(array())
        );

        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Config::class, $oConfig);
        \OxidEsales\Eshop\Core\UtilsObject::setClassInstance(\OxidEsales\Eshop\Core\Config::class, $oConfig);

        /**
        modConfig::setRequestParameter('oxpsmodulesconfig_modules', array('my_module'));
        modConfig::setRequestParameter('oxpsmodulesconfig_settings', array('version' => 1, 'extend' => 1));
        modConfig::setRequestParameter('oxpsmodulesconfig_import', 1);
         */
        $this->setRequestParameter('oxpsmodulesconfig_modules', array('my_module'));
        $this->setRequestParameter('oxpsmodulesconfig_settings', array('version' => 1, 'extend' => 1));
        $this->setRequestParameter('oxpsmodulesconfig_import', 1);

        // Request validator instance mock
        $oValidator = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class,
            array( 'validateRequestData', 'validateImportData')
        );
        $oValidator->expects($this->once())->method('validateRequestData')
            ->with(
                array(
                    'modules'  => array('my_module'),
                    'settings' => array('version', 'extend'),
                    'action'   => 'import'
                )
            )
            ->will($this->returnValue(true));
        $oValidator->expects($this->once())->method('validateImportData')->with(array())->will(
            $this->returnValue(false)
        );

        \OxidEsales\Eshop\Core\Registry::set(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, $oValidator);

        // Configuration data transfer handler mock
        $oConfigTransfer = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class,
            array( 'backupToFile', 'setImportDataFromFile', 'importData', 'getImportErrors')
        );
        $oConfigTransfer->expects($this->never())->method('backupToFile');
        $oConfigTransfer->expects($this->never())->method('setImportDataFromFile');
        $oConfigTransfer->expects($this->never())->method('importData');
        $oConfigTransfer->expects($this->never())->method('getImportErrors');

        oxTestModules::addModuleObject(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, $oConfigTransfer);
        //todo: Lines #323 and #324 do not make SUT->getConfig() return $oConfig. So we have implemented the following hack.
        $this->SUT->setConfig($oConfig);
        $this->SUT->actionSubmit();
        $this->assertSame('import', $this->SUT->getAction());
        $this->assertSame(array(), $this->SUT->getMessages());
    }

    public function testActionSubmit_importFailed_setError()
    {
        // Config mock
        $oConfig = $this->getMock('oxConfig', array('getUploadedFile'));
        $oConfig->expects($this->once())->method('getUploadedFile')->with('oxpsmodulesconfig_file')->will(
            $this->returnValue(
                array(
                    'error'    => '',
                    'type'     => 'application/octet-stream',
                    'tmp_name' => '/path/to/good_file.json'
                )
            )
        );

        oxRegistry::set('oxConfig', $oConfig);

        $this->setRequestParameter('oxpsmodulesconfig_modules', array('my_module'));
        $this->setRequestParameter('oxpsmodulesconfig_settings', array('version' => 1, 'extend' => 1));
        $this->setRequestParameter('oxpsmodulesconfig_import', 1);

        // Request validator instance mock
        $oValidator = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class,
            array( 'validateRequestData', 'validateImportData', 'addErrors')
        );
        $oValidator->expects($this->once())->method('validateRequestData')
            ->with(
                array(
                    'modules'  => array('my_module'),
                    'settings' => array('version', 'extend'),
                    'action'   => 'import'
                )
            )
            ->will($this->returnValue(true));
        $oValidator->expects($this->once())->method('validateImportData')
            ->with(
                array(
                    'error'    => '',
                    'type'     => 'application/octet-stream',
                    'tmp_name' => '/path/to/good_file.json'
                )
            )
            ->will($this->returnValue(true));
        $oValidator->expects($this->once())->method('addErrors')->with(
            array('ERR_IMPORT_FAILURE_!', 'ERR-2')
        );

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, $oValidator);

        // Configuration data transfer handler mock
        $oConfigTransfer = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class,
            array( 'backupToFile', 'setImportDataFromFile', 'importData', 'getImportErrors')
        );
        $oConfigTransfer->expects($this->once())->method('backupToFile')
            ->with(
                array(
                    'modules'  => array('my_module', 'good_extension'),
                    'settings' => array('version', 'extend', 'files'),
                    'action'   => ''
                ),
                'full_backup'
            )->will($this->returnValue(true));
        $oConfigTransfer->expects($this->once())->method('setImportDataFromFile')->with(
            array(
                'error'    => '',
                'type'     => 'application/octet-stream',
                'tmp_name' => '/path/to/good_file.json'
            )
        );
        $oConfigTransfer->expects($this->once())->method('importData')
            ->with(
                array(
                    'modules'  => array('my_module'),
                    'settings' => array('version', 'extend'),
                    'action'   => 'import'
                )
            )
            ->will($this->returnValue(false));
        $oConfigTransfer->expects($this->once())->method('getImportErrors')->will(
            $this->returnValue(array('ERR_IMPORT_FAILURE_!', 'ERR-2'))
        );

        oxTestModules::addModuleObject(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, $oConfigTransfer);

        // Content model mock
        $oContent = $this->getMock(\OxidProfessionalServices\ModulesConfig\Model\Content::class, array( 'getModulesList', 'getSettingsList'));
        $oContent->expects($this->once())->method('getModulesList')->will(
            $this->returnValue(
                array(
                    'my_module'      => (object) array('version' => '1.0.0'),
                    'good_extension' => (object) array('version' => '0.2.5'),
                )
            )
        );
        $oContent->expects($this->once())->method('getSettingsList')->will(
            $this->returnValue(
                array(
                    'version' => 'Versions',
                    'extend'  => 'Extended classes',
                    'files'   => 'New classes',
                )
            )
        );

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Model\Content::class, $oContent);

        //todo: Line #380 does not make SUT->getConfig() return $oConfig so we fix this with the following hack for now:
        $this->SUT->setConfig($oConfig);
        $this->SUT->actionSubmit();
        $this->assertSame('import', $this->SUT->getAction());
        $this->assertSame(array('OXPS_MODULESCONFIG_MSG_BACKUP_SUCCESS'), $this->SUT->getMessages());
    }

    public function testActionSubmit_importSuccess()
    {
        // Config mock
        $oConfig = $this->getMock('oxConfig', array('getUploadedFile'));
        $oConfig->expects($this->once())->method('getUploadedFile')->with('oxpsmodulesconfig_file')->will(
            $this->returnValue(
                array(
                    'error'    => '',
                    'type'     => 'application/octet-stream',
                    'tmp_name' => '/path/to/good_file.json'
                )
            )
        );

        oxRegistry::set('oxConfig', $oConfig);

        $this->setRequestParameter('oxpsmodulesconfig_modules', array('my_module'));
        $this->setRequestParameter('oxpsmodulesconfig_settings', array('version' => 1, 'extend' => 1));
        $this->setRequestParameter('oxpsmodulesconfig_import', 1);

        // Request validator instance mock
        $oValidator = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class,
            array( 'validateRequestData', 'validateImportData', 'addErrors')
        );
        $oValidator->expects($this->once())->method('validateRequestData')
            ->with(
                array(
                    'modules'  => array('my_module'),
                    'settings' => array('version', 'extend'),
                    'action'   => 'import'
                )
            )
            ->will($this->returnValue(true));
        $oValidator->expects($this->once())->method('validateImportData')
            ->with(
                array(
                    'error'    => '',
                    'type'     => 'application/octet-stream',
                    'tmp_name' => '/path/to/good_file.json'
                )
            )
            ->will($this->returnValue(true));
        $oValidator->expects($this->never())->method('addErrors');

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Core\RequestValidator::class, $oValidator);

        // Configuration data transfer handler mock
        $oConfigTransfer = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class,
            array( 'backupToFile', 'setImportDataFromFile', 'importData', 'getImportErrors')
        );
        $oConfigTransfer->expects($this->once())->method('backupToFile')
            ->with(
                array(
                    'modules'  => array('my_module', 'good_extension'),
                    'settings' => array('version', 'extend', 'files'),
                    'action'   => ''
                ),
                'full_backup'
            )->will($this->returnValue(true));
        $oConfigTransfer->expects($this->once())->method('setImportDataFromFile')->with(
            array(
                'error'    => '',
                'type'     => 'application/octet-stream',
                'tmp_name' => '/path/to/good_file.json'
            )
        );
        $oConfigTransfer->expects($this->once())->method('importData')
            ->with(
                array(
                    'modules'  => array('my_module'),
                    'settings' => array('version', 'extend'),
                    'action'   => 'import'
                )
            )
            ->will($this->returnValue(true));
        $oConfigTransfer->expects($this->never())->method('getImportErrors');

        oxTestModules::addModuleObject(\OxidProfessionalServices\ModulesConfig\Core\ConfigTransfer::class, $oConfigTransfer);

        // Content model mock
        $oContent = $this->getMock(\OxidProfessionalServices\ModulesConfig\Model\Content::class, array( 'getModulesList', 'getSettingsList'));
        $oContent->expects($this->once())->method('getModulesList')->will(
            $this->returnValue(
                array(
                    'my_module'      => (object) array('version' => '1.0.0'),
                    'good_extension' => (object) array('version' => '0.2.5'),
                )
            )
        );
        $oContent->expects($this->once())->method('getSettingsList')->will(
            $this->returnValue(
                array(
                    'version' => 'Versions',
                    'extend'  => 'Extended classes',
                    'files'   => 'New classes',
                )
            )
        );

        oxRegistry::set(\OxidProfessionalServices\ModulesConfig\Model\Content::class, $oContent);

        // Module instance mock
        $oModule = $this->getMock('oxpsModulesConfigModule', array('clearTmp'));
        //$oModule->expects($this->once())->method('clearTmp');

        oxTestModules::addModuleObject('oxpsModulesConfigModule', $oModule);
        //todo: Line 494 does not make SUT->getConfig() return $oConfig so we fix this with the following hack for now:
        $this->SUT->setConfig($oConfig);
        $this->SUT->actionSubmit();
        $this->assertSame('import', $this->SUT->getAction());
        $this->assertSame(
            array('OXPS_MODULESCONFIG_MSG_BACKUP_SUCCESS', 'OXPS_MODULESCONFIG_MSG_IMPORT_SUCCESS'),
            $this->SUT->getMessages()
        );
    }
}
