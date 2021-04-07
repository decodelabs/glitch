<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

class Stat
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var callable|null
     */
    protected $renderer;

    /**
     * Construct with main info
     *
     * @param mixed $value
     */
    public function __construct(string $key, string $name, $value)
    {
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
     */
    public function setRenderer(?callable $renderer): Stat
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
            return (string)$output;
        }
    }
}
