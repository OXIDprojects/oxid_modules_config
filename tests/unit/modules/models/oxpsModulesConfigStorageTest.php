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
 * Class oxpsModulesConfigStorageTest
 * Tests for model oxpsModulesConfigStorage.
 *
 * @see oxpsModulesConfigStorage
 */
class oxpsModulesConfigStorageTest extends OxidTestCase
{

    /**
     * Subject under the test.
     *
     * @var oxpsModulesConfigStorage
     */
    protected $SUT;


    /**
     * Set SUT state before test.
     */
    public function setUp()
    {
        parent::setUp();

        $this->SUT = $this->getMock(
            \OxidProfessionalServices\ModulesConfig\Model\Storage::class,
            array(
                'getShopConfVar',
                '_loadListFromShopConfig',
                '_loadFromBlocksTable',
                'saveShopConfVar',
                '_saveModuleSettings',
                '_saveModuleBlocks'
            )
        );
    }

    public function testLoad_invalidSetting_returnNull()
    {
        $this->SUT->expects($this->never())->method('getShopConfVar');
        $this->SUT->expects($this->never())->method('_loadListFromShopConfig');
        $this->SUT->expects($this->never())->method('_loadFromBlocksTable');

        $this->assertNull($this->SUT->load('other_module', 'cosmos'));
    }

    public function testLoad_missingShopConfigParam_returnNull()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModuleVersions')->will(
            $this->returnValue(null)
        );

        $this->assertNull($this->SUT->load('other_module', 'version'));
    }

    public function testLoad_missingModuleInShopConfigParam_returnNull()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModuleVersions')->will(
            $this->returnValue(array('my_module' => '1.2.3'))
        );

        $this->assertNull($this->SUT->load('other_module', 'version'));
    }

    public function testLoad_version_loadedFromShopConfigByModuleIdAsKey()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModuleVersions')->will(
            $this->returnValue(array('my_module' => '1.2.3', 'other_module' => '8.8.8'))
        );

        $this->assertSame('8.8.8', $this->SUT->load('other_module', 'version'));
    }

    public function testLoad_extend_loadedFromShopConfigAndUseAllModulesData()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModules')->will(
            $this->returnValue(
                array(
                    'my_module'    => array('oxarticle' => 'my/module/myoxarticle'),
                    'other_module' => array('oxarticle' => 'other/module/otheroxarticle')
                )
            )
        );

        $this->assertSame(
            array(
                'my_module'    => array('oxarticle' => 'my/module/myoxarticle'),
                'other_module' => array('oxarticle' => 'other/module/otheroxarticle')
            ),
            $this->SUT->load('my_module', 'extend')
        );
    }

    public function testLoad_files_loadedFromShopConfigByModuleIdAsKey()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModuleFiles')->will(
            $this->returnValue(
                array(
                    'my_module'    => array('myitem' => 'my/module/myitem'),
                    'other_module' => array('otheritem' => 'other/module/otheritem')
                )
            )
        );

        $this->assertSame(array('myitem' => 'my/module/myitem'), $this->SUT->load('my_module', 'files'));
    }

    public function testLoad_templates_loadedFromShopConfigByModuleIdAsKey()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModuleTemplates')->will(
            $this->returnValue(
                array(
                    'my_module'    => array(
                        'page' => 'my/module/page',
                        'list' => 'mt/module/list'
                    ),
                    'other_module' => array('pagetwo' => 'other/module/pagetwo')
                )
            )
        );

        $this->assertSame(
            array(
                'page' => 'my/module/page',
                'list' => 'mt/module/list'
            ),
            $this->SUT->load('my_module', 'templates')
        );
    }

    public function testLoad_blocks_callForModuleBlocksLoader()
    {
        $this->SUT->expects($this->never())->method('getShopConfVar');
        $this->SUT->expects($this->once())->method('_loadFromBlocksTable')->with('my_module')->will(
            $this->returnValue(array(0 => array('block' => 'my_block')))
        );

        $this->assertSame(array(0 => array('block' => 'my_block')), $this->SUT->load('my_module', 'blocks'));
    }

    public function testLoad_settings_callForModuleSettingsLoader()
    {
        $this->SUT->expects($this->never())->method('getShopConfVar');
        $this->SUT->expects($this->once())->method('_loadListFromShopConfig')->with('my_module')->will(
            $this->returnValue(array(0 => array('setting' => 'my_var')))
        );

        $this->assertSame(array(0 => array('setting' => 'my_var')), $this->SUT->load('my_module', 'settings'));
    }

    public function testLoad_events_loadedFromShopConfigByModuleIdAsKey()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModuleEvents')->will(
            $this->returnValue(
                array(
                    'my_module'    => array(),
                    'other_module' => array('onActivate' => 'myModule::onActivate')
                )
            )
        );

        $this->assertSame(array(), $this->SUT->load('my_module', 'events'));
    }


    public function testSave_invalidSetting_nothingIsSaved()
    {
        $this->SUT->expects($this->never())->method('getShopConfVar');
        $this->SUT->expects($this->never())->method('saveShopConfVar');
        $this->SUT->expects($this->never())->method('_saveModuleSettings');
        $this->SUT->expects($this->never())->method('_saveModuleBlocks');

        $this->SUT->save('new_module', 'advanced_settings', '_special_val_');
    }

    public function testSave_version_dataSavedToShopConfigByTheModuleId()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModuleVersions')->will(
            $this->returnValue(array('my_module' => '1.4.2', 'other_module' => '0.0.1 beta'))
        );
        $this->SUT->expects($this->once())->method('saveShopConfVar')->with(
            'arr',
            'aModuleVersions',
            array(
                'my_module'    => '1.4.2',
                'other_module' => '0.0.1 beta',
                'new_module'   => '1.0.0'
            )
        );

        $this->SUT->save('new_module', 'version', '1.0.0');
    }

    public function testSave_extend_dataSavedToShopConfigForAllModulesGlobally()
    {
        $this->SUT->expects($this->never())->method('getShopConfVar');
        $this->SUT->expects($this->once())->method('saveShopConfVar')->with(
            'arr',
            'aModules',
            array('my_module' => array('oxarticle' => 'my/myarticle'))
        );

        $this->SUT->save('new_module', 'extend', array('my_module' => array('oxarticle' => 'my/myarticle')));
    }

    public function testSave_files_dataSavedToShopConfigByTheModuleId()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModuleFiles')->will(
            $this->returnValue(
                array(
                    'my_module'    => array('myfile' => 'my/file.php'),
                    'other_module' => array()
                )
            )
        );
        $this->SUT->expects($this->once())->method('saveShopConfVar')->with(
            'arr',
            'aModuleFiles',
            array(
                'my_module'    => array('new_file' => 'my/new/file.php'),
                'other_module' => array()
            )
        );

        $this->SUT->save('my_module', 'files', array('new_file' => 'my/new/file.php'));
    }

    public function testSave_templates_dataSavedToShopConfigByTheModuleId()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModuleTemplates')->will(
            $this->returnValue(
                array(
                    'my_module'    => array('page.tpl' => 'my/page.tpl'),
                    'other_module' => array('list.tpl' => 'module/list.tpl')
                )
            )
        );
        $this->SUT->expects($this->once())->method('saveShopConfVar')->with(
            'arr',
            'aModuleTemplates',
            array(
                'my_module'    => array('page.tpl' => 'my/module/views_page.tpl'),
                'other_module' => array('list.tpl' => 'module/list.tpl')
            )
        );

        $this->SUT->save('my_module', 'templates', array('page.tpl' => 'my/module/views_page.tpl'));
    }

    public function testSave_blocks_callModuleBlocksSaveMethod()
    {
        $this->SUT->expects($this->never())->method('getShopConfVar');
        $this->SUT->expects($this->never())->method('saveShopConfVar');
        $this->SUT->expects($this->once())->method('_saveModuleBlocks')->with(
            'my_module',
            array(
                0 => array('block' => 'my_block'),
                1 => array('block' => 'other_block'),
            )
        );

        $this->SUT->save(
            'my_module',
            'blocks',
            array(
                0 => array('block' => 'my_block'),
                1 => array('block' => 'other_block'),
            )
        );
    }

    public function testSave_settings_callModuleBlocksSaveMethod()
    {
        $this->SUT->expects($this->never())->method('getShopConfVar');
        $this->SUT->expects($this->never())->method('saveShopConfVar');
        $this->SUT->expects($this->once())->method('_saveModuleSettings')->with(
            'my_module',
            array(0 => array('setting' => '1'), 1 => array('other_setting' => ''))
        );

        $this->SUT->save(
            'my_module',
            'settings',
            array(0 => array('setting' => '1'), 1 => array('other_setting' => ''))
        );
    }

    public function testSave_events_dataSavedToShopConfigByTheModuleId()
    {
        $this->SUT->expects($this->once())->method('getShopConfVar')->with('aModuleEvents')->will(
            $this->returnValue(
                array(
                    'my_module'    => array(),
                    'other_module' => array('onDeactivate' => 'otherModule::FormatC')
                )
            )
        );
        $this->SUT->expects($this->once())->method('saveShopConfVar')->with(
            'arr',
            'aModuleEvents',
            array(
                'my_module'    => array('onActivate' => 'myModule::Activation', 'onDeactivate' => ''),
                'other_module' => array('onDeactivate' => 'otherModule::FormatC')
            )
        );

        $this->SUT->save('my_module', 'events', array('onActivate' => 'myModule::Activation', 'onDeactivate' => ''));
    }
}
