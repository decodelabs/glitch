<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

/**
 * global helpers
 */

namespace {
    use DecodeLabs\Glitch;
    use DecodeLabs\Glitch\Context;
    use DecodeLabs\Remnant\Frame;
    use DecodeLabs\Veneer;

    use Symfony\Component\VarDumper\VarDumper;

    // Register the Veneer proxy
    Veneer\Manager::getGlobalManager()->register(
        Context::class,
        Glitch::class
    );

    if (!function_exists('dd')) {
        function dd(
            mixed $var,
            mixed ...$vars
        ): void {
            Glitch::dumpValues(func_get_args(), 1, true);
        }
    }

    if (!function_exists('dd2')) {
        /**
         * @param mixed $var
         * @param mixed ...$vars
         */
        function dd2(
            mixed $var,
            mixed ...$vars
        ): never {
            foreach (func_get_args() as $arg) {
                echo '<div><pre>' . print_r($arg, true) . '</pre></div>';
            }

            exit;
        }
    }

    if (!function_exists('dump')) {
        /**
         * @param mixed $var
         * @param mixed ...$vars
         */
        function dump(
            mixed $var,
            mixed ...$vars
        ): void {
            Glitch::dumpValues(func_get_args(), 1, false);
        }
    } elseif (class_exists(VarDumper::class)) {
        VarDumper::setHandler(function ($var) {
            /**
             * We have to do some silly juggling here to combine all the dump args into one
             * Symfony blindly calls dump for each var, which doesn't work for us
             * Instead we grab all args from the stack trace and then skip the following calls
             *
             * @var int $skip
             */
            static $skip;

            if (!$skip) {
                $frame = Frame::create(2);
                $func = $frame->function;
                $type = $frame->type;

                if (
                    (
                        $func == 'dd' ||
                        $func == 'dump'
                    ) &&
                    $type == 'globalFunction'
                ) {
                    $args = $frame->arguments;
                    $skip = count($args) - 1;
                } else {
                    $args = func_get_args();
                }

                Glitch::dumpValues($args, 4, $func == 'dd');
            } else {
                $skip--;
                return;
            }
        });
    }
}
