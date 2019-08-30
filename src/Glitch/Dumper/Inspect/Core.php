<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Core
{
    /**
     * Inspect Closure
     */
    public static function inspectClosure(\Closure $closure, Entity $entity, Inspector $inspector): void
    {
        $reflection = new \ReflectionFunction($closure);

        $entity
            ->setDefinition(Reflection::getFunctionDefinition($reflection))
            ->setFile($reflection->getFileName())
            ->setStartLine($reflection->getStartLine())
            ->setEndLine($reflection->getEndLine());
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

        $entity
            ->setDefinition(Reflection::getFunctionDefinition($function))
            ->setFile($function->getFileName())
            ->setStartLine($function->getStartLine())
            ->setEndLine($function->getEndLine());
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
