<?php

namespace OxidProfessionalServices\ModulesConfig\Core;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;

abstract class CommandBase
{
    /**
     * @var bool Should the command run for all shops?
     */
    protected $allShops = true;

    /**
     * @var array Shop-IDs the command has to run on
     */
    protected $shops = [];

    /**
     * @var string The environment we are working on.
     */
    protected $sEnv;

    /**
     * @var OutputInterface $output The output stream, where to write the configuration.
     */
    protected $output;

    /*
     * @var InputInterface $oInput The input stream with arguments
     */
    protected $input;

    /**
     * @var array Configuration loaded from file
     */
    protected $aConfiguration;

    /**
     * @var array The configuration of the environment.
     */
    protected $aEnvConfig;

    /**
     * @var string
     */
    protected $sNameForMetaData = "Meta";

    /**
     * @var
     */
    protected $aDefaultConfig;

    /**
     * @var string
     */
    protected $sNameForGeneralShopSettings = "GeneralShopSettings";

    /**
     * @var OutputInterface
     */
    protected $debugOutput;

    /**
     * CommandBase constructor.
     *
     * @param OutputInterface $output
     * @param InputInterface  $input
     */
    public function __construct(OutputInterface $output, InputInterface $input)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Sets output stream, gets environment from commandline, init configuration and set debug output stream.
     */
    protected function init()
    {
        if (!$this->input->getOption('env')) {
            $this->output->writeLn('CAUTION: no environment given, please specify it like so:');
            $this->output->writeLn('config:[import|export] --env=<ENVIRONMENT_NAME>');
            $this->output->writeLn('aborting');
            exit;
        }
        $this->sEnv = $this->input->getOption('env');
        if ($this->input->hasOption('shop') && $this->input->getOption('shop')) {
            $this->shops = array_map('intval', explode(',', $this->input->getOption('shop')));
            $this->allShops = false;
        }
        $this->setDebugOutput();
        $this->initConfiguration();
        $aConfigIntersect = array_intersect($this->aConfiguration['excludeFields'], $this->aConfiguration['envFields']);
        if (count($aConfigIntersect) > 0) {
            $this->getDebugOutput()->writeLn(
                "CAUTION: excludeFields and envFields are not disjoint! " . print_r($aConfigIntersect, true)
            );
        }
    }

    /**
     * Getter for the directory where the shop configuration should be stored.
     *
     * @return string
     */
    protected function getConfigDir()
    {
        return $this->aConfiguration['dir'];
    }

    /**
     * Getter for the environment directory where environment specific config values are stored.
     *
     * @return null|string
     */
    protected function getEnvironmentConfigDir()
    {
        $sDir = null;

        if ($this->sEnv) {
            $sDir = isset($this->aEnvConfig["dir"]) ? $this->aEnvConfig["dir"] : false;
            if (!$sDir) {
                $sDir = $this->getConfigDir() . '/' . $this->sEnv;
            }
            if (!is_readable($sDir)) {
                $this->output->writeLn('There is no such ' . $sDir . ' config dir. stopping');
                exit;
            }
        }

        return $sDir;
    }

    /**
     * Getter for the full file name of the shop config file.
     *
     * @return string
     */
    protected function getShopsConfigFileName()
    {
        return $this->aConfiguration['dir'] . '/shops.' . $this->getFileExt();
    }

    /**
     * Init configuration from file
     *
     * It is been done only once. It will be stored as object property
     * after first call and will return it.
     *
     * @throws \oxFileException
     *
     */
    protected function initConfiguration()
    {
        $sConfigurationsDir = $this->_getConfigurationDirectoryPath();
        if ($this->aConfiguration === null) {
            $this->aConfiguration = $this->_getModuleSettings();
        }
        $exclude = $this->aConfiguration['excludeFields'];
        $this->aConfiguration['excludeDeep'] = array_filter($exclude,'is_array');

        $aAllEnvConfigs       = $this->aConfiguration['env'];
        $sFilename            = $sConfigurationsDir . 'defaultconfig' . DIRECTORY_SEPARATOR . 'defaults.yaml';
        $this->aDefaultConfig = $this->readConfigValues($sFilename, 'yaml');
        $aEnvConfig           = isset($aAllEnvConfigs[$this->sEnv]) ? $aAllEnvConfigs[$this->sEnv] : null;
        $this->aEnvConfig     = $aEnvConfig;
    }

    /**
     * @todo: is it necessary to activate the module to make the path settings?
     *
     * @return array
     */
    protected function _getModuleSettings()
    {
        $aModuleSettings           = array();
        $sPathToModuleSettingsFile = $this->_getModuleSettingsFilePath();
        if (file_exists($sPathToModuleSettingsFile)) {
            $aModuleSettings = require $sPathToModuleSettingsFile;
        }

        return $aModuleSettings;
    }

    /**
     * @return string
     *
     * @throws \oxFileException
     */
    protected function _getModuleSettingsFilePath()
    {
        $sConfigurationDirectoryPath = $this->_getConfigurationDirectoryPath();
        $sModuleSettingsFilePath     = $sConfigurationDirectoryPath . 'oxpsconfigmodulesettings.php';
        if (!is_file($sModuleSettingsFilePath) || !is_readable($sModuleSettingsFilePath)) {
            /** @var \oxFileException $oEx */
            $oEx = oxNew('oxFileException');
            $oEx->setMessage("Requested file does not exist: " . $sModuleSettingsFilePath);
            throw $oEx;
        }

        return $sModuleSettingsFilePath;
    }

    /**
     * @return string
     *
     * @throws \oxFileException
     */
    protected function _getConfigurationDirectoryPath()
    {
        $oConfig                             = \oxRegistry::getConfig();
        $sPathToThisModule                   = $oConfig->getModulesDir(
            ) . 'oxps' . DIRECTORY_SEPARATOR . 'modulesconfig' . DIRECTORY_SEPARATOR;
        $sRelativeConfigurationDirectoryPath = $oConfig->getConfigParam(
            'OXPS_MODULESCONFIG_SETTING_CONFIGURATION_DIRECTORY'
        );

        // Prevent empty result when parameter is not configured yet (in order to find a working configuration).
        if (!isset($sRelativeConfigurationDirectoryPath)) {
            $sRelativeConfigurationDirectoryPath = 'configurations';
        }

        if (is_string($sRelativeConfigurationDirectoryPath)) {
            $sRelativeConfigurationDirectoryPath = trim($sRelativeConfigurationDirectoryPath, '/');
        }
        $sPathToModuleSettingsFile = $sPathToThisModule . $sRelativeConfigurationDirectoryPath . DIRECTORY_SEPARATOR;
        if (!is_dir($sPathToModuleSettingsFile)) {
            /** @var \oxFileException $oEx */
            $oEx = oxNew("oxFileException");
            $oEx->setMessage("Requested directory does not exist: " . $sPathToModuleSettingsFile);
            throw $oEx;
        }

        return $sPathToModuleSettingsFile;
    }

    /**
     * Setter for the debug output stream.
     */
    protected function setDebugOutput()
    {
        $oDebugOutput       = ($this->output->getVerbosity() & OutputInterface::VERBOSITY_VERBOSE) == OutputInterface::VERBOSITY_VERBOSE ? $this->output : oxNew(NullOutput::class);
        $this->debugOutput = $oDebugOutput;
    }

    /**
     * Getter for the debug output stream.
     *
     * @return OutputInterface
     */
    protected function getDebugOutput()
    {
        return $this->debugOutput;
    }

    /**
     * Getter for the file extension.
     *
     * @return string The extension of the file.
     */
    protected function getFileExt()
    {
        return $this->getExportFormat();
    }

    /**
     * Getter for the output format.
     *
     * @return string The type of output.
     */
    protected function getExportFormat()
    {
        return $this->aConfiguration['type'];
    }

    /**
     * Read configuration from file
     * It is being done only once. It will be stored as object property
     *
     * @param string $sFileName Name/path to the config file, that configure this config ex/importer
     * @param null   $sType
     *
     * @throws \Exception
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     *
     * @return array|mixed
     */
    protected function readConfigValues($sFileName, $sType = null)
    {
        $this->output->writeLn("Reading shop config file $sFileName");

        if ($sType == null) {
            $sType = $this->aConfiguration['type'];
        }
        $sFileContent = file_get_contents($sFileName);

        if ($sType == 'json') {
            $aResults = json_decode($sFileContent, true);
            $error    = json_last_error();
            if ($error !== JSON_ERROR_NONE) {
                throw new \Exception("invalid JSON in $sFileName $error");
            }
        } elseif ($sType == 'yaml') {
            $aResults = Yaml::parse($sFileContent);
        } else {
            throw new \Exception("unsuported config type" . $sType);
        }

        return $aResults;
    }
}
