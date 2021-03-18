<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Curl
{
    /**
     * Inspect cURL resource
     */
    public static function inspectCurl($resource, Entity $entity, Inspector $inspector): void
    {
        $entity->setMetaList($inspector->inspectList(curl_getinfo($resource)));
    }
}
