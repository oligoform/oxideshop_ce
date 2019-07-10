<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Templating;

use OxidEsales\Eshop\Core\Exception\SystemComponentException;

/**
 * Class TemplateLoader
 * @package OxidEsales\EshopCommunity\Internal\Templating
 */
class TemplateLoader implements TemplateLoaderInterface
{
    /**
     * @var TemplateNameResolverInterface
     */
    private $templateNameResolver;

    /**
     * @var TemplateFileLocatorInterface
     */
    private $fileLocator;

    /**
     * TemplateLoader constructor.
     *
     * @param TemplateFileLocatorInterface  $fileLocator
     * @param TemplateNameResolverInterface $templateNameResolver
     */
    public function __construct(
        TemplateFileLocatorInterface $fileLocator,
        TemplateNameResolverInterface $templateNameResolver
    ) {
        $this->fileLocator = $fileLocator;
        $this->templateNameResolver = $templateNameResolver;
    }

    /**
     * Check a template exists.
     *
     * @param string $name The name of the template
     *
     * @return bool
     */
    public function exists($name): bool
    {
        try {
            $this->findTemplate($name);
        } catch (SystemComponentException $e) {
            return false;
        }
        return true;
    }

    /**
     * Returns the content of the given template.
     *
     * @param string $name The name of the template
     *
     * @return string
     *
     * @throws SystemComponentException
     */
    public function getContext($name): string
    {
        $path = $this->findTemplate($name);

        return file_get_contents($path);
    }

    /**
     * Returns the path to the template.
     *
     * @param string $name A template name
     *
     * @return string
     *
     * @throws SystemComponentException
     */
    public function getPath($name): string
    {
        return $this->findTemplate($name);
    }

    /**
     * @param string $name A template name
     *
     * @return string
     *
     * @throws SystemComponentException
     */
    private function findTemplate($name): string
    {
        $templateName = $this->templateNameResolver->resolve($name);
        $file = $this->fileLocator->locate($templateName);

        if (false === $file || null === $file || '' === $file) {
            $ex = oxNew(SystemComponentException::class);
            $ex->setMessage('EXCEPTION_SYSTEMCOMPONENT_TEMPLATENOTFOUND' . ' ' . $name);
            $ex->setComponent($name);

            throw $ex;
        }
        return $file;
    }
}
