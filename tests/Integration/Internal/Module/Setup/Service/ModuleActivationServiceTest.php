<?php
declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ClassExtensionsChain;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleSetting;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Module\Path\ModulePathResolver;
use OxidEsales\EshopCommunity\Internal\Module\Path\ModulePathResolverInterface;
use OxidEsales\EshopCommunity\Internal\Module\Setup\Service\ModuleActivationServiceInterface;
use OxidEsales\EshopCommunity\Internal\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\Module\TestData\TestModule\SomeModuleService;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\TestContainerFactory;
use OxidEsales\TestingLibrary\Services\Library\DatabaseRestorer\DatabaseRestorer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;

/**
 * @internal
 */
class ModuleActivationServiceTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;
    private $shopId = 1;
    private $testModuleId = 'testModuleId';
    private $databaseRestorer;

    use ContainerTrait;

    public function setUp()
    {
        $this->container = $this->setupAndConfigureContainer();

        $this->databaseRestorer = new DatabaseRestorer();
        $this->databaseRestorer->dumpDB(__CLASS__);

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->databaseRestorer->restoreDB(__CLASS__);

        parent::tearDown();
    }

    public function testActivation()
    {
        $this->persistModuleConfiguration($this->getTestModuleConfiguration());

        $moduleStateService = $this->container->get(ModuleStateServiceInterface::class);
        $moduleActivationService = $this->container->get(ModuleActivationServiceInterface::class);

        $moduleActivationService->activate($this->testModuleId, $this->shopId);

        $this->assertTrue($moduleStateService->isActive($this->testModuleId, $this->shopId));

        $moduleActivationService->deactivate($this->testModuleId, $this->shopId);

        $this->assertFalse($moduleStateService->isActive($this->testModuleId, $this->shopId));
    }

    public function testSetAutoActiveInModuleConfiguration()
    {
        $this->persistModuleConfiguration($this->getTestModuleConfiguration());

        $moduleConfigurationDao = $this->container->get(ModuleConfigurationDaoInterface::class);
        $moduleActivationService = $this->container->get(ModuleActivationServiceInterface::class);

        $moduleActivationService->activate($this->testModuleId, $this->shopId);
        $moduleConfiguration = $moduleConfigurationDao->get($this->testModuleId, $this->shopId);

        $this->assertTrue($moduleConfiguration->isAutoActive());

        $moduleActivationService->deactivate($this->testModuleId, $this->shopId);
        $moduleConfiguration = $moduleConfigurationDao->get($this->testModuleId, $this->shopId);

        $this->assertFalse($moduleConfiguration->isAutoActive());
    }

    public function testClassExtensionChainUpdate()
    {
        $shopConfigurationSettingDao = $this->container->get(ShopConfigurationSettingDaoInterface::class);

        $moduleConfiguration = $this->getTestModuleConfiguration();
        $moduleConfiguration->addClassExtension(new ClassExtension('originalClassNamespace', 'moduleClassNamespace'));

        $this->persistModuleConfiguration($moduleConfiguration);

        $moduleActivationService = $this->container->get(ModuleActivationServiceInterface::class);
        $moduleActivationService->activate($this->testModuleId, $this->shopId);

        $moduleClassExtensionChain = $shopConfigurationSettingDao->get(
            ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS_CHAIN,
            $this->shopId
        );

        $this->assertSame(
            ['originalClassNamespace' => 'moduleClassNamespace'],
            $moduleClassExtensionChain->getValue()
        );

        $moduleActivationService->deactivate($this->testModuleId, $this->shopId);

        $moduleClassExtensionChain = $shopConfigurationSettingDao->get(
            ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS_CHAIN,
            $this->shopId
        );

        $this->assertSame(
            [],
            $moduleClassExtensionChain->getValue()
        );
    }

    public function testActivationOfModuleServices()
    {
        $moduleConfiguration = $this->getTestModuleConfiguration();
        $this->persistModuleConfiguration($moduleConfiguration);

        $moduleActivationService = $this->container->get(ModuleActivationServiceInterface::class);
        $moduleActivationService->activate($this->testModuleId, $this->shopId);

        $this->assertInstanceOf(
            SomeModuleService::class,
            $this->setupAndConfigureContainer()->get(SomeModuleService::class)
        );
    }

    /**
     * @return ShopAdapterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getModulePathResolverMock()
    {
        $modulePathResolverMock = $this
            ->getMockBuilder(ModulePathResolverInterface::class)
            ->getMock();

        $modulePathResolverMock
            ->method('getFullModulePathFromConfiguration')
            ->willReturn(__DIR__ . '/../../TestData/TestModule');

        return $modulePathResolverMock;
    }

    private function getTestModuleConfiguration(): ModuleConfiguration
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId($this->testModuleId);
        $moduleConfiguration->setPath('TestModule');

        $moduleConfiguration
            ->addSetting(new ModuleSetting(
                ModuleSetting::CONTROLLERS,
                [
                    'originalClassNamespace' => 'moduleClassNamespace',
                    'otherOriginalClassNamespace' => 'moduleClassNamespace',
                ]
            ))
            ->addSetting(new ModuleSetting(
                ModuleSetting::TEMPLATES,
                [
                    'originalTemplate' => 'moduleTemplate',
                    'otherOriginalTemplate' => 'moduleTemplate',
                ]
            ))
            ->addSetting(new ModuleSetting(
                ModuleSetting::SMARTY_PLUGIN_DIRECTORIES,
                [
                    'SmartyPlugins/directory1',
                    'SmartyPlugins/directory2',
                ]
            ))
            ->addSetting(new ModuleSetting(
                ModuleSetting::TEMPLATE_BLOCKS,
                [
                    [
                        'block'     => 'testBlock',
                        'position'  => '3',
                        'theme'     => 'flow_theme',
                        'template'  => 'extendedTemplatePath',
                        'file'      => 'filePath',
                    ],
                ]
            ))
            ->addClassExtension(
                new ClassExtension(
                    'originalClassNamespace','moduleClassNamespace'
                )
            )
            ->addClassExtension(
                new ClassExtension(
                    'otherOriginalClassNamespace','moduleClassNamespace'
                )
            )
            ->addSetting(new ModuleSetting(
                ModuleSetting::CLASSES_WITHOUT_NAMESPACE,
                [
                    'class1' => 'class1.php',
                    'class2' => 'class2.php',
                ]
            ))
            ->addSetting(new ModuleSetting(
                ModuleSetting::SHOP_MODULE_SETTING,
                [
                    [
                        'group' => 'frontend',
                        'name'  => 'grid',
                        'type'  => 'str',
                        'value' => 'row',
                    ],
                    [
                        'group' => 'frontend',
                        'name'  => 'array',
                        'type'  => 'arr',
                        'value' => ['1', '2'],
                    ],
                ]
            ));

        return $moduleConfiguration;
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     */
    private function persistModuleConfiguration(ModuleConfiguration $moduleConfiguration)
    {
        $chain = new ClassExtensionsChain();
        $chain->setChain([
            'originalClassNamespace' => ['moduleClassNamespace'],
        ]);

        $shopConfiguration = new ShopConfiguration();
        $shopConfiguration->setClassExtensionsChain($chain);
        $shopConfiguration->addModuleConfiguration($moduleConfiguration);

        $shopConfigurationDao = $this->container->get(ShopConfigurationDaoInterface::class);
        $shopConfigurationDao->save($shopConfiguration, $this->shopId, $this->getEnvironment());
    }

    /**
     * We need to replace services in the container with a mock
     *
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private function setupAndConfigureContainer()
    {
        $container = (new TestContainerFactory())->create();

        $container->set(ModulePathResolverInterface::class, $this->getModulePathResolverMock());
        $container->autowire(ModulePathResolverInterface::class, ModulePathResolver::class);

        $container->compile();

        return $container;
    }

    private function getEnvironment(): string
    {
        return $this->get(ContextInterface::class)->getEnvironment();
    }
}
