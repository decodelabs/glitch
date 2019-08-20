<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper;

use Glitch\Context;
use Glitch\IContext;
use Glitch\IInspectable;
use Glitch\Stack\Trace;

use Glitch\Dumper\ObjectInspect as Obj;

class Inspector
{
    const OBJECTS = [
        // Core
        'Closure' => [Obj\Core::class, 'inspectClosure'],
        'Generator' => [Obj\Core::class, 'inspectGenerator'],
        '__PHP_Incomplete_Class' => [Obj\Core::class, 'inspectIncompleteClass'],

        // Reflection
        'ReflectionClass' => [Obj\Reflection::class, 'inspectReflectionClass'],
        'ReflectionClassConstant' => [Obj\Reflection::class, 'inspectReflectionClassConstant'],
        'ReflectionZendExtension' => [Obj\Reflection::class, 'inspectReflectionZendExtension'],
        'ReflectionExtension' => [Obj\Reflection::class, 'inspectReflectionExtension'],
        'ReflectionFunction' => [Obj\Reflection::class, 'inspectReflectionFunction'],
        'ReflectionFunctionAbstract' => [Obj\Reflection::class, 'inspectReflectionFunction'],
        'ReflectionMethod' => [Obj\Reflection::class, 'inspectReflectionMethod'],
        'ReflectionParameter' => [Obj\Reflection::class, 'inspectReflectionParameter'],
        'ReflectionProperty' => [Obj\Reflection::class, 'inspectReflectionProperty'],
        'ReflectionType' => [Obj\Reflection::class, 'inspectReflectionType'],
        'ReflectionGenerator' => [Obj\Reflection::class, 'inspectReflectionGenerator'],
    ];

    protected $objectInspectors = [];


    /**
     * Construct with context to generate object inspectors
     */
    public function __construct(IContext $context)
    {
        $this->objectInspectors = static::OBJECTS;

        foreach ($context->getObjectInspectors() as $class => $inspector) {
            $this->objectInspectors[$class] = $inspector;
        }
    }


    /**
     * Inspect single value
     */
    public function inspectValue($value)
    {
        switch (true) {
            case $value === null:
            case is_bool($value):
            case is_int($value):
            case is_float($value):
                return $value;

            case is_string($value):
                return $this->inspectString($value);

            case is_resource($value):
                return $this->inspectResource($value);

            case is_array($value):
                return $this->inspectArray($value);

            case is_object($value):
                return $this->inspectObject($value);

            default:
                throw \Glitch::EUnexpectedValue(
                    'Unknown entity type',
                    null,
                    $value
                );
        }
    }

    /**
     * Inspect values list
     */
    public function inspectValues(array $values): array
    {
        $output = [];

        foreach ($values as $key => $value) {
            $output[$key] = $this->inspectValue($value);
        }

        return $output;
    }


    /**
     * Convert string into Entity
     */
    public function inspectString(string $string)
    {
        // Binary string
        if ($string !== '' && !preg_match('//u', $string)) {
            return (new Entity('binary'))
                ->setName('Binary')
                ->setText(bin2hex($string))
                ->setLength(strlen($string));

        // Class name
        } elseif (class_exists($string)) {
            return (new Entity('class'))
                ->setClass($string);

        // Interface name
        } elseif (interface_exists($string)) {
            return (new Entity('interface'))
                ->setClass($string);


        // Standard string
        } else {
            return $string;
        }
    }

    /**
     * Convert resource into Entity
     */
    public function inspectResource($resource): Entity
    {
        $output = (new Entity('resource'))
            ->setName((string)$resource)
            ->setClass($rType = get_resource_type($resource));

        $typeName = str_replace(' ', '', ucwords($rType));
        $method = 'inspect'.ucfirst($typeName).'Resource';

        if (method_exists($this, $method)) {
            $this->{$method}($resource, $output);
        }

        return $output;
    }

    /**
     * Inspect curl resource
     */
    public function inspectCurlResource($resource, Entity $entity): void
    {
        foreach (curl_getinfo($resource) as $key => $value) {
            $entity->setMeta($key, $this->inspectValue($value));
        }
    }

    /**
     * Inspect dba resource
     */
    public function inspectDbaResource($resource, Entity $entity): void
    {
        $list = dba_list();
        $entity->setMeta('file', $this->inspectValue($list[(int)$resource]));
    }

    /**
     * Inspect dba persistent resource
     */
    public function inspectDbaPersistentResource($resource, Entity $entity): void
    {
        $this->inspectDbaResource($resource, $entity);
    }

    /**
     * Inspect gd resource
     */
    public function inspectGdResource($resource, Entity $entity): void
    {
        $entity
            ->setMeta('width', $this->inspectValue(imagesx($resource)))
            ->setMeta('height', $this->inspectValue(imagesy($resource)));
    }

    /**
     * Inspect pgsql resource
     */
    public function inspectPgsqlLinkResource($resource, Entity $entity): void
    {
    }

    /**
     * Inspect pgsql persistent resource
     */
    public function inspectPgsqlPersistentLinkResource($resource, Entity $entity): void
    {
    }

    /**
     * Inspect pgsql large object resource
     */
    public function inspectPgsqlLargeObjectResource($resource, Entity $entity): void
    {
    }

    /**
     * Inspect pgsql result resource
     */
    public function inspectPgsqlResultResource($resource, Entity $entity): void
    {
    }

    /**
     * Inspect process resource
     */
    public function inspectProcessResource($resource, Entity $entity): void
    {
        foreach (proc_get_status($resource) as $key => $value) {
            $entity->setMeta($key, $this->inspectValue($value));
        }
    }

    /**
     * Inspect stream resource
     */
    public function inspectStreamResource($resource, Entity $entity): void
    {
        foreach (stream_get_meta_data($resource) as $key => $value) {
            $entity->setMeta($key, $this->inspectValue($value));
        }

        $this->inspectStreamContextResource($resource, $entity);
    }

    /**
     * Inspect persistent stream resource
     */
    public function inspectPersistentStreamResource($resource, Entity $entity): void
    {
        $this->inspectStreamResource($resource, $entity);
    }

    /**
     * Inspect stream context resource
     */
    public function inspectStreamContextResource($resource, Entity $entity): void
    {
        if (!$params = @stream_context_get_params($resource)) {
            return;
        }

        foreach ($params as $key => $value) {
            $entity->setMeta($key, $this->inspectValue($value));
        }
    }

    /**
     * Inspect xml resource
     */
    public function inspectXmlResource($resource, Entity $entity): void
    {
        $entity
            ->setMeta('current_byte_index', $this->inspectValue(xml_get_current_byte_index($resource)))
            ->setMeta('current_column_number', $this->inspectValue(xml_get_current_column_number($resource)))
            ->setMeta('current_line_number', $this->inspectValue(xml_get_current_line_number($resource)))
            ->setMeta('error_code', $this->inspectValue(xml_get_error_code($resource)));
    }






    /**
     * Convert array into Entity
     */
    public function inspectArray(array $array): ?Entity
    {
        return (new Entity('array'))
            ->setClass('array')
            ->setLength(count($array))
            ->setValues($this->inspectValues($array));
    }



    /**
     * Convert object into Entity
     */
    public function inspectObject(object $object): ?Entity
    {
        $reflection = new \ReflectionObject($object);

        $entity = (new Entity('object'))
            ->setName($reflection->getShortName())
            ->setClass($this->normalizeClassName($className = $reflection->getName(), $reflection))
            ->setObjectId(spl_object_id($object));

        if (!$reflection->isInternal()) {
            $entity
                ->setFile($reflection->getFileName())
                ->setStartLine($reflection->getStartLine())
                ->setEndLine($reflection->getEndLine());
        }

        $parents = $this->inspectObjectParents($reflection, $entity);

        $reflections = [
            $className => $reflection
        ] + $parents;

        $this->inspectObjectProperties($object, $reflections, $entity);

        return $entity;
    }

    /**
     * Normalize virtual class name
     */
    protected function normalizeClassName(string $class, \ReflectionObject $reflection): string
    {
        if (0 === strpos($class, "class@anonymous\x00")) {
            $class = $reflection->getParentClass()->getShortName().'@anonymous';
        }

        return $class;
    }


    /**
     * Inspect object parents
     */
    protected function inspectObjectParents(\ReflectionObject $reflection, Entity $entity): array
    {
        // Parents
        $reflectionBase = $reflection;
        $parents = [];

        while (true) {
            if (!$ref = $reflectionBase->getParentClass()) {
                break;
            }

            $parents[$ref->getName()] = $ref;
            $reflectionBase = $ref;
        }

        $entity
            ->setParentClasses(...array_keys($parents))
            ->setInterfaces(...$reflection->getInterfaceNames())
            ->setTraits(...$reflection->getTraitNames());

        return $parents;
    }

    /**
     * Find object property provider
     */
    protected function inspectObjectProperties(object $object, array $reflections, Entity $entity): void
    {
        $className = get_class($object);

        // Object inspector
        if (isset($this->objectInspectors[$className])) {
            call_user_func($this->objectInspectors[$className], $object, $entity, $this);
            return;

        // Inspectable
        } elseif ($object instanceof IInspectable) {
            $object->glitchInspect($entity, $this);
            return;

        // Debug info
        } elseif (method_exists($object, '__debugInfo')) {
            $entity->setValues($this->inspectValues($object->__debugInfo()));
            return;
        }


        // Members
        foreach (array_reverse($reflections) as $className => $reflection) {
            // Parent object inspectors
            if (isset($this->objectInspectors[$className])) {
                call_user_func($this->objectInspectors[$className], $object, $entity, $this);
                continue;
            }

            // Reflection
            $this->inspectClassMembers($object, $reflection, $entity);
        }
    }

    /**
     * Inspect class members
     */
    protected function inspectClassMembers(object $object, \ReflectionClass $reflection, Entity $entity): void
    {
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $property->setAccessible(true);
            $name = $property->getName();
            $prefix = null;

            switch (true) {
                case $property->isProtected():
                    $prefix = '*';
                    break;

                case $property->isPrivate():
                    $prefix = '!';
                    break;
            }

            if ($entity->hasProperty($name)) {
                continue;
            }

            $value = $property->getValue($object);
            $entity->setProperty($name, $this->inspectValue($value));
        }
    }


    /**
     * Convert a scalar value to a string
     */
    public static function scalarToString($value): ?string
    {
        switch (true) {
            case $value === null:
                return 'null';

            case is_bool($value):
                return $value ? 'true' : 'false';

            case is_int($value):
            case is_float($value):
                return (string)$value;

            case is_string($value):
                return '"'.$value.'"';

            default:
                return (string)$value;
        }
    }
}
