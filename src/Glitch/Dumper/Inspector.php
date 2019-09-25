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

        // Date
        'DateTime' => [Inspect\Date::class, 'inspectDateTime'],
        'DateInterval' => [Inspect\Date::class, 'inspectDateInterval'],
        'DateTimeZone' => [Inspect\Date::class, 'inspectDateTimeZone'],
        'DatePeriod' => [Inspect\Date::class, 'inspectDatePeriod'],

        // DOM
        'DOMAttr' => [Inspect\Dom::class, 'inspectAttr'],
        'DOMCdataSection' => [Inspect\Dom::class, 'inspectCdataSection'],
        'DOMCharacterData' => [Inspect\Dom::class, 'inspectCharacterData'],
        'DOMComment' => [Inspect\Dom::class, 'inspectComment'],
        'DOMDocument' => [Inspect\Dom::class, 'inspectDocument'],
        'DOMDocumentFragment' => [Inspect\Dom::class, 'inspectDocumentFragment'],
        'DOMDocumentType' => [Inspect\Dom::class, 'inspectDocumentType'],
        'DOMElement' => [Inspect\Dom::class, 'inspectElement'],
        'DOMEntity' => [Inspect\Dom::class, 'inspectEntity'],
        'DOMEntityReference' => [Inspect\Dom::class, 'inspectEntityReference'],
        'DOMImplementation' => [Inspect\Dom::class, 'inspectImplementation'],
        'DOMNamedNodeMap' => [Inspect\Dom::class, 'inspectNamedNodeMap'],
        'DOMNode' => [Inspect\Dom::class, 'inspectNode'],
        'DOMNodeList' => [Inspect\Dom::class, 'inspectNodeList'],
        'DOMNotation' => [Inspect\Dom::class, 'inspectNotation'],
        'DOMProcessingInstruction' => [Inspect\Dom::class, 'inspectProcessingInstruction'],
        'DOMText' => [Inspect\Dom::class, 'inspectText'],
        'DOMXPath' => [Inspect\Dom::class, 'inspectXPath'],


        // Ds
        'Ds\\Vector' => [Inspect\Ds::class, 'inspectCollection'],
        'Ds\\Map' => [Inspect\Ds::class, 'inspectCollection'],
        'Ds\\Deque' => [Inspect\Ds::class, 'inspectCollection'],
        'Ds\\Pair' => [Inspect\Ds::class, 'inspectPair'],
        'Ds\\Set' => [Inspect\Ds::class, 'inspectSet'],
        'Ds\\Stack' => [Inspect\Ds::class, 'inspectCollection'],
        'Ds\\Queue' => [Inspect\Ds::class, 'inspectCollection'],
        'Ds\\PriorityQueue' => [Inspect\Ds::class, 'inspectCollection'],

        // GMP
        'GMP' => [Inspect\Gmp::class, 'inspectGmp'],

        // PDO
        'PDO' => [Inspect\PDO::class, 'inspectPdo'],
        'PDOStatement' => [Inspect\PDO::class, 'inspectPdoStatement'],

        // Redis
        'Redis' => [Inspect\Redis::class, 'inspectRedis'],


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

        // Spl
        'ArrayObject' => [Inspect\Spl::class, 'inspectArrayObject'],
        'ArrayIterator' => [Inspect\Spl::class, 'inspectArrayIterator'],
        'SplDoublyLinkedList' => [Inspect\Spl::class, 'inspectSplDoublyLinkedList'],
        'SplHeap' => [Inspect\Spl::class, 'inspectSplHeap'],
        'SplPriorityQueue' => [Inspect\Spl::class, 'inspectSplPriorityQueue'],
        'SplFixedArray' => [Inspect\Spl::class, 'inspectSplFixedArray'],
        'SplObjectStorage' => [Inspect\Spl::class, 'inspectSplObjectStorage'],

        'SplFileInfo' => [Inspect\Spl::class, 'inspectSplFileInfo'],
        'SplFileObject' => [Inspect\Spl::class, 'inspectSplFileObject'],

        // Xml
        'SimpleXMLElement' => [Inspect\Xml::class, 'inspectSimpleXmlElement'],
        'XMLWriter' => [Inspect\Xml::class, 'inspectXmlWriter']
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

    protected $objectIds = [];
    protected $objectRefs = [];

    protected $arrayIds = [];
    protected $arrayObjectId = 0;
    protected $arrayCookies = [];
    protected $arrayCookieKey;


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
     * Reset all references
     */
    public function reset(): Inspector
    {
        $this->objectIds = [];
        $this->objectRefs = [];

        $this->arrayIds = [];
        $this->arrayObjectId = 0;
        $this->arrayCookies = [];
        $this->arrayCookieKey = null;

        return $this;
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
    public function inspectValue(&$value)
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
                ->setClass($string);

        // Interface name
        } elseif ($isPossibleClass && interface_exists($string, $loadClasses)) {
            return (new Entity('interface'))
                ->setClass($string);

        // Trait name
        } elseif ($isPossibleClass && trait_exists($string, $loadClasses)) {
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
        $parts = explode('#', (string)$resource);
        $id = array_pop($parts);

        $entity = (new Entity('resource'))
            ->setName('resource')
            ->setClass($rType = get_resource_type($resource))
            ->setObjectId((int)$id);

        $typeName = str_replace(' ', '', ucwords($rType));
        $method = 'inspect'.ucfirst($typeName).'Resource';

        if (isset($this->resourceInspectors[$rType])) {
            call_user_func($this->resourceInspectors[$rType], $resource, $entity, $this);
        }

        return $entity;
    }


    /**
     * Name single flag from set
     */
    public function inspectFlag($flag, array $options): ?Entity
    {
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
     */
    public function inspectFlagSet(?int $flags, array $options): Entity
    {
        $entity = (new Entity('flags'))
            ->setName($name ?? 'bitset');

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
    public function inspectConstant(string $const): Entity
    {
        $entity = (new Entity('const'))
            ->setName($const)
            ->setLength(constant($const));

        return $entity;
    }



    /**
     * Convert array into Entity
     */
    public function inspectArray(array &$array): ?Entity
    {
        if (!isset($this->arrayCookieKey)) {
            $this->arrayCookieKey = uniqid('__glitch_array_cookie_', true);
        }

        $empty = empty($array);

        if (isset($array[$this->arrayCookieKey])) {
            $isRef = true;
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

        $entity = (new Entity($isRef ? 'arrayReference' : 'array'))
            //->setClass('array')
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
    public function inspectObject(object $object, bool $properties=true): ?Entity
    {
        $objectId = spl_object_id($object);
        $reflection = new \ReflectionObject($object);
        $className = $reflection->getName();
        $isRef = isset($this->objectIds[$objectId]);

        $entity = (new Entity($isRef ? 'objectReference' : 'object'))
            ->setName($this->normalizeClassName($reflection->getShortName(), $reflection))
            //->setClass($className)
            ->setObjectId($objectId)
            ->setHash(spl_object_hash($object));

        if ($object instanceof \Countable) {
            $entity->setLength($object->count());
        }


        if (!$reflection->isInternal()) {
            $entity
                ->setFile($reflection->getFileName())
                ->setStartLine($reflection->getStartLine())
                ->setEndLine($reflection->getEndLine());
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
    protected function normalizeClassName(string $class, \ReflectionObject $reflection): string
    {
        if (false !== strpos($class, 'Glitch/Exception/Factory.php')) {
            $class = 'EGlitch';
        } elseif (0 === strpos($class, "class@anonymous\x00")) {
            if ($parent = $reflection->getParentClass()) {
                $class = $parent->getShortName().'@anonymous';
            } else {
                $class = '@anonymous';
            }
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


        // Reflection members
        foreach (array_reverse($reflections) as $className => $reflection) {
            $this->inspectClassMembers($object, $reflection, $entity);
        }
    }

    /**
     * Inspect class members
     */
    public function inspectClassMembers(object $object, \ReflectionClass $reflection, Entity $entity, array $blackList=[], bool $asMeta=false): void
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

            if (!$asMeta) {
                $name = $prefix.$name;
            }

            if ($asMeta && $entity->hasMeta($name)) {
                continue;
            } elseif ($entity->hasProperty($name)) {
                continue;
            }

            $value = $property->getValue($object);
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
     */
    public static function hashArray(array $array): ?string
    {
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
