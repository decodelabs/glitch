<?php
/**
 * This file is part of the Glitch package
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
            ->setMeta('width', $inspector->inspectValue(imagesx($resource)))
            ->setMeta('height', $inspector->inspectValue(imagesy($resource)));
    }

    /**
     * Inspect GD font resource
     */
    public static function inspectGdFont($resource, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setMeta('width', $inspector->inspectValue(imagesfontwidth($resource)))
            ->setMeta('height', $inspector->inspectValue(imagesfontheight($resource)));
    }
}
