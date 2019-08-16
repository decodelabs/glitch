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
    use Glitch\PathHandler;

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

    PathHandler::registerAlias('glitch', __DIR__);
}
