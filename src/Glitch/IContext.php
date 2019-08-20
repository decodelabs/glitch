<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch;

interface IContext
{
    // Mode
    public function setRunMode(string $mode): IContext;
    public function getRunMode(): string;

    public function isProduction(): bool;
    public function isTesting(): bool;
    public function isDevelopment(): bool;


    // Dump
    public function dump(array $vars, int $rewind=null): void;
    public function dumpDie(array $vars, int $rewind=null): void;

    // Stubs
    public function incomplete($data=null, int $rewind=null): void;


    // Logs
    public function logException(\Throwable $e): void;


    // Start time
    public function setStartTime(float $time): IContext;
    public function getStartTime(): float;


    // Path aliases
    public function registerPathAlias(string $name, string $path): IContext;
    public function registerPathAliases(array $aliases): IContext;
    public function getPathAliases(): array;
    public function normalizePath(string $path): string;


    // Object inspectors
    public function registerObjectInspector(string $class, callable $inspector): IContext;
    public function getObjectInspectors(): array;
}
