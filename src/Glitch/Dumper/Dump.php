<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper;

use ArrayIterator;
use Countable;

use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\Glitch\Stat;

use Iterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<mixed>
 */
class Dump implements IteratorAggregate, Countable
{
    /**
     * @var array<string, Stat>
     */
    protected $stats = [];

    /**
     * @var array<mixed>
     */
    protected $entities = [];


    /**
     * @var Trace
     */
    protected $trace;

    /**
     * Construct with stack trace of invoking call
     */
    public function __construct(Trace $trace)
    {
        $this->trace = $trace;
    }


    /**
     * Set named statistic
     */
    public function addStats(Stat ...$stats): Dump
    {
        foreach ($stats as $stat) {
            $this->stats[$stat->getKey()] = $stat;
        }

        return $this;
    }

    /**
     * Get named statistic
     */
    public function getStat(string $key): ?Stat
    {
        return $this->stats[$key] ?? null;
    }

    /**
     * Remove named statistic
     */
    public function removeStat(string $key): Dump
    {
        unset($this->stats[$key]);
        return $this;
    }

    /**
     * Get all named statistics
     *
     * @return array<string, Stat>
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Clear all named statistics
     */
    public function clearStats(): Dump
    {
        $this->stats = [];
        return $this;
    }


    /**
     * Get active trace
     */
    public function getTrace(): Trace
    {
        return $this->trace;
    }


    /**
     * Add an entity to the list
     *
     * @param mixed $entity
     */
    public function addEntity($entity): Dump
    {
        $this->entities[] = $entity;
        return $this;
    }

    /**
     * Get list of entities
     *
     * @return array<mixed>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Count entities
     */
    public function count(): int
    {
        return count($this->entities);
    }


    /**
     * Loop all entities
     *
     * @return Iterator<mixed>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->entities);
    }
}
