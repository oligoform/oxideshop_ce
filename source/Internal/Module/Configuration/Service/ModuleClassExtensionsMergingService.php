<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Module\Configuration\Service;

use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ClassExtensionsChain;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleSetting;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ShopConfiguration;

/**
 * @internal
 */
class ModuleClassExtensionsMergingService implements ModuleClassExtensionsMergingServiceInterface
{

    /**
     * @param ShopConfiguration   $shopConfiguration
     * @param ModuleConfiguration $moduleConfiguration
     *
     * @return ClassExtensionsChain
     * @throws \OxidEsales\EshopCommunity\Internal\Adapter\Exception\ModuleConfigurationNotFoundException
     * @throws \OxidEsales\EshopCommunity\Internal\Module\Configuration\Exception\ExtensionNotInChainException
     */
    public function merge(
        ShopConfiguration $shopConfiguration,
        ModuleConfiguration $moduleConfiguration
    ): ClassExtensionsChain {
        $classExtensionChain = $shopConfiguration->getClassExtensionsChain();

        if ($moduleConfiguration->hasClassExtensionSetting()) {
            $classExtensionsToMerge = [];

            foreach ($moduleConfiguration->getClassExtensions() as $extension) {
                $classExtensionsToMerge [$extension->getShopClassNamespace()] = $extension->getModuleExtensionClassNamespace();
            }

            if ($shopConfiguration->hasModuleConfiguration($moduleConfiguration->getId())) {
                $existingModuleConfiguration = $shopConfiguration->getModuleConfiguration($moduleConfiguration->getId());

                $classExtensionChain = $this->compareClassExtensionsAndUpdateChain(
                    $existingModuleConfiguration,
                    $classExtensionsToMerge,
                    $classExtensionChain
                );
            } else {
                $extensions = [];
                foreach ($moduleConfiguration->getClassExtensions() as $extension) {
                    $extensions [$extension->getShopClassNamespace()] = $extension->getModuleExtensionClassNamespace();
                }
                $classExtensionChain->addExtensions($extensions);
            }
        }

        return $classExtensionChain;
    }

    /**
     * @param ModuleConfiguration  $existingModuleConfiguration
     * @param array                $classExtensionsToMerge
     * @param ClassExtensionsChain $chain
     *
     * @return ClassExtensionsChain
     * @throws \OxidEsales\EshopCommunity\Internal\Module\Configuration\Exception\ExtensionNotInChainException
     */
    private function compareClassExtensionsAndUpdateChain(
        ModuleConfiguration $existingModuleConfiguration,
        array $classExtensionsToMerge,
        ClassExtensionsChain $chain
    ): ClassExtensionsChain {
        if ($existingModuleConfiguration->hasClassExtensionSetting()) {
            $classExtensionsOfExistingModuleConfiguration = $existingModuleConfiguration
                ->getClassExtensions();
            $classExtensionChain = $chain->getChain();
            foreach ($classExtensionsOfExistingModuleConfiguration as $extension) {
                if (\array_key_exists($extension->getShopClassNamespace(), $classExtensionsToMerge)) {
                    if ($extension->getModuleExtensionClassNamespace() !== $classExtensionsToMerge[$extension->getShopClassNamespace()]) {
                        $classExtensionChain[$extension->getShopClassNamespace()] = $this->replaceExtendedClassAndKeepOrder(
                            $extension->getModuleExtensionClassNamespace(),
                            $classExtensionsToMerge[$extension->getShopClassNamespace()],
                            $classExtensionChain[$extension->getShopClassNamespace()]
                        );
                        $chain->setChain($classExtensionChain);
                    }
                } else {
                    $chain->removeExtension($extension->getShopClassNamespace(), $extension->getModuleExtensionClassNamespace());
                }
                unset($classExtensionsToMerge[$extension->getShopClassNamespace()]);
            }
        }
        $chain->addExtensions($classExtensionsToMerge);

        return $chain;
    }

    /**
     * Converts e.g. the chain [Class1, ClassOld, Class3] to [Class1, ClassNew, Class3]. Keeping the order is important
     * as the order can be changed in OXID eShop admin.
     *
     * @param string $classExtensionsExisting
     * @param string $classExtensionsToMerge
     * @param array  $classExtensionChainData
     *
     * @return array
     */
    private function replaceExtendedClassAndKeepOrder(string $classExtensionsExisting, string $classExtensionsToMerge, array $classExtensionChainData): array
    {
        return str_replace(
            $classExtensionsExisting,
            $classExtensionsToMerge,
            $classExtensionChainData
        );
    }
}
