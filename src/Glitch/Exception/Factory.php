<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Exception;

/**
 * Automatically generate Exceptions on the fly based on scope and
 * requested interface types
 */
class Factory
{
    const STANDARD = [
        'ELogic' => [
            'type' => 'LogicException'
        ],
            'EBadFunctionCall' => [
                'extend' => 'ELogic',
                'type' => 'BadFunctionCallException'
            ],
                'EBadMethodCall' => [
                    'extend' => 'EBadFunctionCall',
                    'type' => 'BadMethodCallException'
                ],

            'EDomain' => [
                'extend' => 'ELogic',
                'type' => 'DomainException'
            ],
            'EInvalidArgument' => [
                'extend' => 'ELogic',
                'type' => 'InvalidArgumentException'
            ],
            'ELength' => [
                'extend' => 'ELogic',
                'type' => 'LengthException'
            ],
            'EOutOfRange' => [
                'extend' => 'ELogic',
                'type' => 'OutOfRangeException'
            ],

            'EDefinition' => [
                'extend' => 'ELogic'
            ],
            'EImplementation' => [
                'extend' => 'ELogic'
            ],
                'ENotImplemented' => [
                    'extend' => 'EImplementation',
                    'http' => 501
                ],

            'EUnsupported' => [
                'extend' => 'ELogic'
            ],


        'ERuntime' => [
            'type' => 'RuntimeException'
        ],
            'EOutOfBounds' => [
                'extend' => 'ERuntime',
                'type' => 'OutOfBoundsException'
            ],
            'EOverflow' => [
                'extend' => 'ERuntime',
                'type' => 'OverflowException'
            ],
            'ERange' => [
                'extend' => 'ERuntime',
                'type' => 'RangeException'
            ],
            'EUnderflow' => [
                'extend' => 'ERuntime',
                'type' => 'UnderflowException'
            ],
            'EUnexpectedValue' => [
                'extend' => 'ERuntime',
                'type' => 'UnexpectedValueException'
            ],

            'EIo' => [
                'extend' => 'ERuntime'
            ],

            'EBadRequest' => [
                'extend' => 'ERuntime',
                'http' => 400
            ],
            'EUnauthorized' => [
                'extend' => 'ERuntime',
                'http' => 401
            ],
            'EForbidden' => [
                'extend' => 'EUnauthorized',
                'http' => 403
            ],
            'ENotFound' => [
                'extend' => 'ERuntime',
                //'http' => 404
            ],
            'EComponentUnavailable' => [
                'extend' => 'ERuntime'
            ],
            'EServiceUnavailable' => [
                'extend' => 'ERuntime',
                'http' => 503
            ],

        'ESystemError' => [
            'type' => 'ErrorException'
        ]
    ];

    const REWIND = 3;

    private static $instances = [];

    protected $type;
    protected $message;
    protected $params = [];

    protected $targetNamespace;
    protected $namespaces = [];

    protected $interfaces = [];
    protected $traits = [];

    protected $hasRoot = false;

    protected $interfaceIndex = [];
    protected $interfaceDefs = [];
    protected $exceptionDef;


    /**
     * Direct call interface
     */
    public static function __callStatic(string $method, array $args): \EGlitch
    {
        return self::create(
            null,
            explode(',', $method),
            1,
            ...$args
        );
    }

    /**
     * Generate a context specific, message oriented throwable error
     */
    public static function create(?string $type, array $interfaces=[], int $rewind=null, $message=null, ?array $params=[], $data=null): \EGlitch
    {
        return (new self($type, $interfaces, $rewind, $message, $params, $data))->build();
    }


    protected function __construct(?string $type, array $interfaces=[], int $rewind=null, $message=null, ?array $params=[], $data=null)
    {
        if (is_array($message)) {
            $params = $message;
            $message = $message['message'] ?? null;
        }

        if ($message === null) {
            $message = 'Undefined error';
        }

        $this->type = $type;
        $this->params = $params ?? [];
        $this->message = $message;

        if ($data !== null) {
            $this->params['data'] = $data;
        }

        if ($rewind !== null) {
            $this->params['rewind'] = $rewind;
        }

        $this->params['rewind'] = $rewind = max((int)($this->params['rewind'] ?? 0), 0);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $rewind + static::REWIND + 2);
        $key = $rewind + static::REWIND;
        $lastTrace = $trace[$key - 1];

        if (isset($this->params['namespace'])) {
            $this->targetNamespace = $this->params['namespace'];
            unset($this->params['namespace']);
        } elseif (isset($trace[$key])) {
            $this->targetNamespace = $trace[$key]['class'] ?? null;

            if (!empty($this->targetNamespace)) {
                if (false !== strpos($this->targetNamespace, 'class@anon')) {
                    $this->targetNamespace = null;
                } else {
                    $parts = explode('\\', $this->targetNamespace);
                    $className = array_pop($parts);
                    $this->targetNamespace = implode('\\', $parts);
                }
            }
        }

        if ($this->targetNamespace !== null) {
            $this->targetNamespace = ltrim($this->targetNamespace, '\\');
        }

        if (empty($this->targetNamespace)) {
            $this->targetNamespace = null;
        } else {
            $this->targetNamespace = '\\'.$this->targetNamespace;
        }

        if (!isset($this->params['file'])) {
            $this->params['file'] = $lastTrace['file'] ?? null;
        }

        if (!isset($this->params['line'])) {
            $this->params['line'] = $lastTrace['line'] ?? null;
        }

        $this->params['interfaces'] = [];

        $this->interfaces['\\DecodeLabs\\Glitch\\Inspectable'] = true;
        $this->traits['\\EGlitchTrait'] = true;

        $this->prepareInterfaces($interfaces);
    }


    /**
     * Prepare interface list
     */
    protected function prepareInterfaces(array $interfaces): void
    {
        foreach ($interfaces as $interface) {
            $interface = trim($interface);

            if (false !== strpos($interface, '/')) {
                // Path style
                $interface = str_replace('/', '\\', $interface);

                if (substr($interface, 0, 1) == '.') {
                    $interface = $this->targetNamespace.'\\'.$interface;
                }

                $interface = '\\'.ltrim($interface, '\\');

                if (false !== strpos($interface, '.')) {
                    $parts = [];

                    foreach (explode('\\', $interface) as $part) {
                        if ($part == '.') {
                            continue;
                        } elseif ($part == '..') {
                            array_pop($parts);
                        } else {
                            $parts[] = $part;
                        }
                    }

                    $interface = implode('\\', $parts);
                }
            } else {
                // Namespace style
                if (substr($interface, 0, 1) !== '\\') {
                    $interface = $this->targetNamespace.'\\'.$interface;
                }
            }

            $this->interfaces[$interface] = true;

            if ($interface !== '\\DecodeLabs\\Glitch\\Inspectable') {
                $this->params['interfaces'][] = ltrim($interface, '\\');
            }
        }
    }



    /**
     * Build exception object
     */
    protected function build(): \EGlitch
    {
        $this->indexInterfaces();
        $this->buildDefinitions();

        $hash = $this->compileDefinitions();
        $this->params['type'] = $this->type;
        return new self::$instances[$hash]($this->message, $this->params);
    }


    /**
     * Extract namespaces from selected interface list
     */
    protected function indexInterfaces(): void
    {
        if ($this->targetNamespace !== null) {
            $this->namespaces[$this->targetNamespace] = true;
        }

        foreach ($this->interfaces as $interface => $enabled) {
            if (null !== ($ns = $this->indexInterface($interface))) {
                $this->namespaces[$ns] = true;
            }
        }

        foreach ($this->namespaces as $namespace => $enabled) {
            $this->indexNamespaceInterfaces($namespace);
        }
    }


    /**
     * Add interface info to class extend list
     */
    protected function indexInterface(string $interface): ?string
    {
        $parts = explode('\\', $interface);
        $name = array_pop($parts);
        $traitName = implode('\\', $parts).'\\'.$name.'Trait';
        $isEFormat = preg_match('/^(E)[A-Z][a-zA-Z0-9_]+$/', $name);

        if ($isEFormat && trait_exists($traitName, true)) {
            $this->traits[$traitName] = true;
        }

        if (interface_exists($interface)) {
            $this->interfaceIndex[$interface] = [];
            return null;
        }

        if (!$isEFormat) {
            return null;
        }

        $output = implode('\\', $parts);

        if (isset(static::STANDARD[$name])) {
            $standard = static::STANDARD[$name];

            if (isset($standard['extend'])) {
                $standard['extend'] = ['\\'.$standard['extend'] => true];
            }

            $this->interfaceIndex[$interface] = $standard;

            if (count($parts) > 1) {
                $this->interfaceIndex[$name] = $standard;
            }

            if ($this->type === null && isset($standard['type'])) {
                $this->type = $standard['type'];
            }

            if (!isset($this->params['http']) && isset($standard['http'])) {
                $this->params['http'] = $standard['http'];
            }
        } elseif (!isset($this->interfaceIndex[$interface])) {
            $this->interfaceIndex[$interface] = [];
        }

        if ($name === 'EGlitch') {
            array_pop($parts);
        }

        $extend = implode('\\', $parts).'\\EGlitch';

        if (empty($this->interfaceIndex[$interface]['extend'] ?? null) || $extend !== '\\EGlitch') {
            $this->interfaceIndex[$interface]['extend'][$extend] = true;
        }

        return $output;
    }


    /**
     * Build interface definitions
     */
    protected function buildDefinitions(): void
    {
        // Create definitions for needed interfaces
        foreach ($this->interfaceIndex as $interface => $info) {
            if (!empty($info)) {
                $this->defineInterface($interface, $info);
            }
        }

        // Ensure defaults
        if ($this->type === null) {
            $this->type = \Exception::class;
        }


        if (empty($this->interfaceIndex)) {
            $this->interfaceIndex['\\EGlitch'] = [];
        }


        // Build class def
        $this->exceptionDef = 'return new class(\'\') extends '.$this->type;
        $interfaces = [];
        $hasGlitch = false;

        foreach ($this->interfaceIndex as $interface => $set) {
            if ($this->hasRoot && $interface === '\\EGlitch') {
                continue;
            }

            $parts = explode('\\', $interface);
            $name = array_pop($parts);

            if ($name === 'EGlitch') {
                if (!$hasGlitch) {
                    $hasGlitch = true;
                } else {
                    continue;
                }
            }

            $interfaces[] = $interface;
        }

        if (empty($interfaces)) {
            $interfaces[] = '\\EGlitch';
        }

        $this->exceptionDef .= ' implements '.implode(',', $interfaces);
        $this->exceptionDef .= ' {';

        foreach ($this->traits as $trait => $enabled) {
            $this->exceptionDef .= 'use '.$trait.';';
        }

        $this->exceptionDef .= '};';
    }



    /**
     * Create an interface tree back down to Df ns root
     */
    protected function indexNamespaceInterfaces(string $namespace): void
    {
        $parts = explode('\\', $namespace);
        $parent = null;

        foreach ($parts as $part) {
            if ($parent === null) {
                $ins = $part;
            } else {
                $ins = $parent.'\\'.$part;
            }

            $interface = $ins.'\\EGlitch';

            $this->indexInterface($interface);
            $parent = $ins;
        }
    }


    /**
     * Recursively define interfaces, adding in inherited parents
     */
    protected function defineInterface(string $interface, array $info): void
    {
        $parent = '\\EGlitch';

        if ($interface === $parent) {
            return;
        }

        if (isset($info['extend'])) {
            $parent = [];

            foreach ($info['extend'] as $extend => $enabled) {
                $parent[] = '\\'.ltrim($extend, '\\');
                $parts = explode('\\', $extend);
                $name = array_pop($parts);

                if (isset($this->interfaceIndex[$extend])) {
                    $inner = $this->interfaceIndex[$extend];
                    unset($this->interfaceIndex[$extend]);
                    $this->defineInterface($extend, $inner);
                } elseif (isset(static::STANDARD[$name])) {
                    $standard = static::STANDARD[$name];

                    if (isset($standard['extend'])) {
                        $standard['extend'] = ['\\'.$standard['extend'] => true];
                    }

                    if ($this->type === null && isset($standard['type'])) {
                        $this->type = $standard['type'];
                    }

                    $this->defineInterface($extend, $standard);
                }
            }

            $parent = implode(',', $parent);
        }

        $parts = explode('\\', $interface);
        $name = array_pop($parts);
        array_shift($parts);

        if ($parent === '\\EGlitch') {
            $this->hasRoot = true;
        }

        if (interface_exists($interface)) {
            return;
        }

        $this->interfaceDefs[$interface] = 'namespace '.implode($parts, '\\').' {interface '.$name.' extends '.$parent.' {}}';
    }


    /**
     * Compile definitions using eval()
     */
    protected function compileDefinitions(): string
    {
        $defs = implode("\n", $this->interfaceDefs);

        // Put the eval code in $GLOBALS to dump if it dies
        $GLOBALS['__eval'] = $defs."\n".$this->exceptionDef;

        eval($defs);
        $hash = md5($this->exceptionDef);

        if (!isset(self::$instances[$hash])) {
            self::$instances[$hash] = eval($this->exceptionDef);
        }

        // Remove defs from $GLOBALS again
        unset($GLOBALS['__eval']);

        return $hash;
    }
}
