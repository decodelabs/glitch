<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

use GdImage;

class Gd
{
    /**
     * Inspect GD resource
     *
     * @param resource|GdImage $resource
     */
    public static function inspectGd($resource, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setMeta('width', $inspector(
                /** @phpstan-ignore-next-line */
                imagesx($resource)
            ))
            ->setMeta('height', $inspector(
                /** @phpstan-ignore-next-line */
                imagesy($resource)
            ));
    }

    /**
     * Inspect GD font resource
     *
     * @param int $font
     */
    public static function inspectGdFont(int $font, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setMeta('width', $inspector(imagefontwidth($font)))
            ->setMeta('height', $inspector(imagefontheight($font)));
    }
}
