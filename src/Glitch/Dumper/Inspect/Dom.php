<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

use DOMAttr;
use DOMCdataSection;
use DOMCharacterData;
use DOMComment;
use DOMDocument;
use DOMDocumentFragment;
use DOMDocumentType;
use DOMElement;
use DOMEntity;
use DOMEntityReference;
use DOMImplementation;
use DOMNamedNodeMap;
use DOMNode;
use DOMNodeList;
use DOMNotation;
use DOMProcessingInstruction;
use DOMText;
use DOMXPath;

class Dom
{
    /**
     * Inspect attr
     */
    public static function inspectAttr(DOMAttr $attr, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperty('name', $attr->name)
            ->setValues($inspector->inspectList([$attr->value]))
            ->setShowKeys(false);
    }

    /**
     * Inspect CData section
     */
    public static function inspectCdataSection(DOMCdataSection $section, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setText($section->data);
    }

    /**
     * Inspect character data
     */
    public static function inspectCharacterData(DOMCharacterData $data, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setText($data->data);
    }

    /**
     * Inspect comment
     */
    public static function inspectComment(DOMComment $comment, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setText($comment->data);
    }

    /**
     * Inspect document
     */
    public static function inspectDocument(DOMDocument $document, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setDefinition((string)$document->saveXML())
            ->setSectionVisible('definition', false)

            ->setMeta('encoding', $inspector($document->encoding))
            ->setMeta('actualEncoding', $inspector($document->actualEncoding))
            ->setMeta('xmlEncoding', $inspector($document->xmlEncoding))
            ->setMeta('standalone', $inspector($document->standalone))
            ->setMeta('version', $inspector($document->version))
            ->setMeta('xmlVersion', $inspector($document->xmlVersion))
            ->setMeta('config', $inspector($document->config))
            ->setMeta('formatOutput', $inspector($document->formatOutput))
            ->setMeta('validateOnParse', $inspector($document->validateOnParse))
            ->setMeta('resolveExternals', $inspector($document->resolveExternals))
            ->setMeta('preserveWhiteSpace', $inspector($document->preserveWhiteSpace))
            ->setMeta('recover', $inspector($document->recover))
            ->setMeta('substituteEntities', $inspector($document->substituteEntities))

            ->setProperty('documentURI', $inspector($document->documentURI))
            ->setProperty('doctype', $inspector($document->doctype))
            ->setProperty('documentElement', $inspector($document->documentElement));
    }

    /**
     * Inspect document fragment
     */
    public static function inspectDocumentFragment(DOMDocumentFragment $fragment, Entity $entity, Inspector $inspector): void
    {
        self::inspectNode($fragment, $entity, $inspector);
    }

    /**
     * Inspect document type
     */
    public static function inspectDocumentType(DOMDocumentType $type, Entity $entity, Inspector $inspector): void
    {
        if (null === ($owner = $type->ownerDocument)) {
            $xml = null;
        } else {
            $xml = (string)$owner->saveXML($type);
        }

        $entity
            ->setDefinition($xml)

            ->setProperty('name', $inspector($type->name))
            ->setProperty('entities', $inspector($type->entities))
            ->setProperty('notations', $inspector($type->notations))
            ->setProperty('publicId', $inspector($type->publicId))
            ->setProperty('systemId', $inspector($type->systemId))
            ->setProperty('internalSubset', $inspector($type->internalSubset))
            ->setSectionVisible('properties', false);
    }

    /**
     * Inspect element
     */
    public static function inspectElement(DOMElement $element, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperty('tagName', $inspector($element->tagName))
            ->setProperty('attributes', $inspector($element->attributes))
            ->setProperty('childNodes', $inspector->inspectObject($element->childNodes, false))
            ;
    }

    /**
     * Inspect entity
     */
    public static function inspectEntity(DOMEntity $domEntity, Entity $entity, Inspector $inspector): void
    {
        self::inspectNode($domEntity, $entity, $inspector);
    }

    /**
     * Inspect entity reference
     */
    public static function inspectEntityReference(DOMEntityReference $reference, Entity $entity, Inspector $inspector): void
    {
        self::inspectNode($reference, $entity, $inspector);
    }

    /**
     * Inspect implementation
     */
    public static function inspectImplementation(DOMImplementation $implementation, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect node map
     *
     * @param DOMNamedNodeMap<mixed> $map
     */
    public static function inspectNamedNodeMap(DOMNamedNodeMap $map, Entity $entity, Inspector $inspector): void
    {
        $values = [];

        foreach ($map as $key => $attr) {
            $values[$key] = $inspector($attr);
        }

        $entity
            ->setLength($map->count())
            ->setValues($values);
    }

    /**
     * Inspect node
     */
    public static function inspectNode(DOMNode $node, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperty('nodeName', $inspector($node->nodeName))
            ->setProperty('nodeType', $inspector->inspectFlag($node->nodeType, [
                'XML_ELEMENT_NODE',
                'XML_ATTRIBUTE_NODE',
                'XML_TEXT_NODE',
                'XML_CDATA_SECTION_NODE',
                'XML_ENTITY_REF_NODE',
                'XML_ENTITY_NODE',
                'XML_PI_NODE',
                'XML_COMMENT_NODE',
                'XML_DOCUMENT_NODE',
                'XML_DOCUMENT_TYPE_NODE',
                'XML_DOCUMENT_FRAG_NODE',
                'XML_NOTATION_NODE',
                'XML_HTML_DOCUMENT_NODE',
                'XML_DTD_NODE',
                'XML_ELEMENT_DECL_NODE',
                'XML_ATTRIBUTE_DECL_NODE',
                'XML_ENTITY_DECL_NODE',
                'XML_NAMESPACE_DECL_NODE'
            ]))
            ->setSingleValue($inspector($node->nodeValue));
    }

    /**
     * Inspect node list
     *
     * @param DOMNodeList<DOMNode> $list
     */
    public static function inspectNodeList(DOMNodeList $list, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setLength($list->length)
            ->setValues($inspector->inspectList(iterator_to_array($list)))
            ->setShowKeys(false);
    }

    /**
     * Inspect notation
     */
    public static function inspectNotation(DOMNotation $notation, Entity $entity, Inspector $inspector): void
    {
        self::inspectNode($notation, $entity, $inspector);
    }

    /**
     * Inspect PI
     */
    public static function inspectProcessingInstruction(DOMProcessingInstruction $pi, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setDefinition($pi->data);
    }

    /**
     * Inspect text
     */
    public static function inspectText(DOMText $text, Entity $entity, Inspector $inspector): void
    {
        $entity->setText($text->wholeText);
    }

    /**
     * Inspect xpath
     */
    public static function inspectXPath(DOMXPath $xpath, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperty('document', $inspector($xpath->document, function ($entity) {
                $entity->setOpen(false);
            }));
    }
}
