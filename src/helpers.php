<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);


/**
 * global helpers
 */
namespace
{
    use Glitch\Factory;
    use Glitch\Context;

    use Symfony\Component\VarDumper\VarDumper;

    if (!function_exists('dd')) {
        /**
         * Super quick global dump & die
         */
        function dd($var, ...$vars): void
        {
            Context::getDefault()->dumpDie(func_get_args(), 1);
        }
    }

    if (!function_exists('dump')) {
        /**
         * Quick dump
         */
        function dump($var, ...$vars): void
        {
            Context::getDefault()->dump(func_get_args(), 1);
        }
    } elseif (class_exists(VarDumper::class)) {
        VarDumper::setHandler(function ($var, ...$vars) {
            Context::getDefault()->dump(func_get_args(), 1);
        });
    }


    /**
     * Temporary dump handler
     */
    function dd2($var, ...$vars): void
    {
        Context::getDefault()->dd2(func_get_args());
    }


    /**
     * Direct facade for generating IError based exceptions
     */
    function Glitch($message, ?array $params=[], $data=null): \EGlitch
    {
        return Factory::create(
            null,
            [],
            $message,
            $params,
            $data
        );
    }
}
