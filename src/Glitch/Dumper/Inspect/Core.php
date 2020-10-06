<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;
use DecodeLabs\Glitch\Stack\Trace;

class Core
{
    /**
     * Inspect generic exception
     */
    public static function inspectException(\Throwable $exception, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setType('exception')
            ->setText($exception->getMessage())
            ->setProperty('*code', $inspector($exception->getCode()))
            ->setProperty('!previous', $inspector($exception->getPrevious(), function ($entity) {
                $entity->setOpen(false);
            }))
            ->setFile($exception->getFile())
            ->setStartLine($exception->getLine())
            ->setStackTrace(Trace::fromException($exception));

        $reflection = new \ReflectionObject($exception);
        $inspector->inspectClassMembers($exception, $reflection, $entity, [
            'code', 'previous', 'message', 'file', 'line', 'trace', 'stackTrace', 'string', 'xdebug_message'
        ]);
    }


    /**
     * Inspect Closure
     */
    public static function inspectClosure(\Closure $closure, Entity $entity, Inspector $inspector): void
    {
        $reflection = new \ReflectionFunction($closure);

        if (false === ($file = $reflection->getFileName())) {
            $file = null;
        }

        if (false === ($startLine = $reflection->getStartLine())) {
            $startLine = null;
        }

        if (false === ($endLine = $reflection->getEndLine())) {
            $endLine = null;
        }

        $entity
            ->setDefinition(Reflection::getFunctionDefinition($reflection))
            ->setFile($file)
            ->setStartLine($startLine)
            ->setEndLine($endLine);
    }

    /**
     * Inspect Generator
     */
    public static function inspectGenerator(\Generator $generator, Entity $entity, Inspector $inspector): void
    {
        try {
            $reflection = new \ReflectionGenerator($generator);
        } catch (\Exception $e) {
            return;
        }

        $function = $reflection->getFunction();

        if (false === ($file = $function->getFileName())) {
            $file = null;
        }

        if (false === ($startLine = $function->getStartLine())) {
            $startLine = null;
        }

        if (false === ($endLine = $function->getEndLine())) {
            $endLine = null;
        }

        $entity
            ->setDefinition(Reflection::getFunctionDefinition($function))
            ->setFile($file)
            ->setStartLine($startLine)
            ->setEndLine($endLine);
    }

    /**
     * Inspect __PHP_Incomplete_Class
     */
    public static function inspectIncompleteClass(\__PHP_Incomplete_Class $class, Entity $entity, Inspector $inspector): void
    {
        $vars = (array)$class;
        $entity->setDefinition($vars['__PHP_Incomplete_Class_Name']);
        unset($vars['__PHP_Incomplete_Class_Name']);
        $entity->setValues($inspector->inspectList($vars));
    }
}
