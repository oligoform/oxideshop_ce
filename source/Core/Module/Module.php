<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\Module;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Adapter\Exception\ModuleConfigurationNotFoundException;
use OxidEsales\EshopCommunity\Internal\Application\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleSetting;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Module\MetaData\Service\MetaDataProvider;
use OxidEsales\EshopCommunity\Internal\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleConfiguration;

/**
 * Module class.
 *
 * @deprecated since v6.4.0 (2019-03-22); Use service 'OxidEsales\EshopCommunity\Internal\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface'.
 * @internal Do not make a module extension for this class.
 * @see      https://oxidforge.org/en/core-oxid-eshop-classes-must-not-be-extended.html
 */
class Module extends \OxidEsales\Eshop\Core\Base
{
    /**
     * Metadata version as defined in metadata.php
     */
    protected $metaDataVersion;

    /**
     * @return mixed
     */
    public function getMetaDataVersion()
    {
        return $this->metaDataVersion;
    }

    /**
     * @param mixed $metaDataVersion
     */
    public function setMetaDataVersion($metaDataVersion)
    {
        $this->metaDataVersion = $metaDataVersion;
    }

    /**
     * Modules info array
     *
     * @var array
     */
    protected $_aModule = [];

    /**
     * Defines if module has metadata file or not
     *
     * @var bool
     */
    protected $_blMetadata = false;

    /**
     * Defines if module is registered in metadata or legacy storage
     *
     * @var bool
     */
    protected $_blRegistered = false;

    /**
     * Set passed module data
     *
     * @param array $aModule module data
     */
    public function setModuleData($aModule)
    {
        $this->_aModule = $aModule;
    }

    /**
     * Get the modules metadata array
     *
     * @return  array Module meta data array
     */
    public function getModuleData()
    {
        return $this->_aModule;
    }

    /**
     * Load module info
     *
     * @param string $moduleId
     *
     * @return bool
     */
    public function load($moduleId)
    {
        try {
            $this->_aModule['id'] = $moduleId;

            $container = ContainerFactory::getInstance()->getContainer();
            $moduleConfiguration = $container->get(ModuleConfigurationDaoBridgeInterface::class)->get($moduleId);
            $sMetadataPath = $this->getModuleFullPath($moduleId) . "/metadata.php";

            if (is_readable($sMetadataPath) && $moduleConfiguration) {
                $this->_aModule = $this->convertModuleConfigurationToArray($moduleConfiguration);
                $this->includeModuleMetaData($sMetadataPath);

                $this->_blRegistered = true;
                $this->_blMetadata = true;
                $this->_aModule['active'] = $this->isActive();

                return true;
            }
        } catch (ModuleConfigurationNotFoundException $e) {
            return false;
        }

        return false;
    }

    /**
     * Load module by dir name
     *
     * @param string $sModuleDir Module dir name
     *
     * @return bool
     */
    public function loadByDir($sModuleDir)
    {
        $sModuleId = null;
        $aModulePaths = $this->getModulePaths();

        if (is_array($aModulePaths)) {
            $sModuleId = array_search($sModuleDir, $aModulePaths);
        }

        // if no module id defined, using module dir as id
        if (!$sModuleId) {
            $sModuleId = $sModuleDir;
        }

        return $this->load($sModuleId);
    }

    /**
     * Get module description
     *
     * @return string
     */
    public function getDescription()
    {
        $iLang = \OxidEsales\Eshop\Core\Registry::getLang()->getTplLanguage();

        return $this->getInfo("description", $iLang);
    }

    /**
     * Get module title
     *
     * @return string
     */
    public function getTitle()
    {
        $iLang = \OxidEsales\Eshop\Core\Registry::getLang()->getTplLanguage();

        return $this->getInfo("title", $iLang);
    }

    /**
     * Get module ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->_aModule['id'];
    }

    /**
     * Returns array of module extensions.
     *
     * @return array
     */
    public function getExtensions()
    {
        $moduleConfiguration = $this
            ->getContainer()
            ->get(ModuleConfigurationDaoBridgeInterface::class)
            ->get($this->getId());

        $extensions = [];

        if ($moduleConfiguration->hasClassExtensionSetting()) {
            foreach ($moduleConfiguration->getClassExtensions() as $extension) {
                $extensions[$extension->getShopClassNamespace()] = $extension->getModuleExtensionClassNamespace();
            }
        }

        return $extensions;
    }

    /**
     * Returns associative array of module controller ids and corresponding classes.
     *
     * @return array
     */
    public function getControllers()
    {
        if (isset($this->_aModule['controllers']) && ! is_array($this->_aModule['controllers'])) {
            throw new \InvalidArgumentException('Value for metadata key "controllers" must be an array');
        }

        return isset($this->_aModule['controllers']) ? array_change_key_case($this->_aModule['controllers']) : [];
    }

    /**
     * @return array
     */
    public function getSmartyPluginDirectories()
    {
        if (isset($this->_aModule['smartyPluginDirectories']) && !is_array($this->_aModule['smartyPluginDirectories'])) {
            throw new \InvalidArgumentException('Value for metadata key "smartyPluginDirectories" must be an array');
        }

        return isset($this->_aModule['smartyPluginDirectories']) ? $this->_aModule['smartyPluginDirectories'] : [];
    }

    /**
     * Returns array of module PHP files.
     *
     * @return array
     */
    public function getFiles()
    {
        return isset($this->_aModule['files']) ? $this->_aModule['files'] : [];
    }

    /**
     * Get module ID
     *
     * @param string $module extension full path
     *
     * @return string
     */
    public function getIdByPath($module)
    {
        $moduleId = null;
        $moduleFile = $module;
        $moduleId = $this->getModuleIdByClassName($module);
        if (!$moduleId) {
            $modulePaths = $this->getModulePaths();

            if (is_array($modulePaths)) {
                foreach ($modulePaths as $id => $path) {
                    if (strpos($moduleFile, $path . "/") === 0) {
                        $moduleId = $id;
                    }
                }
            }
        }
        if (!$moduleId) {
            $moduleId = substr($moduleFile, 0, strpos($moduleFile, "/"));
        }
        if (!$moduleId) {
            $moduleId = $moduleFile;
        }

        return $moduleId;
    }

    /**
     * @deprecated since v6.0.0 (2017-03-21); Use self::getModuleIdByClassName()
     *
     * Get the module id of given extended class name or namespace.
     *
     * @param string $className
     *
     * @return string
     */
    public function getIdFromExtension($className)
    {
        return $this->getModuleIdByClassName($className);
    }

    /**
     * Get the module id for a given class name. If there are duplicates, the first module id will be returned.
     *
     * @param string $className
     *
     * @return string
     */
    public function getModuleIdByClassName($className)
    {
        $moduleId = '';

        foreach ($this->getShopConfiguration()->getModuleConfigurations() as $module) {
            if ($module->hasClassExtension($className)) {
                return $module->getId();
            }
        }

        return $moduleId;
    }

    /**
     * Get module info item. If second param is passed, will try
     * to get value according selected language.
     *
     * @param string $sName name of info item to retrieve
     * @param string $iLang language ID
     *
     * @return mixed
     */
    public function getInfo($sName, $iLang = null)
    {
        if (isset($this->_aModule[$sName])) {
            if ($iLang !== null && is_array($this->_aModule[$sName])) {
                $sValue = null;

                $sLang = \OxidEsales\Eshop\Core\Registry::getLang()->getLanguageAbbr($iLang);

                if (!empty($this->_aModule[$sName])) {
                    if (!empty($this->_aModule[$sName][$sLang])) {
                        $sValue = $this->_aModule[$sName][$sLang];
                    } elseif (!empty($this->_aModule['lang'])) {
                        // trying to get value according default language
                        $sValue = $this->_aModule[$sName][$this->_aModule['lang']];
                    } else {
                        // returning first array value
                        $sValue = reset($this->_aModule[$sName]);
                    }

                    return $sValue;
                }
            } else {
                return $this->_aModule[$sName];
            }
        }
    }

    /**
     * Check if extension is active
     *
     * @return bool
     */
    public function isActive()
    {
        if ($this->getId() === null) {
            return false;
        }

        $moduleActivationBridge = $this
            ->getContainer()
            ->get(ModuleActivationBridgeInterface::class);

        return $moduleActivationBridge->isActive(
            $this->getId(),
            Registry::getConfig()->getShopId()
        );
    }

    /**
     * Checks if has extend class.
     *
     * @return bool
     */
    public function hasExtendClass()
    {
        return !empty($this->getExtensions());
    }

    /**
     * Checks if module is registered in any way
     *
     * @return bool
     */
    public function isRegistered()
    {
        return $this->_blRegistered;
    }

    /**
     * Checks if module has metadata
     *
     * @return bool
     */
    public function hasMetadata()
    {
        return $this->_blMetadata;
    }

    /**
     * Get full path to module metadata file.
     *
     * @return string
     */
    public function getMetadataPath()
    {
        $sModulePath = $this->getModuleFullPath();
        if (substr($sModulePath, -1) != DIRECTORY_SEPARATOR) {
            $sModulePath .= DIRECTORY_SEPARATOR;
        }

        return $sModulePath . 'metadata.php';
    }

    /**
     * Get module dir
     *
     * @param string $sModuleId Module ID
     *
     * @return string
     */
    public function getModulePath($sModuleId = null)
    {
        if (!$sModuleId) {
            $sModuleId = $this->getId();
        }

        $aModulePaths = $this->getModulePaths();

        $sModulePath = (isset($aModulePaths[$sModuleId])) ? $aModulePaths[$sModuleId] : '';

        // if still no module dir, try using module ID as dir name
        if (!$sModulePath && is_dir($this->getConfig()->getModulesDir() . $sModuleId)) {
            $sModulePath = $sModuleId;
        }

        return $sModulePath;
    }

    /**
     * Returns full module path
     *
     * @param string $sModuleId
     *
     * @return string
     */
    public function getModuleFullPath($sModuleId = null)
    {
        if (!$sModuleId) {
            $sModuleId = $this->getId();
        }

        if ($sModuleDir = $this->getModulePath($sModuleId)) {
            return $this->getConfig()->getModulesDir() . $sModuleDir;
        }

        return false;
    }

    /**
     * Get module id's with path
     *
     * @return array
     */
    public function getModulePaths()
    {
        $moduleConfigurations = $this->getInstalledModuleConfigurations();
        $paths = [];
        foreach ($moduleConfigurations as $moduleConfiguration) {
            $paths[$moduleConfiguration->getId()] = $moduleConfiguration->getPath();
        }

        return $paths;
    }

    /**
     * Return templates affected by template blocks for given module id.
     *
     * @todo extract oxtplblocks query to ModuleTemplateBlockRepository
     *
     * @param string $sModuleId Module id
     *
     * @return array
     */
    public function getTemplates($sModuleId = null)
    {
        if (is_null($sModuleId)) {
            $sModuleId = $this->getId();
        }

        if (!$sModuleId) {
            return [];
        }

        $sShopId = $this->getConfig()->getShopId();

        return \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->getCol("SELECT oxtemplate FROM oxtplblocks WHERE oxmodule = '$sModuleId' AND oxshopid = '$sShopId'");
    }

    /**
     * Include data from metadata.php
     *
     * @param string $metadataPath Path to metadata.php
     */
    protected function includeModuleMetaData($metadataPath)
    {
        include $metadataPath;

        /**
         * metadata.php should include a variable called $sMetadataVersion
         */

        if (isset($sMetadataVersion)) {
            $this->setMetaDataVersion($sMetadataVersion);
        }
    }

    /**
     * @return array
     */
    private function getInstalledModuleConfigurations(): array
    {
        $shopConfiguration = $this->getShopConfiguration();

        return $shopConfiguration->getModuleConfigurations();
    }

    /**
     * @return ShopConfiguration
     */
    private function getShopConfiguration(): ShopConfiguration
    {
        $container = $this->getContainer();
        return $container->get(ShopConfigurationDaoBridgeInterface::class)->get();
    }

    /**
     * Convert ModuleConfiguration to Array
     *
     * @param ModuleConfiguration $configuration
     *
     * @return array
     */
    private function convertModuleConfigurationToArray(ModuleConfiguration $configuration): array
    {
        $data = [
            'id'          => $configuration->getId(),
            'version'     => $configuration->getVersion(),
            'title'       => $configuration->getTitle(),
            'description' => $configuration->getDescription(),
            'lang'        => $configuration->getLang(),
            'thumbnail'   => $configuration->getThumbnail(),
            'author'      => $configuration->getAuthor(),
            'url'         => $configuration->getUrl(),
            'email'       => $configuration->getEmail(),
        ];

        foreach ($this->convertModuleSettingsToArray($configuration) as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     *
     * @return array
     */
    private function convertModuleSettingsToArray(ModuleConfiguration $moduleConfiguration): array
    {
        $data = [];

        $data[MetaDataProvider::METADATA_EXTEND] = $this->convertClassExtensionsToArray($moduleConfiguration);

        foreach ($moduleConfiguration->getSettings() as $setting) {
            switch ($setting->getName()) {
                case ModuleSetting::CLASSES_WITHOUT_NAMESPACE:
                    $data[MetaDataProvider::METADATA_FILES] = $setting->getValue();
                    break;

                case ModuleSetting::TEMPLATE_BLOCKS:
                    $data[MetaDataProvider::METADATA_BLOCKS] = $setting->getValue();
                    break;

                case ModuleSetting::CONTROLLERS:
                    $data[MetaDataProvider::METADATA_CONTROLLERS] = $setting->getValue();
                    break;

                case ModuleSetting::EVENTS:
                    $data[MetaDataProvider::METADATA_EVENTS] = $setting->getValue();
                    break;

                case ModuleSetting::TEMPLATES:
                    $data[MetaDataProvider::METADATA_TEMPLATES] = $setting->getValue();
                    break;

                case ModuleSetting::SHOP_MODULE_SETTING:
                    $data[MetaDataProvider::METADATA_SETTINGS] = $setting->getValue();
                    break;

                case ModuleSetting::SMARTY_PLUGIN_DIRECTORIES:
                    $data[MetaDataProvider::METADATA_SMARTY_PLUGIN_DIRECTORIES] = $setting->getValue();
                    break;
            }
        }

        return $data;
    }

    private function convertClassExtensionsToArray(ModuleConfiguration $moduleConfiguration): array
    {
        $data = [];

        foreach ($moduleConfiguration->getClassExtensions() as $extension) {
            $data[$extension->getShopClassNamespace()] = $extension->getModuleExtensionClassNamespace();
        }

        return $data;
    }
}
