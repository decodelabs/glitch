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
    use DecodeLabs\Glitch\Factory;
    use DecodeLabs\Glitch\Context;
    use DecodeLabs\Glitch\Stack\Frame;

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

                Context::getDefault()->dump($args, 3);
            } else {
                $skip--;
                return;
            }
        });
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
