<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Process
{
    /**
     * Inspect process resource
     *
     * @param resource $resource
     */
    public static function inspectProcess($resource, Entity $entity, Inspector $inspector): void
    {
        $entity->setMetaList($inspector->inspectList(proc_get_status($resource)));
    }
}
