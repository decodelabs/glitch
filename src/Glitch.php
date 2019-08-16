<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

use Glitch\Factory;

/**
 * This is just a facade.
 * See lang\error
 */
class Glitch
{
    const TYPE = null;

    public static function __callStatic(string $method, array $args): \EGlitch
    {
        return Factory::create(
            static::TYPE,
            explode(',', $method),
            ...$args
        );
    }

    private function __construct()
    {
    }
}
