<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper\ObjectInspect;

use Glitch\Dumper\Entity;
use Glitch\Dumper\Inspector;

class Reflection
{
    /**
     * Inspect Closure
     */
    public static function inspectClosure(\Closure $closure, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect Generator
     */
    public static function inspectGenerator(\Generator $generator, Entity $entity, Inspector $inspector): void
    {
    }
}
