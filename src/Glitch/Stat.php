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

    public function __construct(
        string $key,
        string $name,
        mixed $value
    ) {
        $this->key = $key;
        $this->name = $name;
        $this->value = $value;
    }


    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @return $this
     */
    public function setRenderer(
        ?callable $renderer
    ): static {
        $this->renderer = $renderer;
        return $this;
    }

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
            return Coercion::tryString($output);
        }
    }
}
