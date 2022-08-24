<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use DecodeLabs\Coercion;

class Stat
{
    protected string $name;
    protected string $key;
    protected mixed $value;

    /**
     * @var callable|null
     */
    protected $renderer;

    /**
     * Construct with main info
     */
    public function __construct(
        string $key,
        string $name,
        mixed $value
    ) {
        $this->key = $key;
        $this->name = $name;
        $this->value = $value;
    }


    /**
     * Get key
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get name
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * Add a named renderer
     *
     * @return $this
     */
    public function setRenderer(?callable $renderer): static
    {
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * Render to string using stack of named renderers
     */
    public function render(): ?string
    {
        if (isset($this->renderer)) {
            $output = ($this->renderer)($this->value);
        } else {
            $output = $this->value;
        }

        if ($output === null) {
            return null;
        } else {
            return Coercion::toStringOrNull($output);
        }
    }
}
