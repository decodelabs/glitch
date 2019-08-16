<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch;

class PathHandler
{
    protected static $aliases = null;

    public static function registerAlias(string $name, string $path): void
    {
        self::$aliases[$name] = $path;

        uasort(self::$aliases, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
    }

    public static function registerAliases(array $aliases): void
    {
        foreach ($aliases as $name => $path) {
            self::$aliases[$name] = $path;
        }

        uasort(self::$aliases, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
    }

    public static function getAliases(): array
    {
        return array_flip(self::$aliases);
    }

    public static function normalizePath(string $path): string
    {
        foreach (self::$aliases as $name => $test) {
            if (0 === strpos($path, $test)) {
                return '{'.$name.'}'.substr($path, strlen($test));
            }
        }

        return $path;
    }
}
