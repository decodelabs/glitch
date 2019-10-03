<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Stream
{
    /**
     * Inspect stream resource
     */
    public static function inspectStream($resource, Entity $entity, Inspector $inspector): void
    {
        $entity->setMetaList($inspector->inspectList(stream_get_meta_data($resource)));
        self::inspectStreamContext($resource, $entity, $inspector);
    }

    /**
     * Inspect stream context resource
     */
    public static function inspectStreamContext($resource, Entity $entity, Inspector $inspector): void
    {
        if (!$params = @stream_context_get_params($resource)) {
            return;
        }

        $entity->setMetaList($inspector->inspectList($params));
    }
}
