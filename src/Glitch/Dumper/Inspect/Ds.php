<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

use Ds\Collection;
use Ds\Pair;
use Ds\Set;

class Ds
{
    /**
     * Inspect Collection
     *
     * @param Collection<int|string, mixed> $collection
     */
    public static function inspectCollection(Collection $collection, Entity $entity, Inspector $inspector): void
    {
        if (method_exists($collection, 'capacity')) {
            $capacity = $collection->capacity();
        } else {
            $capacity = 0;
        }

        $entity
            ->setLength(count($collection))
            ->setMeta('capacity', $inspector($capacity))
            ->setValues($inspector->inspectList($collection->toArray()));
    }

    /**
     * Inspect Pair
     *
     * @param Pair<int|string, mixed> $pair
     */
    public static function inspectPair(Pair $pair, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties($inspector->inspectList($pair->toArray()));
    }

    /**
     * Inspect Set
     *
     * @param Set<mixed> $set
     */
    public static function inspectSet(Set $set, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setLength(count($set))
            ->setMeta('capacity', $inspector($set->capacity()))
            ->setValues($inspector->inspectList($set->toArray()));
    }
}
