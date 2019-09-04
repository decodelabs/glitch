<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch;

class Stat
{
    protected $name;
    protected $key;
    protected $value;
    protected $class = 'info';

    protected $renderers = [];

    /**
     * Construct with main info
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
     * Set badge class
     */
    public function setClass(string $class): Stat
    {
        $this->class = $class;
        return $this;
    }

    /**
     * Get badge class
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Apply class with value
     */
    public function applyClass(callable $applicator): Stat
    {
        return $this->setClass($applicator($this->value));
    }


    /**
     * Add a named renderer
     */
    public function setRenderer(string $type, callable $renderer): Stat
    {
        $this->renderers[$type] = $renderer;
        return $this;
    }

    /**
     * Render to string using stack of named renderers
     */
    public function render(string $type): ?string
    {
        if (isset($this->renderers[$type])) {
            $output = $this->renderers[$type]($this->value);
        } elseif ($type !== 'text' && isset($this->renderers['text'])) {
            $output = $this->renderers['text']($this->value);
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
