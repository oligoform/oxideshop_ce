<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Test\Integration\Internal\Module\MetaData;

use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleSetting;
use OxidEsales\EshopCommunity\Internal\Module\MetaData\Exception\ModuleIdNotValidException;
use OxidEsales\EshopCommunity\Internal\Module\MetaData\Exception\UnsupportedMetaDataKeyException;
use OxidEsales\EshopCommunity\Internal\Module\MetaData\Exception\UnsupportedMetaDataValueTypeException;
use OxidEsales\EshopCommunity\Internal\Module\MetaData\Service\MetaDataProviderInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\TestContainerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\PathUtil\Path;

class MetaDataMapperTest extends TestCase
{
    public function testModuleMetaData20(): void
    {
        $metaDataFilePath = $this->getMetaDataFilePath('TestModuleMetaData20');
        $expectedModuleData = [
            'id'          => 'TestModuleMetaData20',
            'title'                   => [
                'en' => 'Module for testModuleMetaData20'
            ],
            'description' => [
                'de' => 'de description for testModuleMetaData20',
                'en' => 'en description for testModuleMetaData20',
            ],
            'lang'        => 'en',
            'thumbnail'   => 'picture.png',
            'version'     => '1.0',
            'author'      => 'OXID eSales AG',
            'url'         => 'https://www.oxid-esales.com',
            'email'       => 'info@oxid-esales.com',
            'extend'      => [
                'OxidEsales\Eshop\Application\Model\Payment' => 'TestModuleMetaData20\Payment',
                'OxidEsales\Eshop\Application\Model\Article' => 'TestModuleMetaData20\Article',
            ],
            'controllers' => [
                'myvendor_mymodule_MyModuleController'      => 'TestModuleMetaData20\Controller',
                'myvendor_mymodule_MyOtherModuleController' => 'TestModuleMetaData20\OtherController',
            ],
            'templates'   => [
                'mymodule.tpl'       => 'TestModuleMetaData20/mymodule.tpl',
                'mymodule_other.tpl' => 'TestModuleMetaData20/mymodule_other.tpl'
            ],
            'blocks'      => [
                [
                    'theme'    => 'theme_id',
                    'template' => 'template_1.tpl',
                    'block'    => 'block_1',
                    'file'     => '/blocks/template_1.tpl',
                    'position' => '1'
                ],
                [
                    'template' => 'template_2.tpl',
                    'block'    => 'block_2',
                    'file'     => '/blocks/template_2.tpl',
                    'position' => '2'
                ],
            ],
            'settings'    => [
                [
                    'group' => 'main',
                    'name' => 'setting_1',
                    'type' => 'select',
                    'value' => '0',
                    'constraints' => ['0', '1', '2', '3'],
                    'position' => 3],
                ['group' => 'main', 'name' => 'setting_2', 'type' => 'arr', 'value' => ['value1', 'value2']]
            ],
            'events'      => [
                'onActivate'   => 'TestModuleMetaData20\Events::onActivate',
                'onDeactivate' => 'TestModuleMetaData20\Events::onDeactivate'
            ],
        ];

        $container = $this->getCompiledTestContainer();

        $metaDataDataProvider = $container->get(MetaDataProviderInterface::class);
        $normalizedMetaData = $metaDataDataProvider->getData($metaDataFilePath);

        $metaDataDataMapper = $container->get('oxid_esales.module.metadata.datamapper.metadatamapper');
        $moduleConfiguration = $metaDataDataMapper->fromData($normalizedMetaData);

        $this->assertSame($expectedModuleData['id'], $moduleConfiguration->getId());
        $this->assertSame($expectedModuleData['version'], $moduleConfiguration->getVersion());
        $this->assertSame($expectedModuleData['title'], $moduleConfiguration->getTitle());
        $this->assertSame($expectedModuleData['description'], $moduleConfiguration->getDescription());
        $this->assertSame($expectedModuleData['lang'], $moduleConfiguration->getLang());
        $this->assertSame($expectedModuleData['thumbnail'], $moduleConfiguration->getThumbnail());
        $this->assertSame($expectedModuleData['author'], $moduleConfiguration->getAuthor());
        $this->assertSame($expectedModuleData['url'], $moduleConfiguration->getUrl());
        $this->assertSame($expectedModuleData['email'], $moduleConfiguration->getEmail());

        $classExtensions = [];

        foreach ($moduleConfiguration->getClassExtensions() as $extension) {
            $classExtensions[$extension->getShopClassNamespace()] = $extension->getModuleExtensionClassNamespace();
        }
        $this->assertSame(
            $expectedModuleData['extend'],
            $classExtensions
        );

        $this->assertSame(
            $expectedModuleData['controllers'],
            $moduleConfiguration->getSetting(ModuleSetting::CONTROLLERS)->getValue()
        );
        $this->assertSame(
            $expectedModuleData['templates'],
            $moduleConfiguration->getSetting(ModuleSetting::TEMPLATES)->getValue()
        );
        $this->assertSame(
            $expectedModuleData['blocks'],
            $moduleConfiguration->getSetting(ModuleSetting::TEMPLATE_BLOCKS)->getValue()
        );
        $this->assertSame(
            $expectedModuleData['settings'],
            $moduleConfiguration->getSetting(ModuleSetting::SHOP_MODULE_SETTING)->getValue()
        );
        $this->assertSame(
            $expectedModuleData['events'],
            $moduleConfiguration->getSetting(ModuleSetting::EVENTS)->getValue()
        );
    }

    public function testModuleMetaData21(): void
    {
        $metaDataFilePath = $this->getMetaDataFilePath('TestModuleMetaData21');
        $expectedModuleData = [
            'id'                      => 'TestModuleMetaData21',
            'title'                   => [
                'en' => 'Module for testModuleMetaData21'
            ],
            'description'             => [
                'de' => 'de description for testModuleMetaData21',
                'en' => 'en description for testModuleMetaData21',
            ],
            'lang'                    => 'en',
            'thumbnail'               => 'picture.png',
            'version'                 => '1.0',
            'author'                  => 'OXID eSales AG',
            'url'                     => 'https://www.oxid-esales.com',
            'email'                   => 'info@oxid-esales.com',
            'extend'                  => [
                'OxidEsales\Eshop\Application\Model\Payment' => 'TestModuleMetaData21\Payment',
                'OxidEsales\Eshop\Application\Model\Article' => 'TestModuleMetaData21\Article'
            ],
            'controllers'             => [
                'myvendor_mymodule_MyModuleController'      => 'TestModuleMetaData21\Controller',
                'myvendor_mymodule_MyOtherModuleController' => 'TestModuleMetaData21\OtherController',
            ],
            'templates'               => [
                'mymodule.tpl'       => 'TestModuleMetaData21/mymodule.tpl',
                'mymodule_other.tpl' => 'TestModuleMetaData21/mymodule_other.tpl'
            ],
            'blocks'                  => [
                [
                    'theme'    => 'theme_id',
                    'template' => 'template_1.tpl',
                    'block'    => 'block_1',
                    'file'     => '/blocks/template_1.tpl',
                    'position' => '1'
                ],
                [
                    'template' => 'template_2.tpl',
                    'block'    => 'block_2',
                    'file'     => '/blocks/template_2.tpl',
                    'position' => '2'
                ],
            ],
            'settings'                => [
                [
                    'group' => 'main',
                    'name' => 'setting_1',
                    'type' => 'select',
                    'value' => '0',
                    'constraints' => ['0', '1', '2', '3'],
                    'position' => 3
                ],
                ['group' => 'main', 'name' => 'setting_2', 'type' => 'password', 'value' => 'changeMe']
            ],
            'events'                  => [
                'onActivate'   => 'TestModuleMetaData21\Events::onActivate',
                'onDeactivate' => 'TestModuleMetaData21\Events::onDeactivate'
            ],
            'smartyPluginDirectories' => [
                'Smarty/PluginDirectory'
            ],
        ];

        $container = $this->getCompiledTestContainer();

        $metaDataDataProvider = $container->get(MetaDataProviderInterface::class);
        $normalizedMetaData = $metaDataDataProvider->getData($metaDataFilePath);

        $metaDataDataMapper = $container->get('oxid_esales.module.metadata.datamapper.metadatamapper');
        $moduleConfiguration = $metaDataDataMapper->fromData($normalizedMetaData);

        $this->assertSame($expectedModuleData['id'], $moduleConfiguration->getId());
        $this->assertSame($expectedModuleData['version'], $moduleConfiguration->getVersion());
        $this->assertSame($expectedModuleData['title'], $moduleConfiguration->getTitle());
        $this->assertSame($expectedModuleData['description'], $moduleConfiguration->getDescription());
        $this->assertSame($expectedModuleData['lang'], $moduleConfiguration->getLang());
        $this->assertSame($expectedModuleData['thumbnail'], $moduleConfiguration->getThumbnail());
        $this->assertSame($expectedModuleData['author'], $moduleConfiguration->getAuthor());
        $this->assertSame($expectedModuleData['url'], $moduleConfiguration->getUrl());
        $this->assertSame($expectedModuleData['email'], $moduleConfiguration->getEmail());

        $classExtensions = [];

        foreach ($moduleConfiguration->getClassExtensions() as $extension) {
            $classExtensions[$extension->getShopClassNamespace()] = $extension->getModuleExtensionClassNamespace();
        }

        $this->assertSame(
            $expectedModuleData['extend'],
            $classExtensions
        );
        $this->assertSame(
            $expectedModuleData['controllers'],
            $moduleConfiguration->getSetting(ModuleSetting::CONTROLLERS)->getValue()
        );
        $this->assertSame(
            $expectedModuleData['templates'],
            $moduleConfiguration->getSetting(ModuleSetting::TEMPLATES)->getValue()
        );
        $this->assertSame(
            $expectedModuleData['blocks'],
            $moduleConfiguration->getSetting(ModuleSetting::TEMPLATE_BLOCKS)->getValue()
        );
        $this->assertSame(
            $expectedModuleData['settings'],
            $moduleConfiguration->getSetting(ModuleSetting::SHOP_MODULE_SETTING)->getValue()
        );
        $this->assertSame(
            $expectedModuleData['events'],
            $moduleConfiguration->getSetting(ModuleSetting::EVENTS)->getValue()
        );
        $this->assertSame(
            $expectedModuleData['smartyPluginDirectories'],
            $moduleConfiguration->getSetting(ModuleSetting::SMARTY_PLUGIN_DIRECTORIES)->getValue()
        );
    }

    /**
     * Test that on metadata.php, which is only partially filled, safe types are returned by the corresponding methods
     */
    public function testModuleWithPartialMetaData(): void
    {
        $this->expectException(ModuleIdNotValidException::class);
        $testModuleDirectory = 'TestModuleWithPartialMetaData';

        $metaDataFilePath = $this->getMetaDataFilePath($testModuleDirectory);
        $expectedModuleData = [
            'extend' => [
                'OxidEsales\Eshop\Application\Model\Payment' => 'TestModuleWithPartialMetaData\Payment',
                'OxidEsales\Eshop\Application\Model\Article' => 'TestModuleWithPartialMetaData\Article'
            ],
        ];

        $container = $this->getCompiledTestContainer();

        $metaDataDataProvider = $container->get(MetaDataProviderInterface::class);
        $normalizedMetaData = $metaDataDataProvider->getData($metaDataFilePath);

        $metaDataDataMapper = $container->get('oxid_esales.module.metadata.datamapper.metadatamapper');
        $moduleConfiguration = $metaDataDataMapper->fromData($normalizedMetaData);

        /**
         * The module directory name should be set as the module ID is missing in metadata.Same
         */
        $this->assertEquals($testModuleDirectory, $moduleConfiguration->getId());

        /** All methods should return type safe default values, if there were no values defined in metadata.php */
        $this->assertSame([], $moduleConfiguration->getTitle());
        $this->assertSame([], $moduleConfiguration->getDescription());
        $this->assertSame('', $moduleConfiguration->getLang());
        $this->assertSame('', $moduleConfiguration->getThumbnail());
        $this->assertSame('', $moduleConfiguration->getAuthor());
        $this->assertSame('', $moduleConfiguration->getUrl());
        $this->assertSame('', $moduleConfiguration->getEmail());

        /** This is the only value defined in metadata.php */

        $classExtensions = [];

        foreach ($moduleConfiguration->getClassExtensions() as $extension) {
            $classExtensions[$extension->getShopClassNamespace()] = $extension->getModuleExtensionClassNamespace();
        }

        $this->assertEquals(
            $expectedModuleData['extend'],
            $classExtensions
        );
    }

    /**
     * @expectedException  UnsupportedMetaDataValueTypeException
     */
    public function testModuleWithSurplusData(): void
    {
        $this->expectException(UnsupportedMetaDataKeyException::class);
        $metaDataFilePath = $this->getMetaDataFilePath('TestModuleWithSurplusData');
        $expectedModuleData = [
            'id' => 'TestModuleWithSurplusData',
        ];

        $container = $this->getCompiledTestContainer();

        $metaDataDataProvider = $container->get(MetaDataProviderInterface::class);
        $normalizedMetaData = $metaDataDataProvider->getData($metaDataFilePath);

        $metaDataDataMapper = $container->get('oxid_esales.module.metadata.datamapper.metadatamapper');
        $moduleConfiguration = $metaDataDataMapper->fromData($normalizedMetaData);

        $this->assertEquals($expectedModuleData['id'], $moduleConfiguration->getId());
    }

    /**
     * @param string $testModuleDirectory
     *
     * @return string
     */
    private function getMetaDataFilePath(string $testModuleDirectory): string
    {
        $metaDataFilePath = Path::join(__DIR__, 'TestData', $testModuleDirectory, 'metadata.php');

        return $metaDataFilePath;
    }

    /**
     * @return ContainerBuilder
     */
    private function getCompiledTestContainer(): ContainerBuilder
    {
        $container = (new TestContainerFactory())->create();
        $container->compile();

        return $container;
    }
}
