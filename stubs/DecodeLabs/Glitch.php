<?php
/**
 * This is a stub file for IDE compatibility only.
 * It should not be included in your projects.
 */

namespace DecodeLabs;

use DecodeLabs\Veneer\Proxy as Proxy;
use DecodeLabs\Veneer\ProxyTrait as ProxyTrait;
use DecodeLabs\Glitch\Context as Inst;
use Psr\Log\LoggerInterface as Ref0;
use DecodeLabs\Glitch\Stack\Trace as Ref1;
use Throwable as Ref2;
use DecodeLabs\Glitch\Dumper\Dump as Ref3;
use DecodeLabs\Glitch\Renderer as Ref4;
use DecodeLabs\Glitch\Transport as Ref5;

class Glitch implements Proxy
{
    use ProxyTrait;

    public const VENEER = 'DecodeLabs\\Glitch';
    public const VENEER_TARGET = Inst::class;
    public const VERSION = Inst::VERSION;

    public static Inst $instance;

    public static function setRunMode(string $mode): Inst
    {
        return static::$instance->setRunMode(...func_get_args());
    }
    public static function getRunMode(): string
    {
        return static::$instance->getRunMode();
    }
    public static function isDevelopment(): bool
    {
        return static::$instance->isDevelopment();
    }
    public static function isTesting(): bool
    {
        return static::$instance->isTesting();
    }
    public static function isProduction(): bool
    {
        return static::$instance->isProduction();
    }
    public static function setLogger(Ref0 $logger): void
    {
    }
    public static function getLogger(): ?Ref0
    {
        return static::$instance->getLogger();
    }
    public static function setLogListener(?callable $listener): Inst
    {
        return static::$instance->setLogListener(...func_get_args());
    }
    public static function getLogListener(): ?callable
    {
        return static::$instance->getLogListener();
    }
    public static function stackTrace(int $rewind = 0): Ref1
    {
        return static::$instance->stackTrace(...func_get_args());
    }
    public static function dump(mixed $var, mixed ...$vars): void
    {
    }
    public static function dumpDie(mixed $var, mixed ...$vars): void
    {
    }
    public static function hasDumpedInBuffer(): bool
    {
        return static::$instance->hasDumpedInBuffer();
    }
    public static function dumpValues(array $values, int $rewind = 0, bool $exit = true): void
    {
    }
    public static function dumpException(Ref2 $exception, bool $exit = true): void
    {
    }
    public static function setStartTime(float $time): Inst
    {
        return static::$instance->setStartTime(...func_get_args());
    }
    public static function getStartTime(): float
    {
        return static::$instance->getStartTime();
    }
    public static function incomplete(mixed $data = null, int $rewind = 0): void
    {
    }
    public static function registerAsErrorHandler(): Inst
    {
        return static::$instance->registerAsErrorHandler();
    }
    public static function handleError(int $level, string $message, string $file, int $line): bool
    {
        return static::$instance->handleError(...func_get_args());
    }
    public static function handleException(Ref2 $exception): void
    {
    }
    public static function logException(Ref2 $exception): void
    {
    }
    public static function handleShutdown(): void
    {
    }
    public static function setHeaderBufferSender(?callable $sender): Inst
    {
        return static::$instance->setHeaderBufferSender(...func_get_args());
    }
    public static function getHeaderBufferSender(): ?callable
    {
        return static::$instance->getHeaderBufferSender();
    }
    public static function setErrorPageRenderer(?callable $renderer): Inst
    {
        return static::$instance->setErrorPageRenderer(...func_get_args());
    }
    public static function getErrorPageRenderer(): ?callable
    {
        return static::$instance->getErrorPageRenderer();
    }
    public static function registerPathAlias(string $name, string $path): Inst
    {
        return static::$instance->registerPathAlias(...func_get_args());
    }
    public static function registerPathAliases(array $aliases): Inst
    {
        return static::$instance->registerPathAliases(...func_get_args());
    }
    public static function getPathAliases(): array
    {
        return static::$instance->getPathAliases();
    }
    public static function normalizePath(?string $path): ?string
    {
        return static::$instance->normalizePath(...func_get_args());
    }
    public static function registerStatGatherer(string $name, callable $gatherer): Inst
    {
        return static::$instance->registerStatGatherer(...func_get_args());
    }
    public static function getStatGatherers(): array
    {
        return static::$instance->getStatGatherers();
    }
    public static function gatherDefaultStats(Ref3 $dump, Inst $context): void
    {
    }
    public static function formatFilesize(int $bytes): string
    {
        return static::$instance->formatFilesize(...func_get_args());
    }
    public static function registerObjectInspector(string $class, callable $inspector): Inst
    {
        return static::$instance->registerObjectInspector(...func_get_args());
    }
    public static function getObjectInspectors(): array
    {
        return static::$instance->getObjectInspectors();
    }
    public static function registerResourceInspector(string $type, callable $inspector): Inst
    {
        return static::$instance->registerResourceInspector(...func_get_args());
    }
    public static function getResourceInspectors(): array
    {
        return static::$instance->getResourceInspectors();
    }
    public static function getVendorPath(): string
    {
        return static::$instance->getVendorPath();
    }
    public static function setRenderer(Ref4 $renderer): Inst
    {
        return static::$instance->setRenderer(...func_get_args());
    }
    public static function useTextRenderer(): Inst
    {
        return static::$instance->useTextRenderer();
    }
    public static function getRenderer(): Ref4
    {
        return static::$instance->getRenderer();
    }
    public static function getActiveRenderer(): Ref4
    {
        return static::$instance->getActiveRenderer();
    }
    public static function setTransport(Ref5 $transport): Inst
    {
        return static::$instance->setTransport(...func_get_args());
    }
    public static function getTransport(): Ref5
    {
        return static::$instance->getTransport();
    }
};
