<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use __PHP_Incomplete_Class;
use BackedEnum;
use Closure;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;
use DecodeLabs\Glitch\Stack\Trace;
use Exception;
use Fiber;
use Generator;
use ReflectionFiber;
use ReflectionFunction;
use ReflectionGenerator;
use ReflectionObject;
use SensitiveParameterValue;
use Throwable;
use UnitEnum;

class Core
{
    /**
     * Inspect generic exception
     */
    public static function inspectException(
        Throwable $exception,
        Entity $entity,
        Inspector $inspector
    ): void {
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

        $reflection = new ReflectionObject($exception);
        $inspector->inspectClassMembers($exception, $reflection, $entity, [
            'code', 'previous', 'message', 'file', 'line', 'trace', 'stackTrace', 'string', 'xdebug_message'
        ]);
    }


    /**
     * Inspect Closure
     */
    public static function inspectClosure(
        Closure $closure,
        Entity $entity,
        Inspector $inspector
    ): void {
        $reflection = new ReflectionFunction($closure);

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
     *
     * @template TKey
     * @template TValue
     * @template TSend
     * @template TReturn
     * @param Generator<TKey, TValue, TSend, TReturn> $generator
     */
    public static function inspectGenerator(
        Generator $generator,
        Entity $entity,
        Inspector $inspector
    ): void {
        try {
            $reflection = new ReflectionGenerator($generator);
        } catch (Exception $e) {
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
     * Inspect enum
     */
    public static function inspectEnum(
        UnitEnum $enum,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity->setClassName($enum->name);

        if ($enum instanceof BackedEnum) {
            $entity->setValue(0, $enum->value);
            $entity->setShowKeys(false);
        }
    }

    /**
     * Inspect Fiber
     *
     * @param Fiber<mixed, mixed, mixed, mixed> $fiber
     */
    public static function inspectFiber(
        Fiber $fiber,
        Entity $entity,
        Inspector $inspector
    ): void {
        if (
            $fiber->isStarted() &&
            !$fiber->isTerminated()
        ) {
            $reflection = new ReflectionFiber($fiber);

            $entity->setMetaList([
                'file' => $reflection->getExecutingFile(),
                'line' => $reflection->getExecutingLine(),
            ]);

            $entity->setStackTrace(Trace::fromArray($reflection->getTrace()));
        }

        $entity->setMetaList([
            'started' => $fiber->isStarted(),
            'running' => $fiber->isRunning(),
            'suspended' => $fiber->isSuspended(),
            'terminated' => $fiber->isTerminated(),
        ]);

        $entity->setSectionVisible('meta', true);

        if ($fiber->isTerminated()) {
            $entity->setShowKeys(false);
            $entity->setValue('return', $inspector($fiber->getReturn()));
        }
    }

    /**
     * Inspect sensitive parameter value
     */
    public static function inspectSensitiveParameterValue(
        SensitiveParameterValue $value,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity
            ->setName('sensitive')
            ->setSensitive(true)
            ->setClassName(getType($value->getValue()));
    }

    /**
     * Inspect __PHP_Incomplete_Class
     */
    public static function inspectIncompleteClass(
        __PHP_Incomplete_Class $class,
        Entity $entity,
        Inspector $inspector
    ): void {
        $vars = (array)$class;
        $entity->setDefinition($vars['__PHP_Incomplete_Class_Name']);
        unset($vars['__PHP_Incomplete_Class_Name']);
        $entity->setValues($inspector->inspectList($vars));
    }
}
