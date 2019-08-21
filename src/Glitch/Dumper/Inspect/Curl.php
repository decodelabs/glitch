<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper\Inspect;

use Glitch\Dumper\Entity;
use Glitch\Dumper\Inspector;

class Curl
{
    /**
     * Inspect cURL resource
     */
    public static function inspectCurl($resource, Entity $entity, Inspector $inspector): void
    {
        foreach (curl_getinfo($resource) as $key => $value) {
            $entity->setMeta($key, $inspector->inspectValue($value));
        }
    }
}
