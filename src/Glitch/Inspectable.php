<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch;

use Glitch\Dumper\Entity;
use Glitch\Dumper\Inspector;

interface Inspectable
{
    public function glitchInspect(Entity $entity, Inspector $inspector): void;
}
