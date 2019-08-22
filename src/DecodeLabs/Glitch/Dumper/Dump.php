<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Dumper;

use \ArrayIterator;

use DecodeLabs\Glitch\Stat;
use DecodeLabs\Glitch\Stack\Trace;

class Dump implements \IteratorAggregate
{
    protected $stats = [];
    protected $entities = [];

    protected $trace;
    protected $traceEntity;

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
        return $this->stats[$name] ?? null;
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
     * Set trace entity
     */
    public function setTraceEntity(Entity $entity): Dump
    {
        $this->traceEntity = $entity;
        return $this;
    }

    /**
     * Get trace entity
     */
    public function getTraceEntity(): ?Entity
    {
        return $this->traceEntity;
    }


    /**
     * Loop all entities
     */
    public function getIterator()
    {
        return new ArrayIterator($this->entities);
    }
}
