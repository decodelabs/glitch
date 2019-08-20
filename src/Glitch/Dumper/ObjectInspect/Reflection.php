<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper\ObjectInspect;

use Glitch\Dumper\Entity;
use Glitch\Dumper\Inspector;

class Reflection
{
    /**
     * Inspect ReflectionClass
     */
    public static function inspectReflectionClass(\ReflectionClass $reflection, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect ReflectionClassConstant
     */
    public static function inspectReflectionClassConstant(\ReflectionClassConstant $reflection, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect ReflectionZendExtension
     */
    public static function inspectReflectionZendExtension(\ReflectionZendExtension $reflection, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect ReflectionExtension
     */
    public static function inspectReflectionExtension(\ReflectionExtension $reflection, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect ReflectionFunction
     */
    public static function inspectReflectionFunction(\ReflectionFunction $reflection, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect ReflectionMethod
     */
    public static function inspectReflectionMethod(\ReflectionMethod $reflection, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect ReflectionParameter
     */
    public static function inspectReflectionParameter(\ReflectionParameter $reflection, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect ReflectionProperty
     */
    public static function inspectReflectionProperty(\ReflectionProperty $reflection, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect ReflectionType
     */
    public static function inspectReflectionType(\ReflectionType $reflection, Entity $entity, Inspector $inspector): void
    {
    }

    /**
     * Inspect ReflectionGenerator
     */
    public static function inspectReflectionGenerator(\ReflectionGenerator $reflection, Entity $entity, Inspector $inspector): void
    {
    }



    /**
     * Export function definition
     */
    public static function getFunctionDefinition(\ReflectionFunctionAbstract $reflection): string
    {
        $output = '';

        if ($reflection instanceof \ReflectionMethod) {
            $output = implode(' ', $reflection->getModifiers());

            if (!empty($output)) {
                $output .= ' ';
            }
        }

        $output .= 'function ';

        if ($reflection->returnsReference()) {
            $output .= '& ';
        }

        if (!$reflection->isClosure()) {
            $output .= $reflection->getName().' ';
        }

        $output .= '(';
        $params = [];

        foreach ($reflection->getParameters() as $parameter) {
            $params[] = self::getParameterDefinition($parameter);
        }

        $output .= implode(', ', $params).')';

        if ($returnType = $reflection->getReturnType()) {
            $output .= ': ';

            if ($returnType->allowsNull()) {
                $output .= '?';
            }

            $output .= (string)$returnType;
        }

        return $output;
    }

    /**
     * Export parameter definition
     */
    public static function getParameterDefinition(\ReflectionParameter $parameter): string
    {
        $output = '';

        if ($parameter->allowsNull()) {
            $output .= '?';
        }

        if ($type = $parameter->getType()) {
            $output .= (string)$type.' ';
        }

        if ($parameter->isPassedByReference()) {
            $output .= '& ';
        }

        if ($parameter->isVariadic()) {
            $output .= '...';
        }

        $output .= '$'.$parameter->getName();

        if ($parameter->isDefaultValueAvailable()) {
            $output .= '='.(Inspector::scalarToString($parameter->getDefaultValue()) ?? '??');
        }

        return $output;
    }
}
