<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Stack;

use DecodeLabs\Glitch\Context;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;

/**
 * Represents a normalized stack trace
 */
class Trace implements \IteratorAggregate, \Countable, Inspectable
{
    protected $frames = [];

    /**
     * Extract trace from exception and build
     */
    public static function fromException(\Throwable $e, int $rewind=0): self
    {
        return self::fromArray($e->getTrace(), $rewind);
    }

    /**
     * Generate a backtrace and build
     */
    public static function create(int $rewind=0): self
    {
        return self::fromArray(debug_backtrace(), $rewind + 1);
    }

    /**
     * Take a trace array and convert to objects
     */
    public static function fromArray(array $trace, int $rewind=0): self
    {
        $last = null;

        if ($rewind) {
            if ($rewind > count($trace) - 1) {
                throw \Glitch::EOutOfRange('Stack rewind out of stack frame range', [
                    'data' => [
                        'rewind' => $rewind,
                        'trace' => $trace
                    ]
                ]);
            }

            while ($rewind >= 0) {
                $rewind--;
                $last = array_shift($trace);
            }
        }

        if (!$last) {
            $last = array_shift($trace);
        }

        $last['fromFile'] = $last['file'] ?? null;
        $last['fromLine'] = $last['line'] ?? null;
        $output = [];

        foreach ($trace as $frame) {
            $frame['fromFile'] = $frame['file'] ?? null;
            $frame['fromLine'] = $frame['line'] ?? null;
            $frame['file'] = $last['fromFile'];
            $frame['line'] = $last['fromLine'];

            $output[] = new Frame($frame);
            $last = $frame;
        }

        return new self($output);
    }


    /**
     * Check list of frames
     */
    public function __construct(array $frames)
    {
        foreach ($frames as $frame) {
            if (!$frame instanceof Frame) {
                throw \Glitch::EUnexpectedValue([
                    'message' => 'Trace frame is not an instance of DecodeLabs\\Glitch\\Frame',
                    'data' => $frame
                ]);
            }

            $this->frames[] = $frame;
        }
    }



    /**
     * Get the frame list as an array
     */
    public function getFrames(): array
    {
        return $this->frames;
    }

    /**
     * Get first frame
     */
    public function getFirstFrame(): ?Frame
    {
        return $this->frames[0] ?? null;
    }


    /**
     * Get file from first frame
     */
    public function getFile(): ?string
    {
        if (!isset($this->frames[0])) {
            return null;
        }

        return $this->frames[0]->getFile();
    }

    /**
     * Get line from first frame
     */
    public function getLine(): ?int
    {
        if (!isset($this->frames[0])) {
            return null;
        }

        return $this->frames[0]->getLine();
    }



    /**
     * Create iterator
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->frames);
    }


    /**
     * Export to generic array
     */
    public function toArray(): array
    {
        return array_map(function ($frame) {
            return $frame->toArray();
        }, $this->frames);
    }

    /**
     * Count frames
     */
    public function count(): int
    {
        return count($this->frames);
    }



    /**
     * Debug info
     */
    public function __debugInfo(): array
    {
        $output = [];
        $frames = $this->getFrames();
        $count = count($frames);

        foreach ($frames as $i => $frame) {
            $output[($count - $i).': '.$frame->getSignature(true)] = [
                'file' => Context::getDefault()->normalizePath($frame->getCallingFile()).' : '.$frame->getCallingLine()
            ];
        }

        return $output;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, callable $inspector): void
    {
        $entity->setStackTrace($this);
    }
}
