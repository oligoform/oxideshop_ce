<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Templating;

/**
 * Interface FileLocatorInterface
 * @package OxidEsales\EshopCommunity\Internal\Templating
 */
interface FileLocatorInterface
{
    /**
     * Returns a full path for a given file name.
     *
     * @param string $name The file name to locate
     *
     * @return string The full path to the file
     */
    public function locate($name): string;
}
