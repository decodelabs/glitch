<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Redis
{
    /**
     * Inspect Redis connection
     */
    public static function inspectRedis(\Redis $redis, Entity $entity, Inspector $inspector): void
    {
        $isConnected = $redis->isConnected();
        $entity->setMeta('connected', $inspector($isConnected));

        if ($isConnected) {
            $entity
                ->setMeta('host', $inspector($redis->getHost()))
                ->setMeta('port', $inspector($redis->getPort()))
                ->setMeta('auth', $inspector($redis->getAuth()))
                ->setMeta('mode', $inspector->inspectFlag($redis->getMode(), [
                    '\Redis::ATOMIC',
                    '\Redis::MULTI',
                    '\Redis::PIPELINE'
                ]))
                ->setMeta('dbNum', $inspector($redis->getDbNum()))
                ->setMeta('timeout', $inspector($redis->getTimeout()))
                ->setMeta('lastError', $inspector($redis->getLastError()))
                ->setMeta('persistentId', $inspector($redis->getPersistentID()));
        }
    }
}
