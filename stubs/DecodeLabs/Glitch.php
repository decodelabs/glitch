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
use Closure as Ref1;
use DecodeLabs\Remnant\Trace as Ref2;
use Throwable as Ref3;
use DecodeLabs\Glitch\Dump as Ref4;
use DecodeLabs\Glitch\Renderer as Ref5;
use DecodeLabs\Glitch\Transport as Ref6;

class Glitch implements Proxy
{
    use ProxyTrait;

    public const Veneer = 'DecodeLabs\\Glitch';
    public const VeneerTarget = Inst::class;

    protected static Inst $_veneerInstance;

    public static function getVersion(): string {
        return static::$_veneerInstance->getVersion();
    }
    public static function setLogger(Ref0 $logger): void {}
    public static function getLogger(): ?Ref0 {
        return static::$_veneerInstance->getLogger();
    }
    public static function setLogListener(?callable $listener): Inst {
        return static::$_veneerInstance->setLogListener(...func_get_args());
    }
    public static function getLogListener(): ?Ref1 {
        return static::$_veneerInstance->getLogListener();
    }
    public static function stackTrace(int $rewind = 0): Ref2 {
        return static::$_veneerInstance->stackTrace(...func_get_args());
    }
    public static function dump(mixed $var, mixed ...$vars): void {}
    public static function dumpDie(mixed $var, mixed ...$vars): void {}
    public static function hasDumpedInBuffer(): bool {
        return static::$_veneerInstance->hasDumpedInBuffer();
    }
    public static function dumpValues(array $values, int $rewind = 0, bool $exit = true): void {}
    public static function dumpException(Ref3 $exception, bool $exit = true): void {}
    public static function setStartTime(float $time): Inst {
        return static::$_veneerInstance->setStartTime(...func_get_args());
    }
    public static function getStartTime(): float {
        return static::$_veneerInstance->getStartTime();
    }
    public static function registerAsErrorHandler(): Inst {
        return static::$_veneerInstance->registerAsErrorHandler();
    }
    public static function handleError(int $level, string $message, string $file, int $line): bool {
        return static::$_veneerInstance->handleError(...func_get_args());
    }
    public static function handleException(Ref3 $exception): void {}
    public static function logException(Ref3 $exception): void {}
    public static function handleShutdown(): void {}
    public static function setHeaderBufferSender(?callable $sender): Inst {
        return static::$_veneerInstance->setHeaderBufferSender(...func_get_args());
    }
    public static function getHeaderBufferSender(): ?Ref1 {
        return static::$_veneerInstance->getHeaderBufferSender();
    }
    public static function setErrorPageRenderer(?callable $renderer): Inst {
        return static::$_veneerInstance->setErrorPageRenderer(...func_get_args());
    }
    public static function getErrorPageRenderer(): ?Ref1 {
        return static::$_veneerInstance->getErrorPageRenderer();
    }
    public static function registerStatGatherer(string $name, callable $gatherer): Inst {
        return static::$_veneerInstance->registerStatGatherer(...func_get_args());
    }
    public static function getStatGatherers(): array {
        return static::$_veneerInstance->getStatGatherers();
    }
    public static function gatherDefaultStats(Ref4 $dump, Inst $context): void {}
    public static function getVendorPath(): string {
        return static::$_veneerInstance->getVendorPath();
    }
    public static function setRenderer(Ref5 $renderer): Inst {
        return static::$_veneerInstance->setRenderer(...func_get_args());
    }
    public static function useTextRenderer(): Inst {
        return static::$_veneerInstance->useTextRenderer();
    }
    public static function getRenderer(): Ref5 {
        return static::$_veneerInstance->getRenderer();
    }
    public static function getActiveRenderer(): Ref5 {
        return static::$_veneerInstance->getActiveRenderer();
    }
    public static function setTransport(Ref6 $transport): Inst {
        return static::$_veneerInstance->setTransport(...func_get_args());
    }
    public static function getTransport(): Ref6 {
        return static::$_veneerInstance->getTransport();
    }
};
