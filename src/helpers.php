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
    use DecodeLabs\Glitch as Facade;
    use DecodeLabs\Glitch\Context;
    use DecodeLabs\Glitch\Exception\Factory;
    use DecodeLabs\Glitch\Stack\Frame;

    use Symfony\Component\VarDumper\VarDumper;

    // Register the Veneer facade
    Context::registerFacade(Facade::class);

    if (!function_exists('dd')) {
        /**
         * Super quick global dump & die
         */
        function dd($var, ...$vars): void
        {
            Facade::dumpValues(func_get_args(), 1, true);
        }
    }

    if (!function_exists('dd2')) {
        /**
         * Last gasp dumper when testing Glitch
         */
        function dd2($var, ...$vars): void
        {
            $output = [];

            foreach (func_get_args() as $arg) {
                echo '<div><pre>'.print_r($arg, true).'</pre></div>';
            }

            exit;
        }
    }

    if (!function_exists('dump')) {
        /**
         * Quick dump
         */
        function dump($var, ...$vars): void
        {
            Facade::dumpValues(func_get_args(), 1, false);
        }
    } elseif (class_exists(VarDumper::class)) {
        VarDumper::setHandler(function ($var) {
            /**
             * We have to do some silly juggling here to combine all the dump args into one
             * Symfony blindly calls dump for each var, which doesn't work for us
             * Instead we grab all args from the stack trace and then skip the following calls
             */
            static $skip;

            if (!$skip) {
                $frame = Frame::create(2);
                $func = $frame->getFunctionName();
                $type = $frame->getType();

                if (($func == 'dd' || $func == 'dump') && $type == 'globalFunction') {
                    $args = $frame->getArgs();
                    $skip = count($args) - 1;
                } else {
                    $args = func_get_args();
                }

                Facade::dumpValues($args, 3, $func == 'dd');
            } else {
                $skip--;
                return;
            }
        });
    }

    if (!function_exists('Glitch')) {
        /**
         * Generic root passthrough function
         */
        function Glitch($message, ?array $params=[], $data=null): \EGlitch
        {
            return Factory::create(
                null,
                [],
                1,
                $message,
                $params,
                $data
            );
        }
    }
}
