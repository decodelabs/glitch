<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect {

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

use ReflectionObject;
use SimpleXMLElement;
use Throwable;
use XMLParser;
use XMLWriter;

class Xml
{
    /**
     * Inspect Xml resource
     *
     * @param resource|XMLParser $resource
     */
    public static function inspectXmlResource($resource, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setMeta('current_byte_index', $inspector(
                /** @phpstan-ignore-next-line */
                xml_get_current_byte_index($resource)
            ))
            ->setMeta('current_column_number', $inspector(
                /** @phpstan-ignore-next-line */
                xml_get_current_column_number($resource)
            ))
            ->setMeta('current_line_number', $inspector(
                /** @phpstan-ignore-next-line */
                xml_get_current_line_number($resource)
            ))
            ->setMeta('error_code', $inspector(
                /** @phpstan-ignore-next-line */
                xml_get_error_code($resource)
            ));
    }

    /**
     * Inspect simple Xml
     */
    public static function inspectSimpleXmlElement(SimpleXMLElement $element, Entity $entity, Inspector $inspector): void
    {
        $ref = new ReflectionObject($element);
        $values = [];

        foreach ($ref->getProperties() as $property) {
            $name = $property->getName();
            $values[$name] = $inspector($property->getValue($element));
        }

        $xml = $element->asXML();

        if (is_bool($xml)) {
            $xml = null;
        }

        $entity
            ->setText(empty($values) ? (string)$element : null)
            ->setDefinition($xml)
            ->setValues($values)
            ->setSectionVisible('definition', false);
    }

    /**
     * Inspect Xml writer
     */
    public static function inspectXmlWriter(XMLWriter $writer, Entity $entity, Inspector $inspector): void
    {
        try {
            $xml = $writer->outputMemory(false);
            $entity->setText($xml);
        } catch (Throwable $e) {
        }
    }
}
}

namespace {
    if (!class_exists('XMLParser')) {
        class XMLParser
        {
        }
    }
}
