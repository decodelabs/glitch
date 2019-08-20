<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper;

use \ArrayIterator;
use Glitch\Stack\Trace;

class Dump implements \IteratorAggregate
{
    protected $entities = [];
    protected $trace;
    protected $time;
    protected $memory;

    /**
     * Construct with stack trace of invoking call
     */
    public function __construct(Trace $trace, float $time, int $memory)
    {
        $this->trace = $trace;
        $this->time = $time;
        $this->memory = $memory;
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

    public function getIterator()
    {
        return new ArrayIterator($this->entities);
    }
}
