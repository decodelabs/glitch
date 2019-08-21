<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper\Inspect;

use Glitch\Dumper\Entity;
use Glitch\Dumper\Inspector;

class Stream
{
    /**
     * Inspect stream resource
     */
    public static function inspectStream($resource, Entity $entity, Inspector $inspector): void
    {
        foreach (stream_get_meta_data($resource) as $key => $value) {
            $entity->setMeta($key, $inspector->inspectValue($value));
        }

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

        foreach ($params as $key => $value) {
            $entity->setMeta($key, $inspector->inspectValue($value));
        }
    }
}
