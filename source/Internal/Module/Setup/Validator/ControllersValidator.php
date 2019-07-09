<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Module\Setup\Validator;

use function is_array;

use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Common\Exception\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Module\Setup\Exception\ControllersDuplicationModuleConfigurationException;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleConfiguration\Controller;

/**
 * @internal
 */
class ControllersValidator implements ModuleConfigurationValidatorInterface
{
    /**
     * @var ShopAdapterInterface
     */
    private $shopAdapter;

    /**
     * @var ShopConfigurationSettingDaoInterface
     */
    private $shopConfigurationSettingDao;

    /**
     * ControllersValidator constructor.
     * @param ShopAdapterInterface                 $shopAdapter
     * @param ShopConfigurationSettingDaoInterface $shopConfigurationSettingDao
     */
    public function __construct(
        ShopAdapterInterface $shopAdapter,
        ShopConfigurationSettingDaoInterface $shopConfigurationSettingDao
    ) {
        $this->shopAdapter = $shopAdapter;
        $this->shopConfigurationSettingDao = $shopConfigurationSettingDao;
    }

    /**
     * @param ModuleConfiguration $configuration
     * @param int                 $shopId
     *
     * @throws ControllersDuplicationModuleConfigurationException
     */
    public function validate(ModuleConfiguration $configuration, int $shopId)
    {
        if ($configuration->hasControllerSetting()) {

            $shopControllerClassMap = $this->shopAdapter->getShopControllerClassMap();

            $controllerClassMap = array_merge(
                $shopControllerClassMap,
                $this->getModulesControllerClassMap($shopId)
            );

            $controllers = $this->convertControllerObjectToArray($configuration->getControllers());

            $this->validateForControllerKeyDuplication($controllers, $controllerClassMap);
            $this->validateForControllerNamespaceDuplication($controllers, $controllerClassMap);
        }
    }

    /**
     * @param int $shopId
     * @return array
     */
    private function getModulesControllerClassMap(int $shopId): array
    {
        $moduleControllersClassMap = [];

        try {
            $controllersGroupedByModule = $this
                ->shopConfigurationSettingDao
                ->get(ShopConfigurationSetting::MODULE_CONTROLLERS, $shopId);

            if (is_array($controllersGroupedByModule->getValue())) {
                foreach ($controllersGroupedByModule->getValue() as $moduleControllers) {
                    $moduleControllersClassMap = array_merge($moduleControllersClassMap, $moduleControllers);
                }
            }
        } catch (EntryDoesNotExistDaoException $exception) {
        }

        return $moduleControllersClassMap;
    }

    /**
     * @param Controller[] $controllers
     * @param array $controllerClassMap
     *
     * @throws ControllersDuplicationModuleConfigurationException
     */
    private function validateForControllerNamespaceDuplication(array $controllers, array $controllerClassMap)
    {
        $duplications = array_intersect(
            $controllers,
            $controllerClassMap
        );

        if (!empty($duplications)) {
            throw new ControllersDuplicationModuleConfigurationException(
                'Controller namespaces duplication: ' . implode(', ', $duplications)
            );
        }
    }

    /**
     * @param Controller[] $controllers
     * @param array $controllerClassMap
     *
     * @throws ControllersDuplicationModuleConfigurationException
     */
    private function validateForControllerKeyDuplication(array $controllers, array $controllerClassMap)
    {
        $duplications = array_intersect_key(
            $this->arrayKeysToLowerCase($controllers),
            $controllerClassMap
        );

        if (!empty($duplications)) {
            throw new ControllersDuplicationModuleConfigurationException(
                'Controller keys duplication: ' . implode(', ', $duplications)
            );
        }
    }

    /**
     * @param array $array
     * @return array
     */
    private function arrayKeysToLowerCase(array $array): array
    {
        return array_change_key_case($array, CASE_LOWER);
    }

    /**
     * @param Controller[] $controllers
     *
     * @return array
     */
    private function convertControllerObjectToArray(array $controllers): array
    {
        $output = [];

        foreach ($controllers as $controller) {
            $output [$controller->getId()] = $controller->getControllerClassNameSpace();
        }

        return $output;
    }
}
