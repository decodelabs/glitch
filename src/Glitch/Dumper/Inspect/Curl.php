<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect {

    use CurlHandle;
    use DecodeLabs\Glitch\Dumper\Entity;
    use DecodeLabs\Glitch\Dumper\Inspector;

    class Curl
    {
        /**
         * Inspect cURL resource
         *
         * @param resource|CurlHandle $resource
         */
        public static function inspectCurl($resource, Entity $entity, Inspector $inspector): void
        {
            $entity->setMetaList($inspector->inspectList(
                /** @phpstan-ignore-next-line */
                curl_getinfo($resource)
            ));
        }
    }
}

namespace {
    if (!class_exists('CurlHandle')) {
        class CurlHandle
        {
        }
    }
}
