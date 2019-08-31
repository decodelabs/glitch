<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Dumper;

use DecodeLabs\Glitch\Context;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Stack\Trace;

use DecodeLabs\Glitch\Dumper\Inspect;

class Inspector
{
    const OBJECTS = [
        // Core
        'Exception' => [Inspect\Core::class, 'inspectException'],
        'Closure' => [Inspect\Core::class, 'inspectClosure'],
        'Generator' => [Inspect\Core::class, 'inspectGenerator'],
        '__PHP_Incomplete_Class' => [Inspect\Core::class, 'inspectIncompleteClass'],

        // Reflection
        'ReflectionClass' => [Inspect\Reflection::class, 'inspectReflectionClass'],
        'ReflectionClassConstant' => [Inspect\Reflection::class, 'inspectReflectionClassConstant'],
        'ReflectionZendExtension' => [Inspect\Reflection::class, 'inspectReflectionZendExtension'],
        'ReflectionExtension' => [Inspect\Reflection::class, 'inspectReflectionExtension'],
        'ReflectionFunction' => [Inspect\Reflection::class, 'inspectReflectionFunction'],
        'ReflectionFunctionAbstract' => [Inspect\Reflection::class, 'inspectReflectionFunction'],
        'ReflectionMethod' => [Inspect\Reflection::class, 'inspectReflectionMethod'],
        'ReflectionParameter' => [Inspect\Reflection::class, 'inspectReflectionParameter'],
        'ReflectionProperty' => [Inspect\Reflection::class, 'inspectReflectionProperty'],
        'ReflectionType' => [Inspect\Reflection::class, 'inspectReflectionType'],
        'ReflectionGenerator' => [Inspect\Reflection::class, 'inspectReflectionGenerator'],
    ];

    const RESOURCES = [
        // Bzip
        'bzip2' => null,

        // Cubrid
        'cubrid connection' => null,
        'persistent cubrid connection' => null,
        'cubrid request' => null,
        'cubrid lob' => null,
        'cubrid lob2' => null,

        // Curl
        'curl' => [Inspect\Curl::class, 'inspectCurl'],

        // Dba
        'dba' => [Inspect\Dba::class, 'inspectDba'],
        'dba persistent' => [Inspect\Dba::class, 'inspectDba'],

        // Dbase
        'dbase' => null,

        // DBX
        'dbx_link_object' => null,
        'dbx_result_object' => null,

        // Firebird
        'fbsql link' => null,
        'fbsql plink' => null,
        'fbsql result' => null,

        // FDF
        'fdf' => null,

        // FTP
        'ftp' => null,

        // GD
        'gd' => [Inspect\Gd::class, 'inspectGd'],
        'gd font' => [Inspect\Gd::class, 'inspectGdFont'],

        // Imap
        'imap' => null,

        // Ingres
        'ingres' => null,
        'ingres persistent' => null,

        // Interbase
        'interbase link' => null,
        'interbase link persistent' => null,
        'interbase query' => null,
        'interbase result' => null,

        // Ldap
        'ldap link' => null,
        'ldap result' => null,

        // mSQL
        'msql link' => null,
        'msql link persistent' => null,
        'msql query' => null,

        // msSQL
        'mssql link' => null,
        'mssql link persistent' => null,
        'mssql result' => null,

        // Oci8
        'oci8 collection' => null,
        'oci8 connection' => null,
        'oci8 lob' => null,
        'oci8 statement' => null,

        // Odbc
        'odbc link' => null,
        'odbc link persistent' => null,
        'odbc result' => null,

        // OpenSSL
        'OpenSSL key' => null,
        'OpenSSL X.509' => null,

        // PDF
        'pdf document' => null,
        'pdf image' => null,
        'pdf object' => null,
        'pdf outline' => null,

        // PgSQL
        'pgsql large object' => null,
        'pgsql link' => null,
        'pgsql link persistent' => null,
        'pgsql result' => null,

        // Process
        'process' => [Inspect\Process::class, 'inspectProcess'],

        // Pspell
        'pspell' => null,
        'pspell config' => null,

        // Shmop
        'shmop' => null,

        // Stream
        'stream' => [Inspect\Stream::class, 'inspectStream'],

        // Socket
        'socket' => null,

        // Sybase
        'sybase-db link' => null,
        'sybase-db link persistent' => null,
        'sybase-db result' => null,
        'sybase-ct link' => null,
        'sybase-ct link persistent' => null,
        'sybase-ct result' => null,

        // Sysv
        'sysvsem' => null,
        'sysvshm' => null,

        // Wddx
        'wddx' => null,

        // Xml
        'xml' => [Inspect\Xml::class, 'inspectXmlResource'],

        // Zlib
        'zlib' => null,
        'zlib.deflate' => null,
        'zlib.inflate' => null
    ];

    protected $objectInspectors = [];
    protected $resourceInspectors = [];

    protected $objectRefs = [];
    protected $arrayRefs = [];
    protected $arrayIds = [];


    /**
     * Construct with context to generate object inspectors
     */
    public function __construct(Context $context)
    {
        foreach (static::OBJECTS as $class => $inspector) {
            if ($inspector !== null) {
                $this->objectInspectors[$class] = $inspector;
            }
        }

        foreach (static::RESOURCES as $type => $inspector) {
            if ($inspector !== null) {
                $this->resourceInspectors[$type] = $inspector;
            }
        }

        foreach ($context->getObjectInspectors() as $class => $inspector) {
            $this->objectInspectors[$class] = $inspector;
        }

        foreach ($context->getResourceInspectors() as $type => $inspector) {
            $this->resourceInspectors[$type] = $inspector;
        }
    }


    /**
     * Invoke wrapper
     */
    public function __invoke($value, callable $entityCallback=null, bool $asList=false)
    {
        if ($asList) {
            return $this->inspectList((array)$value, $entityCallback);
        } else {
            return $this->inspect($value, $entityCallback);
        }
    }

    /**
     * Inspect and report
     */
    public function inspect($value, callable $entityCallback=null)
    {
        $output = $this->inspectValue($value);

        if ($output instanceof Entity && $entityCallback) {
            $entityCallback($output, $value, $this);
        }

        return $output;
    }

    /**
     * Inspect values list
     */
    public function inspectList(array $values, callable $entityCallback=null): array
    {
        $output = [];

        foreach ($values as $key => $value) {
            $output[$key] = $this->inspect($value, $entityCallback);
        }

        return $output;
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

        // Trait name
        } elseif (trait_exists($string)) {
            return (new Entity('trait'))
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
        $entity = (new Entity('resource'))
            ->setName((string)$resource)
            ->setClass($rType = get_resource_type($resource));

        $typeName = str_replace(' ', '', ucwords($rType));
        $method = 'inspect'.ucfirst($typeName).'Resource';

        if (isset($this->resourceInspectors[$rType])) {
            call_user_func($this->resourceInspectors[$rType], $resource, $entity, $this);
        }

        return $entity;
    }



    /**
     * Convert array into Entity
     */
    public function inspectArray(array $array): ?Entity
    {
        $hash = $this->hashArray($array);
        $isRef = $hash !== null && isset($this->arrayRefs[$hash]);

        $entity = (new Entity($isRef ? 'arrayReference' : 'array'))
            ->setClass('array')
            ->setLength(count($array))
            ->setHash($hash);

        if ($isRef) {
            return $entity
                ->setId($this->arrayRefs[$hash])
                ->setObjectId($this->arrayIds[$hash]);
        }

        if ($hash !== null) {
            $this->arrayRefs[$hash] = $entity->getId();
            $this->arrayIds[$hash] = $id = count($this->arrayIds) + 1;
            $entity->setObjectId($id);
        }

        $entity
            ->setValues($this->inspectList($array));

        return $entity;
    }



    /**
     * Convert object into Entity
     */
    public function inspectObject(object $object): ?Entity
    {
        $id = spl_object_id($object);
        $reflection = new \ReflectionObject($object);
        $className = $reflection->getName();
        $isRef = isset($this->objectRefs[$id]);

        $entity = (new Entity($isRef ? 'objectReference' : 'object'))
            ->setName($this->normalizeClassName($reflection->getShortName(), $reflection))
            ->setClass($className)
            ->setObjectId($id)
            ->setHash(spl_object_hash($object));


        if (!$reflection->isInternal()) {
            $entity
                ->setFile($reflection->getFileName())
                ->setStartLine($reflection->getStartLine())
                ->setEndLine($reflection->getEndLine());
        }

        $parents = $this->inspectObjectParents($reflection, $entity);

        if ($isRef) {
            $entity->setId($this->objectRefs[$id]);
            return $entity;
        } else {
            $this->objectRefs[$id] = $entity->getId();
        }

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
        if (false !== strpos($class, 'Glitch/Factory.php')) {
            $class = 'EGlitch';
        } elseif (0 === strpos($class, "class@anonymous\x00")) {
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

        ksort($parents);
        $interfaces = $reflection->getInterfaceNames();
        sort($interfaces);
        $traits = $reflection->getTraitNames();
        sort($traits);

        $entity
            ->setParentClasses(...array_keys($parents))
            ->setInterfaces(...$interfaces)
            ->setTraits(...$traits);

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
        } elseif ($object instanceof Inspectable) {
            $object->glitchInspect($entity, $this);
            return;

        // Debug info
        } elseif (method_exists($object, '__debugInfo')) {
            $entity->setValues($this->inspectList($object->__debugInfo()));
            return;
        }


        // Parent object inspectors
        foreach (array_reverse($reflections) as $className => $reflection) {
            if (isset($this->objectInspectors[$className])) {
                call_user_func($this->objectInspectors[$className], $object, $entity, $this);
                return;
            }
        }


        // Reflection members
        foreach (array_reverse($reflections) as $className => $reflection) {
            $this->inspectClassMembers($object, $reflection, $entity);
        }
    }

    /**
     * Inspect class members
     */
    public function inspectClassMembers(object $object, \ReflectionClass $reflection, Entity $entity, array $blackList=[]): void
    {
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $property->setAccessible(true);
            $name = $property->getName();

            if (in_array($name, $blackList)) {
                continue;
            }

            $prefix = null;
            $open = false;

            switch (true) {
                case $property->isProtected():
                    $prefix = '*';
                    break;

                case $property->isPrivate():
                    $prefix = '!';
                    break;

                default:
                    $open = true;
                    break;
            }

            $name = $prefix.$name;

            if ($entity->hasProperty($name)) {
                continue;
            }

            $value = $property->getValue($object);
            $propValue = $this->inspectValue($value);

            if ($propValue instanceof Entity) {
                $propValue->setOpen($open);
            }

            $entity->setProperty($name, $propValue);
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



    /**
     * Dirty way to get a hash for an array
     */
    public static function hashArray(array $array): ?string
    {
        if (empty($array)) {
            return null;
        }

        $array = self::smashArray($array);

        return md5(serialize($array));
    }

    /**
     * Normalize values for serialize
     */
    public static function smashArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_object($value)) {
                $array[$key] = spl_object_id($value);
            } elseif (is_array($value)) {
                $array[$key] = self::smashArray($value);
            }
        }

        return $array;
    }
}
