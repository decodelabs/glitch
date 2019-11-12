<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\Trace;

use DecodeLabs\Glitch\Context;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

/**
 * Main root exception inheritance
 * This trait is automatically rolled into the generated exception
 * when using the Factory
 */
trait EGlitchTrait
{
    protected $http;
    protected $data;
    protected $rewind;
    protected $stackTrace;

    protected $type;
    protected $interfaces;

    protected $params = [];

    /**
     * Override the standard Exception constructor to simplify instantiation
     */
    public function __construct($message, array $params=[])
    {
        if (!is_string($message)) {
            $message = 'blah';
        }

        $args = [
            $message,
            (int)($params['code'] ?? 0)
        ];

        if ($this instanceof \ErrorException) {
            $args[] = (int)($params['severity'] ?? 0);
            $args[] = (string)($params['file'] ?? '');
            $args[] = (int)($params['line'] ?? 0);
        }

        $args[] = $params['previous'] ?? null;

        parent::__construct(...$args);

        if (isset($params['file'])) {
            $this->file = $params['file'];
        }

        if (isset($params['line'])) {
            $this->line = $params['line'];
        }

        unset($params['code'], $params['previous'], $params['file'], $params['line']);

        $this->data = $params['data'] ?? null;
        $this->rewind = $params['rewind'] ?? 0;

        if (isset($params['http'])) {
            $this->http = (int)$params['http'];
        }

        $this->type = $params['type'] ?? null;
        $this->interfaces = (array)($params['interfaces'] ?? []);

        if (isset($params['stackTrace']) && $params['stackTrace'] instanceof Trace) {
            $this->stackTrace = $params['stackTrace'];
        }

        unset($params['data'], $params['rewind'], $params['http'], $params['type'], $params['interfaces'], $params['stackTrace']);
        $this->params = $params;
    }

    /**
     * Set arbitrary data
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Retrieve previously stored data
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * Associate error with HTTP code
     */
    public function setHttpCode(?int $code)
    {
        $this->http = $code;
        return $this;
    }

    /**
     * Get associated HTTP code
     */
    public function getHttpCode(): ?int
    {
        return $this->http;
    }


    /**
     * Get first call from trace
     */
    public function getStackFrame(): Frame
    {
        return $this->getStackTrace()->getFirstFrame();
    }

    /**
     * Generate a StackTrace object from Exception trace
     */
    public function getStackTrace(): Trace
    {
        if (!$this->stackTrace) {
            $this->stackTrace = Trace::fromException($this, $this->rewind + 1);
        }

        return $this->stackTrace;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        $output = $this->getMessage()."\n".
            'in '.Glitch::normalizePath($this->getFile()).' : '.$this->getLine()."\n\n".
            $this->getStackTrace();

        return $output;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $parts = [];

        if (!empty($this->interfaces)) {
            $parts = $this->interfaces;
        }

        if (isset($this->type) && $this->type !== 'Exception') {
            $parts[] = $this->type;
        }

        if (!empty($parts)) {
            foreach ($parts as $i => $part) {
                $inner = explode('\\', $part);
                $parts[$i] = array_pop($inner);
            }

            $parts = array_unique($parts);
            $name = implode(' | ', $parts);
        } else {
            $name = $entity->getName();
        }

        $entity
            ->setType('exception')
            ->setName($name)
            ->setText($this->message)
            ->setClass('@EGlitch')
            ->setProperty('*code', $inspector($this->code))
            ->setProperty('*http', $inspector($this->http));

        foreach ($this->params as $key => $value) {
            $entity->setProperty('*'.$key, $inspector($value));
        }

        $entity
            ->setProperty('!previous', $inspector($this->getPrevious(), function ($entity) {
                $entity->setOpen(false);
            }))
            ->setValues($this->data !== null ? ['data' => $inspector($this->data)] : null)
            ->setShowKeys(false)
            ->setFile($this->file)
            ->setStartLine($this->line)
            ->setStackTrace($this->getStackTrace());
    }
}
