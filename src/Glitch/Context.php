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
use DecodeLabs\Glitch\Renderer\Text as TextRenderer;
use DecodeLabs\Glitch\Renderer\Cli as CliRenderer;
use DecodeLabs\Glitch\Renderer\Html as HtmlRenderer;
use DecodeLabs\Glitch\Transport;

use DecodeLabs\Glitch\Exception\Factory;

use Composer\Autoload\ClassLoader;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

use DecodeLabs\Veneer\FacadeTarget;
use DecodeLabs\Veneer\FacadeTargetTrait;
use DecodeLabs\Veneer\FacadePlugin;

class Context implements LoggerAwareInterface, FacadeTarget
{
    use FacadeTargetTrait;

    const FACADE = 'Glitch';
    const VERSION = 'v0.15.0';

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

    protected $headerBufferSender;
    protected $errorPageRenderer;


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
     * Set PSR logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }





    /**
     * Send variables to dump, carry on execution
     */
    public function dump($var, ...$vars): void
    {
        $this->dumpValues(func_get_args(), 1, false);
    }

    /**
     * Send variables to dump, exit and render
     */
    public function dumpDie($var, ...$vars): void
    {
        $this->dumpValues(func_get_args(), 1, true);
    }


    /**
     * Send variables to dump, carry on execution
     */
    public function dumpValues(array $values, int $rewind=0, bool $exit=true): void
    {
        if ($exit) {
            while (ob_get_level()) {
                if ($this->dumpedInBuffer) {
                    echo ob_get_clean();
                } else {
                    ob_end_clean();
                }
            }
        }

        $trace = Trace::create($rewind - 1);

        if (null !== $trace->getFirstFrame()->getVeneerFacade()) {
            $trace->shift();
        }

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

        $packet = $this->getActiveRenderer()->renderDump($dump);
        $this->getTransport()->sendDump($packet, $exit);

        if ($exit) {
            exit(1);
        }
    }

    /**
     * Dump and render exception
     */
    public function dumpException(\Throwable $exception, bool $exit=true): void
    {
        if ($exit) {
            while (ob_get_level()) {
                if ($this->dumpedInBuffer) {
                    echo ob_get_clean();
                } else {
                    ob_end_clean();
                }
            }
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
        $this->getTransport()->sendException($packet, $exit);

        if ($exit) {
            exit(1);
        }
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
     * Redirect type list to Factory
     */
    public function __call(string $method, array $args): \EGlitch
    {
        if (!preg_match('|[.\\/]|', $method) && !preg_match('/^[A-Z]/', $method)) {
            throw Glitch::EBadMethodCall('Method '.$method.' is not available in Glitch');
        }

        return Factory::create(
            null,
            explode(',', $method),
            1,
            ...$args
        );
    }

    /**
     * Create generic exception
     */
    public function Exception($message, ?array $params=[], $data=null): \EGlitch
    {
        return Factory::create(
            null,
            [],
            1,
            $message,
            $params,
            $data
        );
    }


    /**
     * Shortcut to incomplete context method
     */
    public function incomplete($data=null, int $rewind=0): void
    {
        $frame = $this->getLastFrame($rewind);

        throw Factory::create(
            null,
            ['EImplementation', 'DecodeLabs/Glitch/Exception/EIncomplete'],
            $rewind,
            $frame->getSignature().' has not been implemented yet',
            null,
            $data
        );
    }

    /**
     * Get last frame
     */
    protected function getLastFrame(int $rewind=0): Frame
    {
        $frame = Frame::create($rewind + 2);

        if ($frame->getVeneerFacade() !== null) {
            $frame = Frame::create($rewind + 3);
        }

        return $frame;
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

        throw Factory::create(
            null,
            ['ESystemError'],
            1,
            $message,
            [
                'stackTrace' => Trace::create(),
                'file' => $file,
                'line' => $line,
                'severity' => $level
            ]
        );
    }

    /**
     * Last-ditch catch-all for exceptions
     */
    public function handleException(\Throwable $exception): void
    {
        try {
            $this->logException($exception);

            if ($this->isProduction() && $this->errorPageRenderer) {
                try {
                    ($this->errorPageRenderer)($exception, $this);
                    return;
                } catch (\Throwable $e) {
                }
            }

            if (!class_exists(Trace::class)) {
                echo (string)$exception;
                exit(1);
            }

            $this->dumpException($exception);
        } catch (\Throwable $e) {
            dd($exception, $e);
        }
    }


    /**
     * Log an exception... somewhere :)
     */
    public function logException(\Throwable $e): void
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->critical($exception->getMessage(), [
            'exception' => $exception
        ]);
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
     * Wrap exceptions thrown from $callable as Glitches
     */
    public function contain(callable $callback, ?callable $inspector=null)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            if ($e instanceof \EGlitch) {
                throw $e;
            }

            if ($inspector) {
                $types = $inspector($e);

                if (!is_array($types)) {
                    $types = explode(',', (string)$types);
                }
            } else {
                $types = ['ERuntime'];
            }

            throw Factory::create(
                null,
                $types,
                1,
                $e->getMessage(),
                [
                    'previous' => $e,
                    'stackTrace' => Trace::fromException($e, 1),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );
        }
    }



    /**
     * Set header buffer sender
     */
    public function setHeaderBufferSender(?callable $sender): Context
    {
        $this->headerBufferSender = $sender;
        return $this;
    }

    /**
     * Get header buffer sender
     */
    public function getHeaderBufferSender(): ?callable
    {
        return $this->headerBufferSender;
    }



    /**
     * Set error page renderer
     */
    public function setErrorPageRenderer(?callable $renderer): Context
    {
        $this->errorPageRenderer = $renderer;
        return $this;
    }

    /**
     * Get error page renderer
     */
    public function getErrorPageRenderer(): ?callable
    {
        return $this->errorPageRenderer;
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
                ->setRenderer(function ($time) {
                    return number_format($time * 1000, 2).' ms';
                }),

            // Memory
            (new Stat('memory', 'Memory usage', memory_get_usage()))
                ->setRenderer(function ($memory) {
                    return self::formatFilesize($memory);
                }),

            // Peak memory
            (new Stat('peakMemory', 'Peak memory usage', memory_get_peak_usage()))
                ->setRenderer(function ($memory) {
                    return self::formatFilesize($memory);
                }),

            // Location
            (new Stat('location', 'Dump location', $frame))
                ->setRenderer(function ($frame) {
                    if (null === ($file = $frame->getCallingFile())) {
                        return null;
                    }

                    return $this->normalizePath($file).' : '.$frame->getCallingLine();
                })
        );
    }

    /**
     * Format filesize bytes as human readable
     */
    public static function formatFilesize($bytes)
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
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
                $this->dumpRenderer = new CliRenderer($this);
            } else {
                $this->dumpRenderer = new HtmlRenderer($this);
            }
        }

        return $this->dumpRenderer;
    }

    /**
     * Get active renderer for current context
     */
    public function getActiveRenderer(): Renderer
    {
        $renderer = $this->getRenderer();

        if ($renderer instanceof HtmlRenderer && headers_sent()) {
            foreach (headers_list() as $header) {
                if (false !== stripos($header, 'content-type: text/plain')) {
                    $renderer = new TextRenderer($this);
                    break;
                }
            }
        }

        return $renderer;
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
            if (in_array(\PHP_SAPI, ['cli', 'phpdbg'])) {
                $this->transport = new Transport\Stdout($this);
            } else {
                $this->transport = new Transport\Http($this);
            }
        }

        return $this->transport;
    }
}
