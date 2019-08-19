<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch;

class Context
{
    protected static $default;

    protected $pathAliases = [];

    /**
     * Create / fetch default context
     */
    public static function getDefault(): Context
    {
        if (!self::$default) {
            self::setDefault(new self());
        }

        return self::$default;
    }

    /**
     * Set custom default context
     */
    public static function setDefault(Context $default): void
    {
        self::$default = $default;
    }


    /**
     * Construct
     */
    public function __construct()
    {
        $this->pathAliases['glitch'] = dirname(__DIR__);
    }


    /**
     * Register path replacement alias
     */
    public function registerPathAlias(string $name, string $path): Context
    {
        $this->pathAliases[$name] = $path;

        uasort($this->pathAliases, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $this;
    }

    /**
     * Register list of path replacement aliases
     */
    public function registerPathAliases(array $aliases): Context
    {
        foreach ($aliases as $name => $path) {
            $this->pathAliases[$name] = $path;
        }

        uasort($this->pathAliases, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $this;
    }

    /**
     * Inspect list of registered path aliases
     */
    public function getPathAliases(): array
    {
        return $this->pathAliases;
    }

    /**
     * Lookup and replace path prefix
     */
    public function normalizePath(string $path): string
    {
        foreach ($this->pathAliases as $name => $test) {
            if (0 === strpos($path, $test)) {
                return '{'.$name.'}'.substr($path, strlen($test));
            }
        }

        return $path;
    }
}
