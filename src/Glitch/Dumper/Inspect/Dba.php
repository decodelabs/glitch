<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Dba
{
    /**
     * Inspect DBA resource
     */
    public static function inspectDba($resource, Entity $entity, Inspector $inspector): void
    {
        $list = dba_list();
        $entity->setMeta('file', $inspector->inspectValue($list[(int)$resource]));
    }
}
