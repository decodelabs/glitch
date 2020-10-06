<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Gd
{
    /**
     * Inspect GD resource
     */
    public static function inspectGd($resource, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setMeta('width', $inspector(imagesx($resource)))
            ->setMeta('height', $inspector(imagesy($resource)));
    }

    /**
     * Inspect GD font resource
     */
    public static function inspectGdFont($resource, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setMeta('width', $inspector(imagefontwidth($resource)))
            ->setMeta('height', $inspector(imagefontheight($resource)));
    }
}
