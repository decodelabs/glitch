<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace {
    use DecodeLabs\Glitch;
    use DecodeLabs\Monarch;
    use DecodeLabs\Remnant\Anchor\FunctionIdentifier as FunctionIdentifierAnchor;
    use DecodeLabs\Remnant\Frame;
    use DecodeLabs\Remnant\FunctionIdentifier\GlobalFunction as GlobalFunctionIdentifier;
    use Symfony\Component\VarDumper\VarDumper;

    if (!function_exists('dd')) {
        function dd(
            mixed $var,
            mixed ...$vars
        ): void {
            $glitch = Monarch::getService(Glitch::class);
            $glitch->dumpValues(
                values: func_get_args(),
                anchor: new FunctionIdentifierAnchor(
                    new GlobalFunctionIdentifier('dd')
                ),
                exit: true
            );
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
            $glitch = Monarch::getService(Glitch::class);
            $glitch->dumpValues(
                values: func_get_args(),
                anchor: new FunctionIdentifierAnchor(
                    new GlobalFunctionIdentifier('dump')
                ),
                exit: false
            );
        }
    } elseif (class_exists(VarDumper::class)) {
        VarDumper::setHandler(function ($var) {
            /**
             * We have to do some silly juggling here to combine all the dump args into one
             * Symfony blindly calls dump for each var, which doesn't work for us
             * Instead we grab all args from the stack trace and then skip the following calls
             *
             * @var int
             */
            static $skip;

            if (!$skip) {
                $frame = Frame::create(2);

                if (
                    $frame->function instanceof GlobalFunctionIdentifier &&
                    $frame->function->isFunction('dd', 'dump')
                ) {
                    $args = $frame->arguments->values;
                    $skip = count($args) - 1;
                } else {
                    $args = func_get_args();
                }

                $glitch = Monarch::getService(Glitch::class);

                $glitch->dumpValues(
                    values: $args,
                    anchor: new FunctionIdentifierAnchor(
                        new GlobalFunctionIdentifier('dd'),
                        new GlobalFunctionIdentifier('dump'),
                    ),
                    exit: $frame->function->isFunction('dd')
                );
            } else {
                $skip--;
                return;
            }
        });
    }
}
