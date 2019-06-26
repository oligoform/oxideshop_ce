<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Smarty;

use OxidEsales\EshopCommunity\Internal\Smarty\Configuration\SmartyConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Templating\TemplateEngineFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Templating\TemplateEngineInterface;

/**
 * Class SmartyEngineFactory
 * @package OxidEsales\EshopCommunity\Internal\Smarty
 */
class SmartyEngineFactory implements TemplateEngineFactoryInterface
{
    /**
     * @var TemplateEngineInterface
     */
    private $engine;

    /**
     * @var SmartyBuilder
     */
    private $smartyBuilder;

    /**
     * @var SmartyConfigurationInterface
     */
    private $smartyConfiguration;

    /**
     * SmartyEngineFactory constructor.
     *
     * @param SmartyBuilder                $smartyBuilder
     * @param SmartyConfigurationInterface $smartyConfiguration
     */
    public function __construct(SmartyBuilder $smartyBuilder, SmartyConfigurationInterface $smartyConfiguration)
    {
        $this->smartyBuilder = $smartyBuilder;
        $this->smartyConfiguration = $smartyConfiguration;
    }

    /**
     * @return TemplateEngineInterface
     */
    public function getEngine(): TemplateEngineInterface
    {
        $this->initializeEngine();
        return $this->engine;
    }

    /**
     * Create and initialize Smarty engine
     */
    private function initializeEngine()
    {
        $smarty = $this->smartyBuilder
            ->setSettings($this->smartyConfiguration->getSettings())
            ->setSecuritySettings($this->smartyConfiguration->getSecuritySettings())
            ->registerPlugins($this->smartyConfiguration->getPlugins())
            ->registerPrefilters($this->smartyConfiguration->getPrefilters())
            ->registerResources($this->smartyConfiguration->getResources())
            ->getSmarty();

        //TODO Event for smarty object configuration

        $this->engine = new SmartyEngine($smarty);
    }
}
