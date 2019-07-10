<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Templating;

/**
 * Class LegacyTemplateNameResolver
 */
class LegacyTemplateNameResolver implements TemplateNameResolverInterface
{
    /**
     * @var TemplateNameResolverInterface
     */
    private $resolver;

    /**
     * TemplateNameResolver constructor.
     *
     * @param TemplateNameResolverInterface $resolver
     */
    public function __construct(TemplateNameResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function resolve(string $name): string
    {
        return $this->resolver->resolve($this->getFileNameWithoutExtension($name));
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getFileNameWithoutExtension(string $fileName): string
    {
        if (false !== $pos = strrpos($fileName, '.tpl')) {
            $fileName = substr($fileName, 0, $pos);
        } elseif (false !== $pos = strrpos($fileName, '.html.twig')) {
            $fileName = substr($fileName, 0, $pos);
        }
        return $fileName;
    }
}
