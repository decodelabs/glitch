<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper\Inspect;

use Glitch\Dumper\Entity;
use Glitch\Dumper\Inspector;

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
