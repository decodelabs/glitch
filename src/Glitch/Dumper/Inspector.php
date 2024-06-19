<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper;

use Countable;

use DecodeLabs\Coercion;
use DecodeLabs\Glitch\Attribute\SensitiveProperty;
use DecodeLabs\Glitch\Context;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Glitch\Dumper\Inspect\Core as InspectCore;
use DecodeLabs\Glitch\Dumper\Inspect\Curl as InspectCurl;
use DecodeLabs\Glitch\Dumper\Inspect\Date as InspectDate;
use DecodeLabs\Glitch\Dumper\Inspect\Dba as InspectDba;
use DecodeLabs\Glitch\Dumper\Inspect\Dom as InspectDom;
use DecodeLabs\Glitch\Dumper\Inspect\Ds as InspectDs;
use DecodeLabs\Glitch\Dumper\Inspect\Gd as InspectGd;
use DecodeLabs\Glitch\Dumper\Inspect\Gmp as InspectGmp;
use DecodeLabs\Glitch\Dumper\Inspect\Pdo as InspectPdo;
use DecodeLabs\Glitch\Dumper\Inspect\Process as InspectProcess;
use DecodeLabs\Glitch\Dumper\Inspect\Redis as InspectRedis;
use DecodeLabs\Glitch\Dumper\Inspect\Reflection as InspectReflection;
use DecodeLabs\Glitch\Dumper\Inspect\Spl as InspectSpl;
use DecodeLabs\Glitch\Dumper\Inspect\Stream as InspectStream;
use DecodeLabs\Glitch\Dumper\Inspect\Xml as InspectXml;
use DecodeLabs\Glitch\Inspectable;

use ReflectionClass;
use ReflectionObject;
use SensitiveParameterValue;

class Inspector
{
    /**
     * @var array<string, array<string>>
     */
    public const OBJECTS = [
        // Core
        'Throwable' => [InspectCore::class, 'inspectException'],
        'Closure' => [InspectCore::class, 'inspectClosure'],
        'Generator' => [InspectCore::class, 'inspectGenerator'],
        'UnitEnum' => [InspectCore::class, 'inspectEnum'],
        'Fiber' => [InspectCore::class, 'inspectFiber'],
        'SensitiveParameterValue' => [InspectCore::class, 'inspectSensitiveParameterValue'],
        '__PHP_Incomplete_Class' => [InspectCore::class, 'inspectIncompleteClass'],

        // Date
        'DateTimeInterface' => [InspectDate::class, 'inspectDateTime'],
        'Carbon\\Carbon' => [InspectDate::class, 'inspectDateTime'],
        'DateInterval' => [InspectDate::class, 'inspectDateInterval'],
        'Carbon\\CarbonInterval' => [InspectDate::class, 'inspectDateInterval'],
        'DateTimeZone' => [InspectDate::class, 'inspectDateTimeZone'],
        'Carbon\\CarbonTimeZone' => [InspectDate::class, 'inspectDateTimeZone'],
        'DatePeriod' => [InspectDate::class, 'inspectDatePeriod'],
        'Carbon\\CarbonPeriod' => [InspectDate::class, 'inspectDatePeriod'],

        // DOM
        'DOMAttr' => [InspectDom::class, 'inspectAttr'],
        'DOMCdataSection' => [InspectDom::class, 'inspectCdataSection'],
        'DOMCharacterData' => [InspectDom::class, 'inspectCharacterData'],
        'DOMComment' => [InspectDom::class, 'inspectComment'],
        'DOMDocument' => [InspectDom::class, 'inspectDocument'],
        'DOMDocumentFragment' => [InspectDom::class, 'inspectDocumentFragment'],
        'DOMDocumentType' => [InspectDom::class, 'inspectDocumentType'],
        'DOMElement' => [InspectDom::class, 'inspectElement'],
        'DOMEntity' => [InspectDom::class, 'inspectEntity'],
        'DOMEntityReference' => [InspectDom::class, 'inspectEntityReference'],
        'DOMImplementation' => [InspectDom::class, 'inspectImplementation'],
        'DOMNamedNodeMap' => [InspectDom::class, 'inspectNamedNodeMap'],
        'DOMNode' => [InspectDom::class, 'inspectNode'],
        'DOMNodeList' => [InspectDom::class, 'inspectNodeList'],
        'DOMNotation' => [InspectDom::class, 'inspectNotation'],
        'DOMProcessingInstruction' => [InspectDom::class, 'inspectProcessingInstruction'],
        'DOMText' => [InspectDom::class, 'inspectText'],
        'DOMXPath' => [InspectDom::class, 'inspectXPath'],


        // Ds
        'Ds\\Vector' => [InspectDs::class, 'inspectCollection'],
        'Ds\\Map' => [InspectDs::class, 'inspectCollection'],
        'Ds\\Deque' => [InspectDs::class, 'inspectCollection'],
        'Ds\\Pair' => [InspectDs::class, 'inspectPair'],
        'Ds\\Set' => [InspectDs::class, 'inspectSet'],
        'Ds\\Stack' => [InspectDs::class, 'inspectCollection'],
        'Ds\\Queue' => [InspectDs::class, 'inspectCollection'],
        'Ds\\PriorityQueue' => [InspectDs::class, 'inspectCollection'],

        // GMP
        'GMP' => [InspectGmp::class, 'inspectGmp'],

        // PDO
        'PDO' => [InspectPdo::class, 'inspectPdo'],
        'PDOStatement' => [InspectPdo::class, 'inspectPdoStatement'],

        // Redis
        'Redis' => [InspectRedis::class, 'inspectRedis'],


        // Reflection
        'ReflectionClass' => [InspectReflection::class, 'inspectReflectionClass'],
        'ReflectionClassConstant' => [InspectReflection::class, 'inspectReflectionClassConstant'],
        'ReflectionZendExtension' => [InspectReflection::class, 'inspectReflectionZendExtension'],
        'ReflectionExtension' => [InspectReflection::class, 'inspectReflectionExtension'],
        'ReflectionFunction' => [InspectReflection::class, 'inspectReflectionFunction'],
        'ReflectionFunctionAbstract' => [InspectReflection::class, 'inspectReflectionFunction'],
        'ReflectionMethod' => [InspectReflection::class, 'inspectReflectionMethod'],
        'ReflectionParameter' => [InspectReflection::class, 'inspectReflectionParameter'],
        'ReflectionProperty' => [InspectReflection::class, 'inspectReflectionProperty'],
        'ReflectionType' => [InspectReflection::class, 'inspectReflectionType'],
        'ReflectionGenerator' => [InspectReflection::class, 'inspectReflectionGenerator'],

        // Spl
        'ArrayObject' => [InspectSpl::class, 'inspectArrayObject'],
        'ArrayIterator' => [InspectSpl::class, 'inspectArrayIterator'],
        'SplDoublyLinkedList' => [InspectSpl::class, 'inspectSplDoublyLinkedList'],
        'SplHeap' => [InspectSpl::class, 'inspectSplHeap'],
        'SplPriorityQueue' => [InspectSpl::class, 'inspectSplPriorityQueue'],
        'SplFixedArray' => [InspectSpl::class, 'inspectSplFixedArray'],
        'SplObjectStorage' => [InspectSpl::class, 'inspectSplObjectStorage'],

        'SplFileInfo' => [InspectSpl::class, 'inspectSplFileInfo'],
        'SplFileObject' => [InspectSpl::class, 'inspectSplFileObject'],

        // Xml
        'SimpleXMLElement' => [InspectXml::class, 'inspectSimpleXmlElement'],
        'XMLWriter' => [InspectXml::class, 'inspectXmlWriter']
    ];

    /**
     * @var array<string, array<string>|null>
     */
    public const RESOURCES = [
        // Bzip
        'bzip2' => null,

        // Cubrid
        'cubrid connection' => null,
        'persistent cubrid connection' => null,
        'cubrid request' => null,
        'cubrid lob' => null,
        'cubrid lob2' => null,

        // Curl
        'curl' => [InspectCurl::class, 'inspectCurl'],

        // Dba
        'dba' => [InspectDba::class, 'inspectDba'],
        'dba persistent' => [InspectDba::class, 'inspectDba'],

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
        'gd' => [InspectGd::class, 'inspectGd'],
        'gd font' => [InspectGd::class, 'inspectGdFont'],

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
        'process' => [InspectProcess::class, 'inspectProcess'],

        // Pspell
        'pspell' => null,
        'pspell config' => null,

        // Shmop
        'shmop' => null,

        // Stream
        'stream' => [InspectStream::class, 'inspectStream'],

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
        'xml' => [InspectXml::class, 'inspectXmlResource'],

        // Zlib
        'zlib' => null,
        'zlib.deflate' => null,
        'zlib.inflate' => null
    ];

    /**
     * @var array<string, callable>
     */
    protected array $objectInspectors = [];

    /**
     * @var array<string, callable>
     */
    protected array $resourceInspectors = [];


    /**
     * @var array<int, string|null>
     */
    protected array $objectIds = [];

    /**
     * @var array<int, object>
     */
    protected array $objectRefs = [];


    protected int $arrayObjectId = 0;

    /**
     * @var array<string, array<string|int|null>>
     */
    protected array $arrayCookies = [];

    protected ?string $arrayCookieKey = null;


    /**
     * Construct with context to generate object inspectors
     */
    public function __construct(
        Context $context
    ) {
        foreach (static::OBJECTS as $class => $inspector) {
            if (is_callable($inspector)) {
                $this->objectInspectors[$class] = $inspector;
            }
        }

        foreach (static::RESOURCES as $type => $inspector) {
            if (is_callable($inspector)) {
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
     * Reset all references
     *
     * @return $this
     */
    public function reset(): static
    {
        $this->objectIds = [];
        $this->objectRefs = [];

        $this->arrayObjectId = 0;
        $this->arrayCookies = [];
        $this->arrayCookieKey = null;

        return $this;
    }


    /**
     * Convert scalar to string
     *
     * @param scalar|resource|null $value
     */
    public static function scalarToString(
        $value
    ): string {
        switch (true) {
            case $value === null:
                return 'null';

            case is_bool($value):
                return $value ? 'true' : 'false';

            case is_int($value):
            case is_float($value):
                return (string)$value;

            case is_string($value):
                return '"' . $value . '"';

            case is_resource($value):
                return (string)$value;


            default:
                return (string)$value;
        }
    }


    /**
     * Invoke wrapper
     */
    public function __invoke(
        mixed $value,
        callable $entityCallback = null,
        bool $asList = false
    ): mixed {
        if ($asList) {
            return $this->inspectList((array)$value, $entityCallback);
        } else {
            return $this->inspect($value, $entityCallback);
        }
    }

    /**
     * Inspect and report
     */
    public function inspect(
        mixed $value,
        callable $entityCallback = null
    ): mixed {
        $output = $this->inspectValue($value);

        if ($output instanceof Entity && $entityCallback) {
            $entityCallback($output, $value, $this);
        }

        return $output;
    }

    /**
     * Inspect values list
     *
     * @param array<mixed> $values
     * @return array<int|string, mixed>
     */
    public function inspectList(
        array $values,
        callable $entityCallback = null
    ): array {
        $output = [];

        foreach ($values as $key => $value) {
            $output[$key] = $this->inspect($value, $entityCallback);
        }

        return $output;
    }



    /**
     * Inspect single value
     */
    public function inspectValue(
        mixed &$value
    ): mixed {
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
                return $this->inspectString(Coercion::forceString($value));
        }
    }



    /**
     * Convert string into Entity
     */
    public function inspectString(
        string $string
    ): Entity|string {
        $isPossibleClass = preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $string);
        $loadClasses = false !== strpos($string, '\\');

        // Binary string
        if ($string !== '' && !preg_match('//u', $string)) {
            return (new Entity('binary'))
                ->setName('Binary')
                ->setText(bin2hex($string))
                ->setLength(strlen($string));

            // Class name
        } elseif ($isPossibleClass && class_exists($string, $loadClasses)) {
            return (new Entity('class'))
                ->setClassName($string);

            // Interface name
        } elseif ($isPossibleClass && interface_exists($string, $loadClasses)) {
            return (new Entity('interface'))
                ->setClassName($string);

            // Trait name
        } elseif ($isPossibleClass && trait_exists($string, $loadClasses)) {
            return (new Entity('trait'))
                ->setClassName($string);


            // Standard string
        } else {
            return $string;
        }
    }

    /**
     * Convert resource into Entity
     *
     * @param resource $resource
     */
    public function inspectResource(
        $resource
    ): Entity {
        $parts = explode('#', (string)$resource);
        $id = array_pop($parts);

        $entity = (new Entity('resource'))
            ->setName('resource')
            ->setClassName($rType = get_resource_type($resource))
            ->setObjectId((int)$id);

        if (isset($this->resourceInspectors[$rType])) {
            call_user_func($this->resourceInspectors[$rType], $resource, $entity, $this);
        }

        return $entity;
    }


    /**
     * Name single flag from set
     *
     * @param array<string> $options
     */
    public function inspectFlag(
        mixed $flag,
        array $options
    ): ?Entity {
        if (!is_string($flag) && !is_int($flag)) {
            return null;
        }

        foreach ($options as $const) {
            $value = constant($const);

            if (!is_int($value)) {
                continue;
            }

            if ($flag === $value) {
                return $this->inspectConstant($const);
            }
        }

        return null;
    }


    /**
     * Create flag list
     *
     * @param array<string> $options
     */
    public function inspectFlagSet(
        ?int $flags,
        array $options
    ): Entity {
        $entity = (new Entity('flags'))
            ->setName('bitset');

        $set = [];

        foreach ($options as $const) {
            $value = constant($const);

            if (!is_int($value)) {
                continue;
            }

            if (($flags & $value) === $value || ($flags === 0 && $value === 0)) {
                $constEnt = $this->inspectConstant($const);

                if ($flags === $value) {
                    $set = [$constEnt];
                    break;
                }

                $set[] = $constEnt;
            }
        }

        $entity
            ->setLength($flags)
            ->setValues($set)
            ->setShowKeys(false)
            ->setOpen(false);

        return $entity;
    }


    /**
     * Inspect const by string
     */
    public function inspectConstant(
        string $const
    ): Entity {
        return (new Entity('const'))
            ->setName($const)
            ->setLength(Coercion::toIntOrNull(constant($const)));
    }



    /**
     * Convert array into Entity
     *
     * @param array<mixed> $array
     */
    public function inspectArray(
        array &$array
    ): ?Entity {
        if (!isset($this->arrayCookieKey)) {
            $this->arrayCookieKey = uniqid('__glitch_array_cookie_', true);
        }

        $empty = empty($array);

        if (isset($array[$this->arrayCookieKey])) {
            $isRef = true;
            /** @var string $id */
            $id = $array[$this->arrayCookieKey];
            [$hash, $objectId] = $this->arrayCookies[$id];
        } else {
            $isRef = false;
            $hash = $objectId = null;
            $id = str_replace('.', '-', uniqid('array-', true));

            if (!$empty) {
                $array[$this->arrayCookieKey] = false;
                $hash = $this->hashArray($array);
                $array[$this->arrayCookieKey] = $id;
                $objectId = $this->arrayObjectId++;
            }

            $this->arrayCookies[$id] = [$hash, $objectId];
        }

        /**
         * @var string|null $hash
         * @var int|null $objectId
         */

        $entity = (new Entity($isRef ? 'arrayReference' : 'array'))
            ->setClass('array')
            ->setLength($empty ? 0 : count($array) - 1)
            ->setHash($hash)
            ->setId($id)
            ->setObjectId($objectId);


        if ($isRef) {
            return $entity;
        }


        $values = [];

        foreach ($array as $key => &$value) {
            if ($key === $this->arrayCookieKey) {
                continue;
            }

            $values[$key] = $this->inspectValue($value);
        }

        $entity->setValues($values);

        return $entity;
    }


    /**
     * Convert object into Entity
     */
    public function inspectObject(
        object $object,
        bool $properties = true
    ): ?Entity {
        $objectId = spl_object_id($object);
        $reflection = null;
        $className = get_class($object);

        switch ($className) {
            // Skip these
            case 'EventBase':
                $name = $shortName = $className;
                break;

            default:
                $reflection = new ReflectionObject($object);
                $shortName = $reflection->getShortName();
                $name = $this->normalizeClassName($shortName, $reflection);
                break;
        }

        $isRef = isset($this->objectIds[$objectId]);

        // Add parent namespace to name if it's also an interface
        if ($name === $shortName && $reflection) {
            $parts = explode('\\', $className);
            array_pop($parts);
            $parentNs = array_pop($parts);

            if (!empty($parentNs)) {
                foreach ($reflection->getInterfaces() as $interface) {
                    $interfaceName = $interface->getShortName();

                    if ($parentNs === $interfaceName) {
                        $name = $parentNs . '\\' . $name;
                        break;
                    }
                }
            }
        }

        $entity = (new Entity($isRef ? 'objectReference' : 'object'))
            ->setName($name)
            ->setClass($className)
            ->setObjectId($objectId)
            ->setHash(spl_object_hash($object));

        /*
        if ($object instanceof Countable) {
            $entity->setLength($object->count());
        }
        */

        if (!$reflection) {
            return $entity;
        }


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

        $parents = $this->inspectObjectParents($reflection, $entity);

        if ($isRef) {
            $entity->setId($this->objectIds[$objectId]);
            return $entity;
        } else {
            $this->objectRefs[$objectId] = $object;
            $this->objectIds[$objectId] = $entity->getId();
        }

        $reflections = [
            $className => $reflection
        ] + $parents;

        if ($properties) {
            $this->inspectObjectProperties($object, $reflections, $entity);
        }

        return $entity;
    }

    /**
     * Normalize virtual class name
     */
    protected function normalizeClassName(
        string $class,
        ReflectionObject $reflection
    ): string {
        if (0 === strpos($class, "class@anonymous\x00")) {
            if ($parent = $reflection->getParentClass()) {
                $class = $parent->getShortName() . '@anonymous';
            } else {
                $class = '@anonymous';
            }
        }

        return $class;
    }


    /**
     * Inspect object parents
     *
     * @return array<string, ReflectionClass<object>>
     */
    protected function inspectObjectParents(
        ReflectionObject $reflection,
        Entity $entity
    ): array {
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
     *
     * @template T of object
     * @param array<string, ReflectionClass<T>> $reflections
     */
    protected function inspectObjectProperties(
        object $object,
        array $reflections,
        Entity $entity
    ): void {
        $className = get_class($object);

        // Export
        if (
            $object instanceof Dumpable ||
            method_exists($object, 'glitchDump')
        ) {
            foreach ($object->glitchDump() as $key => $value) {
                $entity->importDumpValue($object, $key, $value, $this);
            }
            return;

            // Inspectable
        } elseif (
            $object instanceof Inspectable ||
            method_exists($object, 'glitchInspect')
        ) {
            $object->glitchInspect($entity, $this);
            return;

            // Object inspector
        } elseif (isset($this->objectInspectors[$className])) {
            call_user_func($this->objectInspectors[$className], $object, $entity, $this);
            return;

            // Debug info
        } elseif (method_exists($object, '__debugInfo')) {
            $entity->setValues($this->inspectList($info = $object->__debugInfo()));
            return;
        }


        // Parent object inspectors
        foreach (array_reverse($reflections) as $className => $reflection) {
            if (isset($this->objectInspectors[$className])) {
                call_user_func($this->objectInspectors[$className], $object, $entity, $this);
                return;
            }
        }

        // Interfaces
        $ref = new ReflectionClass($object);

        foreach ($ref->getInterfaceNames() as $interfaceName) {
            if (isset($this->objectInspectors[$interfaceName])) {
                call_user_func($this->objectInspectors[$interfaceName], $object, $entity, $this);
                return;
            }
        }


        // Reflection members
        foreach (array_reverse($reflections) as $reflection) {
            $this->inspectClassMembers($object, $reflection, $entity);
        }
    }

    /**
     * Inspect class members
     *
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @param array<string> $blackList
     */
    public function inspectClassMembers(
        object $object,
        ReflectionClass $reflection,
        Entity $entity,
        array $blackList = [],
        bool $asMeta = false
    ): void {
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

            if (!$asMeta) {
                $name = $prefix . $name;
            }

            if (
                $asMeta &&
                $entity->hasMeta($name)
            ) {
                continue;
            } elseif ($entity->hasProperty($name)) {
                continue;
            }


            // Get value
            if ($property->isInitialized($object)) {
                $value = $property->getValue($object);
            } else {
                $value = null;
            }

            // Check sensitive
            if (!empty($property->getAttributes(SensitiveProperty::class))) {
                $value = new SensitiveParameterValue($value);
            }

            // Inspect value
            $propValue = $this->inspectValue($value);

            if ($propValue instanceof Entity) {
                $propValue->setOpen($open);
            }

            if ($asMeta) {
                $entity->setMeta($name, $propValue);
            } else {
                $entity->setProperty($name, $propValue);
            }
        }
    }


    /**
     * Dirty way to get a hash for an array
     *
     * @param array<mixed> $array
     */
    public static function hashArray(
        array $array
    ): ?string {
        if (empty($array)) {
            return null;
        }

        $exp = print_r($array, true);

        if (false === strpos($exp, '*RECURSION*')) {
            //$exp .= '#'.uniqid();
        }

        return md5($exp);
    }
}
