<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

use Glitch\Factory;
use Glitch\Context;
use Glitch\IContext;

/**
 * This is just a facade.
 */
final class Glitch
{
    const TYPE = null;

    /**
     * Redirect type list to Factory
     */
    public static function __callStatic(string $method, array $args): \EGlitch
    {
        return Factory::create(
            static::TYPE,
            explode(',', $method),
            ...$args
        );
    }

    /**
     * Shortcut to Context
     */
    public static function getContext(): IContext
    {
        return Context::getDefault();
    }

    /**
     * Shortcut to incomplete context method
     */
    public static function incomplete($data=null)
    {
        Context::getDefault()->incomplete($data, 1);
    }

    /**
     * Shortcut to normalizePath context method
     */
    public static function normalizePath(string $path): string
    {
        return Context::getDefault()->normalizePath($path);
    }

    /**
     * Shortcut to logException context method
     */
    public function logException(\Throwable $e): void
    {
        Context::getDefault()->logException($e);
    }

    /**
     * Private instanciation
     */
    private function __construct()
    {
    }
}
