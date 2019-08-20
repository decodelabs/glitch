<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch;

use Glitch\Stack\Frame;
use Glitch\Stack\Trace;
use Glitch\Dumper\Inspector;
use Glitch\Dumper\Dump;

class Context implements IContext
{
    protected static $default;

    protected $startTime;
    protected $runMode = 'development';
    protected $pathAliases = [];
    protected $objectInspectors = [];


    /**
     * Create / fetch default context
     */
    public static function getDefault(): IContext
    {
        if (!self::$default) {
            self::setDefault(new self());
        }

        return self::$default;
    }

    /**
     * Set custom default context
     */
    public static function setDefault(IContext $default): void
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
    }



    /**
     * Set active run mode
     */
    public function setRunMode(string $mode): IContext
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
        $this->tempHandler()->dump(array_shift($values), ...$values);
    }

    /**
     * Send variables to dump, exit and render
     */
    public function dumpDie(array $values, int $rewind=null): void
    {
        $this->tempHandler()->dumpDie(array_shift($values), ...$values);
    }


    protected function tempHandler()
    {
        static $output;

        if (!isset($output)) {
            $output = new \Glitch\Dumper\Symfony();
        }

        return $output;
    }


    public function dd2(array $values, int $rewind=null): void
    {
        $trace = Trace::create($rewind + 1);
        $inspector = new Inspector($this);

        $dump = new Dump(
            $trace,
            microtime(true) - $this->getStartTime(),
            memory_get_peak_usage()
        );

        foreach ($values as $value) {
            $dump->addEntity($inspector->inspectValue($value));
        }
        dd($dump);
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
    public function setStartTime(float $time): IContext
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
    public function registerPathAlias(string $name, string $path): IContext
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
    public function registerPathAliases(array $aliases): IContext
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
     * Register callable inspector for a specific class
     */
    public function registerObjectInspector(string $class, callable $inspector): IContext
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
}
