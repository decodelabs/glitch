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
        $entity->setDefinition(Reflection::getClassDefinition($reflection));

        if (!$reflection->isInternal()) {
            $entity
                ->setFile($reflection->getFileName())
                ->setStartLine($reflection->getStartLine())
                ->setEndLine($reflection->getEndLine());
        }
    }

    /**
     * Inspect ReflectionClassConstant
     */
    public static function inspectReflectionClassConstant(\ReflectionClassConstant $reflection, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setDefinition(Reflection::getConstantDefinition($reflection))
            ->setProperties([
                'class' => $reflection->class
            ]);
    }

    /**
     * Inspect ReflectionZendExtension
     */
    public static function inspectReflectionZendExtension(\ReflectionZendExtension $reflection, Entity $entity, Inspector $inspector): void
    {
        $entity->setProperties($inspector->inspectValues([
            'version' => $reflection->getVersion(),
            'author' => $reflection->getAuthor(),
            'copyright' => $reflection->getCopyright(),
            'url' => $reflection->getURL()
        ]));
    }

    /**
     * Inspect ReflectionExtension
     */
    public static function inspectReflectionExtension(\ReflectionExtension $reflection, Entity $entity, Inspector $inspector): void
    {
        $entity->setProperties($inspector->inspectValues([
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
    public static function inspectReflectionFunction(\ReflectionFunctionAbstract $reflection, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setDefinition(Reflection::getFunctionDefinition($reflection))
            ->setFile($reflection->getFileName())
            ->setStartLine($reflection->getStartLine())
            ->setEndLine($reflection->getEndLine());
    }

    /**
     * Inspect ReflectionMethod
     */
    public static function inspectReflectionMethod(\ReflectionMethod $reflection, Entity $entity, Inspector $inspector): void
    {
        self::inspectReflectionFunction($reflection, $entity, $inspector);

        $entity->setProperties([
            'class' => $reflection->getDeclaringClass()->getName()
        ]);
    }

    /**
     * Inspect ReflectionParameter
     */
    public static function inspectReflectionParameter(\ReflectionParameter $reflection, Entity $entity, Inspector $inspector): void
    {
        $entity->setDefinition(self::getParameterDefinition($reflection));
    }

    /**
     * Inspect ReflectionProperty
     */
    public static function inspectReflectionProperty(\ReflectionProperty $reflection, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setDefinition(self::getPropertyDefinition($reflection))
            ->setProperties([
                'class' => $reflection->getDeclaringClass()->getName()
            ]);
    }

    /**
     * Inspect ReflectionType
     */
    public static function inspectReflectionType(\ReflectionType $reflection, Entity $entity, Inspector $inspector): void
    {
        $entity->setProperties([
            'name' => $reflection->getName(),
            'allowsNull' => $reflection->allowsNull(),
            'isBuiltin' => $reflection->isBuiltin()
        ]);
    }

    /**
     * Inspect ReflectionGenerator
     */
    public static function inspectReflectionGenerator(\ReflectionGenerator $reflection, Entity $entity, Inspector $inspector): void
    {
        $function = $reflection->getFunction();

        $entity
            ->setDefinition(Reflection::getFunctionDefinition($function))
            ->setFile($function->getFileName())
            ->setStartLine($function->getStartLine())
            ->setEndLine($function->getEndLine());
    }




    /**
     * Export class definitoin
     */
    public static function getClassDefinition(\ReflectionClass $reflection): string
    {
        $output = 'class ';
        $name = $reflection->getName();

        if (0 === strpos($name, "class@anonymous\x00")) {
            $output .= '() ';
        } else {
            $output .= $name.' ';
        }

        if ($parent = $reflection->getParentClass()) {
            $output .= 'extends '.$parent->getName();
        }

        $interfaces = [];

        foreach ($reflection->getInterfaces() as $interface) {
            $interfaces[] = $interface->getName();
        }

        if (!empty($interfaces)) {
            $output .= 'implements '.implode(', ', $interfaces).' ';
        }

        $output .= '{'."\n";

        foreach ($reflection->getReflectionConstants() as $const) {
            $output .= '    '.self::getConstantDefinition($const)."\n";
        }

        foreach ($reflection->getProperties() as $property) {
            $output .= '    '.self::getPropertyDefinition($property)."\n";
        }

        foreach ($reflection->getMethods() as $method) {
            $output .= '    '.self::getFunctionDefinition($method)."\n";
        }

        $output .= '}';

        return $output;
    }


    /**
     * Export property definition
     */
    public static function getPropertyDefinition(\ReflectionProperty $reflection): string
    {
        $output = implode(' ', \Reflection::getModifierNames($reflection->getModifiers()));
        $name = $reflection->getName();
        $output .= ' $'.$name.' = ';
        $reflection->setAccessible(true);
        $props = $reflection->getDeclaringClass()->getDefaultProperties();
        $value = $prop[$name] ?? null;

        if (is_array($value)) {
            $output .= '[...]';
        } else {
            $output .= Inspector::scalarToString($value);
        }

        return $output;
    }


    /**
     * Export class constant definition
     */
    public static function getConstantDefinition(\ReflectionClassConstant $reflection): string
    {
        $output = implode(' ', \Reflection::getModifierNames($reflection->getModifiers()));
        $output .= ' const '.$reflection->getName().' = ';
        $value = $reflection->getValue();

        if (is_array($value)) {
            $output .= '[...]';
        } else {
            $output .= Inspector::scalarToString($value);
        }

        return $output;
    }


    /**
     * Export function definition
     */
    public static function getFunctionDefinition(\ReflectionFunctionAbstract $reflection): string
    {
        $output = '';

        if ($reflection instanceof \ReflectionMethod) {
            $output = implode(' ', \Reflection::getModifierNames($reflection->getModifiers()));

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

            $output .= $returnType->getName();
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
            $output .= $type->getName().' ';
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
