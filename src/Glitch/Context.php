<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch;

use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\Glitch\Dumper\Inspector;
use DecodeLabs\Glitch\Dumper\Dump;

use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\Transport;

use Composer\Autoload\ClassLoader;

class Context
{
    protected static $default;

    protected $startTime;
    protected $runMode = 'development';
    protected $pathAliases = [];

    protected $statGatherers = [];

    protected $objectInspectors = [];
    protected $resourceInspectors = [];

    protected $dumpRenderer;
    protected $transport;


    /**
     * Create / fetch default context
     */
    public static function getDefault(): Context
    {
        if (!self::$default) {
            self::setDefault(new self());
        }

        return self::$default;
    }

    /**
     * Set custom default context
     */
    public static function setDefault(Context $default): void
    {
        self::$default = $default;
    }


    /**
     * Construct
     */
    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->pathAliases['glitch'] = dirname(__DIR__);

        $this->registerStatGatherer('default', [$this, 'gatherDefaultStats']);
    }



    /**
     * Set active run mode
     */
    public function setRunMode(string $mode): Context
    {
        switch ($mode) {
            case 'production':
            case 'testing':
            case 'development':
                $this->runMode = $mode;
                break;

            default:
                throw \Glitch::EInvalidArgument('Invalid run mode', null, $mode);
        }

        return $this;
    }

    /**
     * Get current run mode
     */
    public function getRunMode(): string
    {
        return $this->runMode;
    }

    /**
     * Is Glitch in development mode?
     */
    public function isDevelopment(): bool
    {
        return $this->runMode == 'development';
    }

    /**
     * Is Glitch in testing mode?
     */
    public function isTesting(): bool
    {
        return $this->runMode == 'testing'
            || $this->runMode == 'development';
    }

    /**
     * Is Glitch in production mode?
     */
    public function isProduction(): bool
    {
        return $this->runMode == 'production';
    }




    /**
     * Send variables to dump, carry on execution
     */
    public function dump(array $values, int $rewind=null): void
    {
        $trace = Trace::create($rewind + 1);
        $inspector = new Inspector($this);
        $dump = new Dump($trace);

        foreach ($this->statGatherers as $gatherer) {
            $gatherer($dump, $this);
        }

        foreach ($values as $value) {
            $dump->addEntity($inspector($value));
        }

        $dump->setTraceEntity($inspector($trace, function ($entity) {
            $entity->setOpen(false);
        }));

        $packet = $this->getRenderer()->renderDump($dump, true);
        $this->getTransport()->sendDump($packet);
    }

    /**
     * Send variables to dump, exit and render
     */
    public function dumpDie(array $values, int $rewind=null): void
    {
        $this->dump($values, $rewind + 1);
        exit(1);
    }



    /**
     * Quit a stubbed method
     */
    public function incomplete($data=null, int $rewind=null): void
    {
        $frame = Frame::create($rewind + 1);

        throw \Glitch::EImplementation(
            $frame->getSignature().' has not been implemented yet',
            null,
            $data
        );
    }



    /**
     * Log an exception... somewhere :)
     */
    public function logException(\Throwable $e): void
    {
        // TODO: put this somewhere
    }



    /**
     * Override app start time
     */
    public function setStartTime(float $time): Context
    {
        $this->startTime = $time;
        return $this;
    }

    /**
     * Get app start time
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }





    /**
     * Register path replacement alias
     */
    public function registerPathAlias(string $name, string $path): Context
    {
        $this->pathAliases[$name] = $path;

        uasort($this->pathAliases, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $this;
    }

    /**
     * Register list of path replacement aliases
     */
    public function registerPathAliases(array $aliases): Context
    {
        foreach ($aliases as $name => $path) {
            $this->pathAliases[$name] = $path;
        }

        uasort($this->pathAliases, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $this;
    }

    /**
     * Inspect list of registered path aliases
     */
    public function getPathAliases(): array
    {
        return $this->pathAliases;
    }

    /**
     * Lookup and replace path prefix
     */
    public function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        foreach ($this->pathAliases as $name => $test) {
            if (0 === strpos($path, $test)) {
                return $name.'://'.ltrim(substr($path, strlen($test)), '/');
            }
        }

        return $path;
    }



    /**
     * Register stat gatherer
     */
    public function registerStatGatherer(string $name, callable $gatherer): Context
    {
        $this->statGatherers[$name] = $gatherer;
        return $this;
    }

    /**
     * Get stat gatherers
     */
    public function getStatGatherers(): array
    {
        return $this->statGatherers;
    }

    /**
     * Default stat gatherer
     */
    public function gatherDefaultStats(Dump $dump, Context $context): void
    {
        $frame = $dump->getTrace()->getFirstFrame();

        $dump->addStats(
            // Time
            (new Stat('time', 'Running time', microtime(true) - $this->getStartTime()))
                ->applyClass(function ($value) {
                    switch (true) {
                        case $value > 0.1:
                            return 'danger';

                        case $value > 0.025:
                            return 'warning';

                        default:
                            return 'success';
                    }
                })
                ->setRenderer('text', function ($time) {
                    return self::formatMicrotime($time);
                }),

            // Memory
            (new Stat('memory', 'Memory usage', memory_get_usage()))
                ->applyClass($memApp = function ($value) {
                    $mb = 1024 * 1024;

                    switch (true) {
                        case $value > (10 * $mb):
                            return 'danger';

                        case $value > (5 * $mb):
                            return 'warning';

                        default:
                            return 'success';
                    }
                })
                ->setRenderer('text', function ($memory) {
                    return self::formatFilesize($memory);
                }),

            // Peak memory
            (new Stat('peakMemory', 'Peak memory usage', memory_get_peak_usage()))
                ->applyClass($memApp)
                ->setRenderer('text', function ($memory) {
                    return self::formatFilesize($memory);
                }),

            // Location
            (new Stat('location', 'Dump location', $frame))
                ->setRenderer('text', function ($frame) {
                    return $this->normalizePath($frame->getFile()).' : '.$frame->getLine();
                })
        );
    }

    /**
     * TODO: move these to a shared location
     */
    private static function formatFilesize($bytes)
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    private static function formatMicrotime($time)
    {
        return number_format($time * 1000, 2).' ms';
    }



    /**
     * Register callable inspector for a specific class
     */
    public function registerObjectInspector(string $class, callable $inspector): Context
    {
        $this->objectInspectors[$class] = $inspector;
        return $this;
    }

    /**
     * Get list of registered inspectors
     */
    public function getObjectInspectors(): array
    {
        return $this->objectInspectors;
    }


    /**
     * Register callable inspector for a specific resource type
     */
    public function registerResourceInspector(string $type, callable $inspector): Context
    {
        $this->resourceInspectors[$type] = $inspector;
        return $this;
    }

    /**
     * Get list of registered inspectors
     */
    public function getResourceInspectors(): array
    {
        return $this->resourceInspectors;
    }




    /**
     * Get composer vendor path
     */
    public function getVendorPath(): string
    {
        static $output;

        if (!isset($output)) {
            $ref = new \ReflectionClass(ClassLoader::class);
            $output = dirname(dirname($ref->getFileName()));
        }

        return $output;
    }


    /**
     * Set dump renderer
     */
    public function setRenderer(Renderer $renderer): Context
    {
        $this->dumpRenderer = $renderer;
        return $this;
    }

    /**
     * Get dump renderer
     */
    public function getRenderer(): Renderer
    {
        if (!$this->dumpRenderer) {
            $this->dumpRenderer = new Renderer\Html($this);
        }

        return $this->dumpRenderer;
    }


    /**
     * Set transport
     */
    public function setTransport(Transport $transport): Context
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * Get transport
     */
    public function getTransport(): Transport
    {
        if (!$this->transport) {
            $this->transport = new Transport\Stdout($this);
        }

        return $this->transport;
    }
}
