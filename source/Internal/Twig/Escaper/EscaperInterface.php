<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Twig\Escaper;

use Twig\Environment;

/**
 * Class EscaperInterface
 *
 * @author Tomasz Kowalewski (t.kowalewski@createit.pl)
 */
interface EscaperInterface
{
    /**
     * @return string
     */
    public function getStrategy(): string;

    /**
     * @param Environment $environment
     * @param string      $string
     * @param string      $charset
     *
     * @return string
     */
    public function escape(Environment $environment, $string, $charset): string;
}
