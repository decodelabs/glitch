<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper\Inspect;

use Glitch\Dumper\Entity;
use Glitch\Dumper\Inspector;

class Xml
{
    /**
     * Inspect Xml resource
     */
    public static function inspectXmlResource($resource, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setMeta('current_byte_index', $inspector->inspectValue(xml_get_current_byte_index($resource)))
            ->setMeta('current_column_number', $inspector->inspectValue(xml_get_current_column_number($resource)))
            ->setMeta('current_line_number', $inspector->inspectValue(xml_get_current_line_number($resource)))
            ->setMeta('error_code', $inspector->inspectValue(xml_get_error_code($resource)));
    }
}
