<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use ArrayIterator;
use Countable;
use DecodeLabs\Remnant\Trace;
use Iterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<mixed>
 */
class Dump implements
    IteratorAggregate,
    Countable
{
    /**
     * @var array<string, Stat>
     */
    protected array $stats = [];

    /**
     * @var array<mixed>
     */
    protected array $entities = [];

    protected Trace $trace;

    public function __construct(
        Trace $trace
    ) {
        $this->trace = $trace;
    }


    /**
     * @return $this
     */
    public function addStats(
        Stat ...$stats
    ): static {
        foreach ($stats as $stat) {
            $this->stats[$stat->getKey()] = $stat;
        }

        return $this;
    }

    public function getStat(
        string $key
    ): ?Stat {
        return $this->stats[$key] ?? null;
    }

    /**
     * @return $this
     */
    public function removeStat(
        string $key
    ): static {
        unset($this->stats[$key]);
        return $this;
    }

    /**
     * @return array<string, Stat>
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * @return $this
     */
    public function clearStats(): static
    {
        $this->stats = [];
        return $this;
    }


    public function getTrace(): Trace
    {
        return $this->trace;
    }


    /**
     * @return $this
     */
    public function addEntity(
        mixed $entity
    ): static {
        $this->entities[] = $entity;
        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function count(): int
    {
        return count($this->entities);
    }


    /**
     * @return Iterator<mixed>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->entities);
    }
}
