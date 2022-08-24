<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

use Reflection as ReflectionRoot;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionExtension;
use ReflectionFunctionAbstract;
use ReflectionGenerator;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use ReflectionZendExtension;

class Reflection
{
    /**
     * Inspect ReflectionClass
     *
     * @template T of object
     * @param ReflectionClass<T> $reflection
     */
    public static function inspectReflectionClass(
        ReflectionClass $reflection,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity->setDefinition(Reflection::getClassDefinition($reflection));

        if (!$reflection->isInternal()) {
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
                ->setFile($file)
                ->setStartLine($startLine)
                ->setEndLine($endLine);
        }
    }

    /**
     * Inspect ReflectionClassConstant
     */
    public static function inspectReflectionClassConstant(
        ReflectionClassConstant $reflection,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity
            ->setDefinition(Reflection::getConstantDefinition($reflection))
            ->setProperties([
                'class' => $reflection->class
            ]);
    }

    /**
     * Inspect ReflectionZendExtension
     */
    public static function inspectReflectionZendExtension(
        ReflectionZendExtension $reflection,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity->setProperties($inspector->inspectList([
            'version' => $reflection->getVersion(),
            'author' => $reflection->getAuthor(),
            'copyright' => $reflection->getCopyright(),
            'url' => $reflection->getURL()
        ]));
    }

    /**
     * Inspect ReflectionExtension
     */
    public static function inspectReflectionExtension(
        ReflectionExtension $reflection,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity->setProperties($inspector->inspectList([
            'version' => $reflection->getVersion(),
            'dependencies' => $reflection->getDependencies(),
            'iniEntries' => $reflection->getIniEntries(),
            'isPersistent' => $reflection->isPersistent(),
            'isTemporary' => $reflection->isTemporary(),
            'constants' => $reflection->getConstants(),
            'functions' => $reflection->getFunctions(),
            'classes' => $reflection->getClasses()
        ]));
    }

    /**
     * Inspect ReflectionFunction
     */
    public static function inspectReflectionFunction(
        ReflectionFunctionAbstract $reflection,
        Entity $entity,
        Inspector $inspector
    ): void {
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
     * Inspect ReflectionMethod
     */
    public static function inspectReflectionMethod(
        ReflectionMethod $reflection,
        Entity $entity,
        Inspector $inspector
    ): void {
        self::inspectReflectionFunction($reflection, $entity, $inspector);

        $entity->setProperties([
            'class' => $reflection->getDeclaringClass()->getName()
        ]);
    }

    /**
     * Inspect ReflectionParameter
     */
    public static function inspectReflectionParameter(
        ReflectionParameter $reflection,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity->setDefinition(self::getParameterDefinition($reflection));
    }

    /**
     * Inspect ReflectionProperty
     */
    public static function inspectReflectionProperty(
        ReflectionProperty $reflection,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity
            ->setDefinition(self::getPropertyDefinition($reflection))
            ->setProperties([
                'class' => $reflection->getDeclaringClass()->getName()
            ]);
    }

    /**
     * Inspect ReflectionType
     */
    public static function inspectReflectionType(
        ReflectionType $reflection,
        Entity $entity,
        Inspector $inspector
    ): void {
        if ($reflection instanceof ReflectionNamedType) {
            $entity->setProperties([
                'name' => $reflection->getName(),
                'allowsNull' => $reflection->allowsNull(),
                'isBuiltin' => $reflection->isBuiltin()
            ]);
        } elseif ($reflection instanceof ReflectionUnionType) {
            $parts = [];

            foreach ($reflection->getTypes() as $inner) {
                if ($inner instanceof ReflectionNamedType) {
                    $parts[] = $inner->getName();
                }
            }

            $entity->setProperties([
                'name' => implode('|', $parts),
                'allowsNull' => $reflection->allowsNull()
            ]);
        } else {
            $entity->setProperties([
                'allowsNull' => $reflection->allowsNull()
            ]);
        }
    }

    /**
     * Inspect ReflectionGenerator
     */
    public static function inspectReflectionGenerator(
        ReflectionGenerator $reflection,
        Entity $entity,
        Inspector $inspector
    ): void {
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
     * Export class definition
     *
     * @template T of object
     * @param ReflectionClass<T> $reflection
     */
    public static function getClassDefinition(ReflectionClass $reflection): string
    {
        $output = 'class ';
        $name = $reflection->getName();

        if (0 === strpos($name, "class@anonymous\x00")) {
            $output .= '() ';
        } else {
            $output .= $name . ' ';
        }

        if ($parent = $reflection->getParentClass()) {
            $output .= 'extends ' . $parent->getName();
        }

        $interfaces = [];

        foreach ($reflection->getInterfaces() as $interface) {
            $interfaces[] = $interface->getName();
        }

        if (!empty($interfaces)) {
            $output .= 'implements ' . implode(', ', $interfaces) . ' ';
        }

        $output .= '{' . "\n";

        foreach ($reflection->getReflectionConstants() as $const) {
            $output .= '    ' . self::getConstantDefinition($const) . "\n";
        }

        foreach ($reflection->getProperties() as $property) {
            $output .= '    ' . self::getPropertyDefinition($property) . "\n";
        }

        foreach ($reflection->getMethods() as $method) {
            $output .= '    ' . self::getFunctionDefinition($method) . "\n";
        }

        $output .= '}';

        return $output;
    }


    /**
     * Export property definition
     */
    public static function getPropertyDefinition(ReflectionProperty $reflection): string
    {
        $output = implode(' ', ReflectionRoot::getModifierNames($reflection->getModifiers()));
        $name = $reflection->getName();
        $output .= ' $' . $name . ' = ';
        $reflection->setAccessible(true);
        $props = $reflection->getDeclaringClass()->getDefaultProperties();
        $value = $props[$name] ?? null;

        if (is_array($value)) {
            $output .= '[...]';
        } else {
            /** @var bool|float|int|resource|string|null $value */
            $output .= Inspector::scalarToString($value);
        }

        return $output;
    }


    /**
     * Export class constant definition
     */
    public static function getConstantDefinition(ReflectionClassConstant $reflection): string
    {
        $output = implode(' ', ReflectionRoot::getModifierNames($reflection->getModifiers()));
        $output .= ' const ' . $reflection->getName() . ' = ';
        $value = $reflection->getValue();

        if (is_array($value)) {
            $output .= '[...]';
        } else {
            /** @var bool|float|int|resource|string|null $value */
            $output .= Inspector::scalarToString($value);
        }

        return $output;
    }


    /**
     * Export function definition
     */
    public static function getFunctionDefinition(ReflectionFunctionAbstract $reflection): string
    {
        $output = '';

        if ($reflection instanceof ReflectionMethod) {
            $output = implode(' ', ReflectionRoot::getModifierNames($reflection->getModifiers()));

            if (!empty($output)) {
                $output .= ' ';
            }
        }

        $output .= 'function ';

        if ($reflection->returnsReference()) {
            $output .= '& ';
        }

        if (!$reflection->isClosure()) {
            $output .= $reflection->getName() . ' ';
        }

        $output .= '(';
        $params = [];

        foreach ($reflection->getParameters() as $parameter) {
            $params[] = self::getParameterDefinition($parameter);
        }

        $output .= implode(', ', $params) . ')';

        if ($returnType = $reflection->getReturnType()) {
            $output .= ': ';

            if ($returnType->allowsNull()) {
                $output .= '?';
            }

            $output .= static::getTypeName($returnType);
        }

        return $output;
    }

    /**
     * Export parameter definition
     */
    public static function getParameterDefinition(ReflectionParameter $parameter): string
    {
        $output = '';

        if ($parameter->allowsNull()) {
            $output .= '?';
        }

        if ($type = $parameter->getType()) {
            $output .= static::getTypeName($type);
        }

        if ($parameter->isPassedByReference()) {
            $output .= '& ';
        }

        if ($parameter->isVariadic()) {
            $output .= '...';
        }

        $output .= '$' . $parameter->getName();

        if ($parameter->isDefaultValueAvailable()) {
            /** @var bool|float|int|resource|string|null $value */
            $value = $parameter->getDefaultValue();
            $output .= '=' . Inspector::scalarToString($value);
        }

        return $output;
    }


    /**
     * Get type name
     */
    protected static function getTypeName(ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName() . ' ';
        }

        if ($type instanceof ReflectionUnionType) {
            $parts = [];

            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof ReflectionNamedType) {
                    $parts[] = $innerType->getName();
                }
            }

            return implode('|', $parts) . ' ';
        }

        return '??';
    }
}
