<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Gmp
{
    /**
     * Inspect Gmp
     */
    public static function inspectGmp(\GMP $gmp, Entity $entity, Inspector $inspector): void
    {
        $entity->setText(gmp_strval($gmp));
    }
}
