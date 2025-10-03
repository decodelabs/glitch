<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs;

use Closure;
use Composer\Autoload\ClassLoader;
use DecodeLabs\Exceptional\Exception as ExceptionalException;
use DecodeLabs\Glitch\Dump;
use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\Renderer\Cli as CliRenderer;
use DecodeLabs\Glitch\Renderer\Html as HtmlRenderer;
use DecodeLabs\Glitch\Renderer\Text as TextRenderer;
use DecodeLabs\Glitch\Stat;
use DecodeLabs\Glitch\Transport;
use DecodeLabs\Glitch\Transport\Http as HttpTransport;
use DecodeLabs\Glitch\Transport\Stdout as StdoutTransport;
use DecodeLabs\Kingdom\PureService;
use DecodeLabs\Kingdom\PureServiceTrait;
use DecodeLabs\Monarch\ExceptionLogger;
use DecodeLabs\Nuance\Reflection as NuanceReflection;
use DecodeLabs\Remnant\Anchor;
use DecodeLabs\Remnant\Anchor\FunctionIdentifier as FunctionIdentifierAnchor;
use DecodeLabs\Remnant\Anchor\Rewind as RewindAnchor;
use DecodeLabs\Remnant\ClassIdentifier\Native as NativeClassIdentifier;
use DecodeLabs\Remnant\Frame;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod as ObjectMethodFunctionIdentifier;
use DecodeLabs\Remnant\Trace;
use ErrorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;

class Glitch implements
    LoggerAwareInterface,
    ExceptionLogger,
    PureService
{
    use PureServiceTrait;

    public float $startTime;

    /**
     * @var array<string,Closure>
     */
    protected array $statGatherers = [];

    protected bool $dumpedInBuffer = false;
    protected ?LoggerInterface $logger = null;

    protected ?Closure $logListener;
    protected ?Renderer $dumpRenderer = null;
    protected ?Transport $transport = null;
    protected ?Closure $headerBufferSender;
    protected ?Closure $errorPageRenderer;


    public function __construct()
    {
        $this->startTime = microtime(true);

        $this->registerStatGatherer('default', $this->gatherDefaultStats(...));
        Monarch::registerExceptionLogger($this);
    }

    public static function getVersion(): string
    {
        $file = dirname(__DIR__) . '/CHANGELOG.md';
        $contents = file_get_contents($file, length: 1000);

        preg_match('/### \[([v0-9.]+)/', (string)$contents, $matches);
        return $matches[1] ?? 'v0.x-dev';
    }

    public function setLogger(
        LoggerInterface $logger
    ): void {
        $this->logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? null;
    }


    /**
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

    public function getLogListener(): ?Closure
    {
        return $this->logListener ?? null;
    }


    public function stackTrace(
        ?Anchor $anchor = null
    ): Trace {
        if ($anchor === null) {
            $anchor = new FunctionIdentifierAnchor(
                new ObjectMethodFunctionIdentifier(
                    new NativeClassIdentifier(__CLASS__),
                    'stackTrace'
                )
            );
        } elseif ($anchor instanceof RewindAnchor) {
            $anchor = clone $anchor;
            $anchor->offset += 1;
        }

        return Trace::create($anchor);
    }



    public function dump(
        mixed $var,
        mixed ...$vars
    ): void {
        $this->dumpValues(
            values: func_get_args(),
            anchor: new FunctionIdentifierAnchor(
                new ObjectMethodFunctionIdentifier(
                    new NativeClassIdentifier(__CLASS__),
                    'dump'
                )
            ),
            exit: false
        );
    }

    public function dumpDie(
        mixed $var,
        mixed ...$vars
    ): void {
        $this->dumpValues(
            values: func_get_args(),
            anchor: new FunctionIdentifierAnchor(
                new ObjectMethodFunctionIdentifier(
                    new NativeClassIdentifier(__CLASS__),
                    'dumpDie'
                )
            ),
            exit: true
        );
    }

    public function hasDumpedInBuffer(): bool
    {
        return $this->dumpedInBuffer;
    }


    /**
     * @param array<mixed> $values
     */
    public function dumpValues(
        array $values,
        ?Anchor $anchor = null,
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

        if ($anchor === null) {
            $anchor = new FunctionIdentifierAnchor(
                new ObjectMethodFunctionIdentifier(
                    new NativeClassIdentifier(__CLASS__),
                    'dumpValues'
                )
            );
        } elseif ($anchor instanceof RewindAnchor) {
            $anchor = clone $anchor;
            $anchor->offset += 1;
        }

        $trace = Trace::create($anchor);
        $dump = new Dump($trace);

        foreach ($this->statGatherers as $gatherer) {
            $gatherer($dump, $this);
        }

        foreach ($values as $value) {
            $dump->addEntity($value);
        }

        if (ob_get_level()) {
            $this->dumpedInBuffer = true;
        }

        $packet = $this->getActiveRenderer()->renderDumpView($dump, $exit);
        $this->getTransport()->sendDump($packet, $exit);

        if ($exit) {
            exit(1);
        }
    }

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

        if ($exception instanceof ExceptionalException) {
            $trace = $exception->stackTrace;
        } else {
            $trace = Trace::fromException($exception);
        }

        $dump = new Dump($trace);

        foreach ($this->statGatherers as $gatherer) {
            $gatherer($dump, $this);
        }

        $packet = $this->getRenderer()->renderExceptionView($exception, $dump);
        $this->getTransport()->sendException($packet, $exit);

        if ($exit) {
            exit(1);
        }
    }






    /**
     * @return $this
     */
    public function setStartTime(
        float $time
    ): static {
        $this->startTime = $time;
        return $this;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * @return $this
     */
    public function registerAsErrorHandler(): static
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);

        return $this;
    }

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
            Monarch::isProduction() &&
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

    public function handleException(
        Throwable $exception
    ): void {
        try {
            $this->logException($exception);

            if (
                Monarch::isProduction() &&
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

    public function getHeaderBufferSender(): ?Closure
    {
        return $this->headerBufferSender ?? null;
    }



    /**
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

    public function getErrorPageRenderer(): ?Closure
    {
        return $this->errorPageRenderer ?? null;
    }


    /**
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
     * @return array<string,Closure>
     */
    public function getStatGatherers(): array
    {
        return $this->statGatherers;
    }

    public function gatherDefaultStats(
        Dump $dump,
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
                    return NuanceReflection::formatFilesize($memory);
                }),

            // Peak memory
            (new Stat('peakMemory', 'Peak memory usage', memory_get_peak_usage()))
                ->setRenderer(function (int $memory) {
                    return NuanceReflection::formatFilesize($memory);
                }),

            // Location
            (new Stat('location', 'Dump location', $frame))
                ->setRenderer(function (Frame $frame) {
                    return $frame->callSite?->__toString();
                })
        );
    }


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
     * @return $this
     */
    public function setRenderer(
        Renderer $renderer
    ): static {
        $this->dumpRenderer = $renderer;
        return $this;
    }

    /**
     * @return $this
     */
    public function useTextRenderer(): static
    {
        $this->dumpRenderer = new TextRenderer($this);
        return $this;
    }

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

    public function getActiveRenderer(): Renderer
    {
        $renderer = $this->getRenderer();

        if (
            $renderer instanceof HtmlRenderer &&
            headers_sent()
        ) {
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
     * @return $this
     */
    public function setTransport(
        Transport $transport
    ): static {
        $this->transport = $transport;
        return $this;
    }

    public function getTransport(): Transport
    {
        if (!$this->transport) {
            if (in_array(\PHP_SAPI, ['cli', 'phpdbg'])) {
                $this->transport = new StdoutTransport($this);
            } else {
                $this->transport = new HttpTransport($this);
            }
        }

        return $this->transport;
    }
}
