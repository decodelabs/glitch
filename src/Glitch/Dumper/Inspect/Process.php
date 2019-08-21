<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper\Inspect;

use Glitch\Dumper\Entity;
use Glitch\Dumper\Inspector;

class Process
{
    /**
     * Inspect process resource
     */
    public static function inspectProcess($resource, Entity $entity, Inspector $inspector): void
    {
        foreach (proc_get_status($resource) as $key => $value) {
            $entity->setMeta($key, $inspector->inspectValue($value));
        }
    }
}
