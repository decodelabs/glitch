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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class Context implements LoggerAwareInterface
{
    protected static $default;

    protected $startTime;
    protected $runMode = 'development';
    protected $pathAliases = [];

    protected $statGatherers = [];

    protected $objectInspectors = [];
    protected $resourceInspectors = [];

    protected $dumpedInBuffer = false;

    protected $logger;
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

        if (\Glitch::$autoRegister) {
            $this->registerAsErrorHandler();
        }

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
     * Set PSR logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }





    /**
     * Send variables to dump, carry on execution
     */
    public function dump(array $values, int $rewind=0): void
    {
        $trace = Trace::create($rewind - 1);
        $inspector = new Inspector($this);
        $dump = new Dump($trace);

        foreach ($this->statGatherers as $gatherer) {
            $gatherer($dump, $this);
        }

        foreach ($values as $value) {
            $dump->addEntity($inspector->inspectValue($value));
        }

        if (ob_get_level()) {
            $this->dumpedInBuffer = true;
        }

        $inspector->reset();
        unset($inspector);

        $packet = $this->getRenderer()->renderDump($dump);
        $this->getTransport()->sendDump($packet);
    }

    /**
     * Send variables to dump, exit and render
     */
    public function dumpDie(array $values, int $rewind=0): void
    {
        while (ob_get_level()) {
            if ($this->dumpedInBuffer) {
                echo ob_get_clean();
            } else {
                ob_end_clean();
            }
        }

        $this->dump($values, $rewind + 1);
        exit(1);
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
     * Register as error handler
     */
    public function registerAsErrorHandler(): Context
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
        ini_set('display_errors', '0');

        return $this;
    }

    /**
     * Default ErrorException wrapper
     */
    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (!$current = error_reporting()) {
            return false;
        }

        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Last-ditch catch-all for exceptions
     */
    public function handleException(\Throwable $exception): void
    {
        if ($this->logger) {
            $this->logger->critical($exception->getMessage(), [
                'exception' => $exception
            ]);
        }

        if ($exception instanceof \EGlitch) {
            $data = $exception->getData();
            $trace = $exception->getStackTrace();
        } else {
            $data = null;
            $trace = Trace::fromException($exception);
        }

        $inspector = new Inspector($this);
        $dump = new Dump($trace);

        foreach ($this->statGatherers as $gatherer) {
            $gatherer($dump, $this);
        }

        $entity = $inspector->inspectValue($exception)
            ->removeProperty('*code')
            ->removeProperty('*http');

        $inspector->reset();
        unset($inspector);

        $packet = $this->getRenderer()->renderException($exception, $entity, $dump);
        $this->getTransport()->sendException($packet);
        exit(1);
    }

    /**
     * Try and do something about fatal errors after shutdown
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && self::isErrorLevelFatal($error['type'])) {
            $this->handleException(new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }

    /**
     * Is this error code fatal?
     */
    protected static function isErrorLevelFatal(int $level): bool
    {
        $errors = E_ERROR;
        $errors |= E_PARSE;
        $errors |= E_CORE_ERROR;
        $errors |= E_CORE_WARNING;
        $errors |= E_COMPILE_ERROR;
        $errors |= E_COMPILE_WARNING;

        return ($level & $errors) > 0;
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
    public function normalizePath(?string $path): ?string
    {
        $path = str_replace('\\', '/', $path);

        foreach ($this->pathAliases as $name => $test) {
            $len = strlen($test);

            if (substr($path, 0, $len) == $test) {
                return $name.'://'.ltrim(substr($path, $len), '/');
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
                    if (null === ($file = $frame->getCallingFile())) {
                        return null;
                    }

                    return $this->normalizePath($file).' : '.$frame->getCallingLine();
                })
        );
    }

    /**
     * TODO: move these to a shared location
     */
    public static function formatFilesize($bytes)
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public static function formatMicrotime($time)
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
            if (in_array(\PHP_SAPI, ['cli', 'phpdbg'])) {
                $this->dumpRenderer = new Renderer\Cli($this);
            } else {
                $this->dumpRenderer = new Renderer\Html($this);
            }
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
