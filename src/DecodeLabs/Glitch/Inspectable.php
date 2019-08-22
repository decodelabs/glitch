<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch;

use DecodeLabs\Glitch\Dumper\Entity;

interface Inspectable
{
    public function glitchInspect(Entity $entity, callable $inspector): void;
}
