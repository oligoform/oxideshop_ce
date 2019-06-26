<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Templating;

/**
 * Class TemplateRenderer
 */
class TemplateRenderer implements TemplateRendererInterface
{
    /**
     * @var TemplateEngineInterface
     */
    private $engine;

    /**
     * @param TemplateEngineInterface $engine
     */
    public function __construct(TemplateEngineInterface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * @param string $template The template name
     * @param array  $context  An array of parameters to pass to the template
     *
     * @return string
     */
    public function renderTemplate(string $template, array $context = []): string
    {
        return $this->getEngine()->render($template, $context);
    }

    /**
     * Renders a fragment of the template.
     *
     * @param string $fragment The template fragment to render
     * @param string $fragmentId The id of the fragment
     * @param array  $context    An array of parameters to pass to the template
     *
     * @return string
     */
    public function renderFragment(string $fragment, string $fragmentId, array $context = []): string
    {
        return $this->getEngine()->renderFragment($fragment, $fragmentId, $context);
    }

    /**
     * Return fallback engine.
     *
     * @return TemplateEngineInterface
     */
    public function getEngine(): TemplateEngineInterface
    {
        return $this->engine;
    }

    /**
     * Returns true if the template exists.
     *
     * @param string $name A template name
     *
     * @return bool true if the template exists, false otherwise
     */
    public function exists(string $name): bool
    {
        return $this->getEngine()->exists($name);
    }
}
