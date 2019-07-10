<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Module\Setup\Handler;

use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Module\Setup\Handler\ClassExtensionsModuleSettingHandler;
use PHPUnit\Framework\TestCase;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;

/**
 * @internal
 */
class ClassExtensionsModuleSettingHandlerTest extends TestCase
{
    public function testHandlingOnModuleActivation()
    {
        $shopConfigurationSettingBeforeHandling = new ShopConfigurationSetting();
        $shopConfigurationSettingBeforeHandling
            ->setValue([
                'alreadyExistentModuleId' => ['extensionClass'],
            ]);

        $shopConfigurationSettingDao = $this->getMockBuilder(ShopConfigurationSettingDaoInterface::class)->getMock();
        $shopConfigurationSettingDao
            ->method('get')
            ->willReturn($shopConfigurationSettingBeforeHandling);

        $shopConfigurationSettingAfterHandling = new ShopConfigurationSetting();
        $shopConfigurationSettingAfterHandling
            ->setValue([
                'alreadyExistentModuleId' => ['extensionClass'],
                'newModuleId'             => ['moduleExtensionClass', 'anotherModuleExtensionClass'],
            ]);

        $shopConfigurationSettingDao
            ->expects($this->once())
            ->method('save')
            ->with($shopConfigurationSettingAfterHandling);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('newModuleId');

        $classExtensions =      [
            'originalClass'         => 'moduleExtensionClass',
            'anotherOriginalClass'  => 'anotherModuleExtensionClass',
        ];

        foreach ($classExtensions as $classNamespace => $moduleNamespace) {
            $moduleConfiguration->addClassExtension(new ClassExtension($classNamespace, $moduleNamespace));
        }

        $handler = new ClassExtensionsModuleSettingHandler($shopConfigurationSettingDao);
        $handler->handleOnModuleActivation($moduleConfiguration, 1);
    }

    public function testHandlingOnModuleDeactivation()
    {
        $shopConfigurationSettingBeforeHandling = new ShopConfigurationSetting();
        $shopConfigurationSettingBeforeHandling
            ->setValue([
                'moduleIdToDeactivate' => ['extensionClass'],
                'anotherModuleId'      => ['anotherExtensionClass'],
            ]);

        $shopConfigurationSettingDao = $this->getMockBuilder(ShopConfigurationSettingDaoInterface::class)->getMock();
        $shopConfigurationSettingDao
            ->method('get')
            ->willReturn($shopConfigurationSettingBeforeHandling);

        $shopConfigurationSettingAfterHandling = new ShopConfigurationSetting();
        $shopConfigurationSettingAfterHandling
            ->setValue([
                'anotherModuleId' => ['anotherExtensionClass'],
            ]);

        $shopConfigurationSettingDao
            ->expects($this->once())
            ->method('save')
            ->with($shopConfigurationSettingAfterHandling);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('moduleIdToDeactivate');
        $moduleConfiguration->addClassExtension(
            new ClassExtension(
                '',
                ''
            )
        );

        $handler = new ClassExtensionsModuleSettingHandler($shopConfigurationSettingDao);
        $handler->handleOnModuleDeactivation($moduleConfiguration, 1);
    }
}
