<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper;

use ArrayIterator;
use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\Glitch\Stat;

class Dump implements \IteratorAggregate, \Countable
{
    protected $stats = [];
    protected $entities = [];

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
     */
    public function addEntity($entity): Dump
    {
        $this->entities[] = $entity;
        return $this;
    }

    /**
     * Get list of entities
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
     */
    public function getIterator()
    {
        return new ArrayIterator($this->entities);
    }
}
