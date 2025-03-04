<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use Closure;
use Composer\Autoload\ClassLoader;
use DecodeLabs\Exceptional;
use DecodeLabs\Exceptional\Exception as ExceptionalException;
use DecodeLabs\Glitch\Dumper\Dump;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;
use DecodeLabs\Glitch\Renderer\Cli as CliRenderer;
use DecodeLabs\Glitch\Renderer\Html as HtmlRenderer;
use DecodeLabs\Glitch\Renderer\Text as TextRenderer;
use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\Glitch\Transport\Http as HttpTransport;
use DecodeLabs\Glitch\Transport\Stdout as StdoutTransport;
use ErrorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;

class Context implements LoggerAwareInterface
{
    protected float $startTime;
    protected string $runMode = 'development';

    /**
     * @var array<string,string>
     */
    protected array $pathAliases = [];

    /**
     * @var array<string,Closure>
     */
    protected array $statGatherers = [];

    /**
     * @var array<string,Closure>
     */
    protected array $objectInspectors = [];

    /**
     * @var array<string,Closure>
     */
    protected array $resourceInspectors = [];


    protected bool $dumpedInBuffer = false;
    protected ?LoggerInterface $logger = null;

    protected ?Closure $logListener;
    protected ?Renderer $dumpRenderer = null;
    protected ?Transport $transport = null;
    protected ?Closure $headerBufferSender;
    protected ?Closure $errorPageRenderer;


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
     * Get version
     */
    public function getVersion(): string
    {
        $file = dirname(__DIR__, 2) . '/CHANGELOG.md';
        $contents = file_get_contents($file, length: 500);

        preg_match('/## ([v0-9.]+)/', (string)$contents, $matches);
        return $matches[1] ?? 'v0.x-dev';
    }


    /**
     * Set active run mode
     *
     * @return $this
     */
    public function setRunMode(
        string $mode
    ): static {
        switch ($mode) {
            case 'production':
            case 'testing':
            case 'development':
                $this->runMode = $mode;
                break;

            default:
                throw Exceptional::InvalidArgument(
                    message: 'Invalid run mode',
                    data: $mode
                );
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
        return
            $this->runMode == 'testing' ||
            $this->runMode == 'development';
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
    public function setLogger(
        LoggerInterface $logger
    ): void {
        $this->logger = $logger;
    }

    /**
     * Get registered
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? null;
    }


    /**
     * Add a logger listener callback
     *
     * @return $this
     */
    public function setLogListener(
        ?callable $listener
    ): static {
        if ($listener) {
            $listener = Closure::fromCallable($listener);
        }

        $this->logListener = $listener;
        return $this;
    }

    /**
     * Get registered logger listener
     */
    public function getLogListener(): ?Closure
    {
        return $this->logListener ?? null;
    }


    /**
     * Create a new stack trace
     */
    public function stackTrace(
        int $rewind = 0
    ): Trace {
        return Trace::create($rewind + 1);
    }



    /**
     * Send variables to dump, carry on execution
     */
    public function dump(
        mixed $var,
        mixed ...$vars
    ): void {
        $this->dumpValues(func_get_args(), 1, false);
    }

    /**
     * Send variables to dump, exit and render
     */
    public function dumpDie(
        mixed $var,
        mixed ...$vars
    ): void {
        $this->dumpValues(func_get_args(), 1, true);
    }

    /**
     * Has dumped in output buffer
     */
    public function hasDumpedInBuffer(): bool
    {
        return $this->dumpedInBuffer;
    }


    /**
     * Send variables to dump, carry on execution
     *
     * @param array<mixed> $values
     */
    public function dumpValues(
        array $values,
        int $rewind = 0,
        bool $exit = true
    ): void {
        if ($exit) {
            while (ob_get_level()) {
                if ($this->dumpedInBuffer) {
                    echo ob_get_clean();
                } else {
                    ob_end_clean();
                }
            }
        }

        $trace = Trace::create($rewind + 1);
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

        $packet = $this->getActiveRenderer()->renderDump($dump, $exit);
        $this->getTransport()->sendDump($packet, $exit);

        if ($exit) {
            exit(1);
        }
    }

    /**
     * Dump and render exception
     */
    public function dumpException(
        Throwable $exception,
        bool $exit = true
    ): void {
        if ($exit) {
            while (ob_get_level()) {
                if ($this->dumpedInBuffer) {
                    echo ob_get_clean();
                } else {
                    ob_end_clean();
                }
            }
        }

        if (
            $exception instanceof IncompleteException ||
            $exception instanceof ExceptionalException
        ) {
            $trace = $exception->stackTrace;
        } else {
            $trace = Trace::fromException($exception);
        }

        $inspector = new Inspector($this);
        $dump = new Dump($trace);

        foreach ($this->statGatherers as $gatherer) {
            $gatherer($dump, $this);
        }

        /** @var Entity $entity */
        $entity = $inspector->inspectValue($exception);

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
     *
     * @return $this
     */
    public function setStartTime(
        float $time
    ): static {
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
     * Shortcut to incomplete context method
     */
    public function incomplete(
        mixed $data = null,
        int $rewind = 0
    ): void {
        throw new IncompleteException(
            Trace::create($rewind + 1),
            $data
        );
    }


    /**
     * Register as error handler
     *
     * @return $this
     */
    public function registerAsErrorHandler(): static
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);

        return $this;
    }

    /**
     * Default ErrorException wrapper
     */
    public function handleError(
        int $level,
        string $message,
        string $file,
        int $line
    ): bool {
        if (!(error_reporting() & $level)) {
            return false;
        }

        $output = Exceptional::Error(
            message: $message,
            file: $file,
            line: $line,
            code: $level,
            severity: $level
        );

        if (
            $this->isProduction() &&
            in_array($level, [
                E_NOTICE,
                E_USER_NOTICE,
                E_DEPRECATED,
                E_USER_DEPRECATED
            ], true)
        ) {
            $this->logException($output);
            return true;
        }

        throw $output;
    }

    /**
     * Last-ditch catch-all for exceptions
     */
    public function handleException(
        Throwable $exception
    ): void {
        try {
            $this->logException($exception);

            if (
                $this->isProduction() &&
                isset($this->errorPageRenderer)
            ) {
                try {
                    ($this->errorPageRenderer)($exception, $this);
                    return;
                } catch (Throwable $e) {
                }
            }

            if (!class_exists(Trace::class)) {
                echo (string)$exception;
                exit(1);
            }

            $this->dumpException($exception);
        } catch (Throwable $e) {
            dd($exception, $e);
        }
    }


    /**
     * Log an exception... somewhere :)
     */
    public function logException(
        Throwable $exception
    ): void {
        if (isset($this->logger)) {
            try {
                $this->logger->critical($exception->getMessage(), [
                    'exception' => $exception
                ]);
            } catch (Throwable $e) {
            }
        }

        if (isset($this->logListener)) {
            try {
                ($this->logListener)($exception);
            } catch (Throwable $e) {
            }
        }
    }


    /**
     * Try and do something about fatal errors after shutdown
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && self::isErrorLevelFatal($error['type'])) {
            $this->handleException(new ErrorException(
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
    protected static function isErrorLevelFatal(
        int $level
    ): bool {
        $errors = E_ERROR;
        $errors |= E_PARSE;
        $errors |= E_CORE_ERROR;
        $errors |= E_CORE_WARNING;
        $errors |= E_COMPILE_ERROR;
        $errors |= E_COMPILE_WARNING;

        return ($level & $errors) > 0;
    }


    /**
     * Set header buffer sender
     *
     * @return $this
     */
    public function setHeaderBufferSender(
        ?callable $sender
    ): static {
        if ($sender) {
            $sender = Closure::fromCallable($sender);
        }

        $this->headerBufferSender = $sender;
        return $this;
    }

    /**
     * Get header buffer sender
     */
    public function getHeaderBufferSender(): ?Closure
    {
        return $this->headerBufferSender ?? null;
    }



    /**
     * Set error page renderer
     *
     * @return $this
     */
    public function setErrorPageRenderer(
        ?callable $renderer
    ): static {
        if ($renderer) {
            $renderer = Closure::fromCallable($renderer);
        }

        $this->errorPageRenderer = $renderer;
        return $this;
    }

    /**
     * Get error page renderer
     */
    public function getErrorPageRenderer(): ?Closure
    {
        return $this->errorPageRenderer ?? null;
    }




    /**
     * Register path replacement alias
     *
     * @return $this
     */
    public function registerPathAlias(
        string $name,
        string $path
    ): static {
        $path = rtrim($path, '/') . '/';
        $this->pathAliases[$name] = $path;

        try {
            if (
                ($realPath = realpath($path)) &&
                $realPath . '/' !== $path
            ) {
                $this->pathAliases[$name . '*'] = $realPath . '/';
            }
        } catch (Throwable $e) {
        }

        uasort($this->pathAliases, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $this;
    }

    /**
     * Register list of path replacement aliases
     *
     * @param array<string, string> $aliases
     * @return $this
     */
    public function registerPathAliases(
        array $aliases
    ): static {
        foreach ($aliases as $name => $path) {
            $path = rtrim($path, '/') . '/';
            $this->pathAliases[$name] = $path;

            try {
                if (
                    ($realPath = realpath($path)) &&
                    $realPath . '/' !== $path
                ) {
                    $this->pathAliases[$name . '*'] = $realPath . '/';
                }
            } catch (Throwable $e) {
            }
        }

        uasort($this->pathAliases, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $this;
    }

    /**
     * Inspect list of registered path aliases
     *
     * @return array<string, string>
     */
    public function getPathAliases(): array
    {
        return $this->pathAliases;
    }

    /**
     * Lookup and replace path prefix
     */
    public function normalizePath(
        ?string $path
    ): ?string {
        if ($path === null) {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $testPath = rtrim($path, '/') . '/';

        foreach ($this->pathAliases as $name => $test) {
            $len = strlen($test);

            if ($testPath === $test) {
                return rtrim($name, '*') . '://';
            } elseif (substr($testPath, 0, $len) == $test) {
                return rtrim($name, '*') . '://' . ltrim(substr($path, $len), '/');
            }
        }

        return $path;
    }



    /**
     * Register stat gatherer
     *
     * @return $this
     */
    public function registerStatGatherer(
        string $name,
        callable $gatherer
    ): static {
        $this->statGatherers[$name] = Closure::fromCallable($gatherer);
        return $this;
    }

    /**
     * Get stat gatherers
     *
     * @return array<string,Closure>
     */
    public function getStatGatherers(): array
    {
        return $this->statGatherers ?? [];
    }

    /**
     * Default stat gatherer
     */
    public function gatherDefaultStats(
        Dump $dump,
        Context $context
    ): void {
        $frame = $dump->getTrace()->getFirstFrame();

        $dump->addStats(
            // Time
            (new Stat('time', 'Running time', microtime(true) - $this->getStartTime()))
                ->setRenderer(function (float $time) {
                    return number_format($time * 1000, 2) . ' ms';
                }),

            // Memory
            (new Stat('memory', 'Memory usage', memory_get_usage()))
                ->setRenderer(function (int $memory) {
                    return self::formatFilesize($memory);
                }),

            // Peak memory
            (new Stat('peakMemory', 'Peak memory usage', memory_get_peak_usage()))
                ->setRenderer(function (int $memory) {
                    return self::formatFilesize($memory);
                }),

            // Location
            (new Stat('location', 'Dump location', $frame))
                ->setRenderer(function (Frame $frame) {
                    if (null === ($file = $frame->callingFile)) {
                        return null;
                    }

                    return $this->normalizePath($file) . ' : ' . $frame->callingLine;
                })
        );
    }

    /**
     * Format filesize bytes as human readable
     */
    public static function formatFilesize(
        int $bytes
    ): string {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }



    /**
     * Register callable inspector for a specific class
     *
     * @return $this
     */
    public function registerObjectInspector(
        string $class,
        callable $inspector
    ): static {
        $this->objectInspectors[$class] = Closure::fromCallable($inspector);
        return $this;
    }

    /**
     * Get list of registered inspectors
     *
     * @return array<string,Closure>
     */
    public function getObjectInspectors(): array
    {
        return $this->objectInspectors ?? [];
    }


    /**
     * Register callable inspector for a specific resource type
     *
     * @return $this
     */
    public function registerResourceInspector(
        string $type,
        callable $inspector
    ): static {
        $this->resourceInspectors[$type] = Closure::fromCallable($inspector);
        return $this;
    }

    /**
     * Get list of registered inspectors
     *
     * @return array<string,Closure>
     */
    public function getResourceInspectors(): array
    {
        return $this->resourceInspectors ?? [];
    }




    /**
     * Get composer vendor path
     */
    public function getVendorPath(): string
    {
        $ref = new ReflectionClass(ClassLoader::class);

        if (false === ($file = $ref->getFileName())) {
            throw Exceptional::Runtime(
                message: 'Unable to work out vendor path'
            );
        }

        return dirname(dirname($file));
    }


    /**
     * Set dump renderer
     *
     * @return $this
     */
    public function setRenderer(
        Renderer $renderer
    ): static {
        $this->dumpRenderer = $renderer;
        return $this;
    }

    /**
     * Fallback to text renderer
     *
     * @return $this
     */
    public function useTextRenderer(): static
    {
        $this->dumpRenderer = new TextRenderer($this);
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
     *
     * @return $this
     */
    public function setTransport(
        Transport $transport
    ): static {
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
                $this->transport = new StdoutTransport();
            } else {
                $this->transport = new HttpTransport();
            }
        }

        return $this->transport;
    }
}
