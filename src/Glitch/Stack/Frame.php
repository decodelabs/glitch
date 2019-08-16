<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Stack;

/**
 * Represents a single entry in a stack trace
 */
class Frame
{
    protected $function;
    protected $className;
    protected $namespace;
    protected $type;
    protected $args = [];

    protected $callingFile;
    protected $callingLine;
    protected $originFile;
    protected $originLine;


    /**
     * Generate a new trace and pull out a single frame
     * depending on the rewind range
     */
    public static function create(int $rewind=0): Frame
    {
        $data = debug_backtrace();

        if ($rewind >= count($data) - 1) {
            throw \Glitch::EOutOfRange('Stack rewind of stack frame range');
        }

        if ($rewind) {
            $data = array_slice($data, $rewind);
        }

        $last = array_shift($data);
        $output = array_shift($data);

        $output['fromFile'] = $output['file'] ?? null;
        $output['fromLine'] = $output['line'] ?? null;
        $output['file'] = $last['file'] ?? null;
        $output['line'] = $last['line'] ?? null;

        return new self($output);
    }


    /**
     * Build the frame object from a stack trace frame array
     */
    public function __construct(array $frame)
    {
        $this->callingFile = $frame['fromFile'] ?? null;
        $this->callingLine = $frame['fromLine'] ?? null;
        $this->originFile = $frame['file'] ?? null;
        $this->originLine = $frame['line'] ?? null;
        $this->function = $frame['function'] ?? null;

        if (isset($frame['class'])) {
            $parts = explode('\\', $frame['class']);
            $this->className = array_pop($parts);
        } elseif ($this->function !== null) {
            $parts = explode('\\', $this->function);
            $this->function = array_pop($parts);
        }

        if (!empty($parts)) {
            $this->namespace = implode('\\', $parts);
        }

        if (isset($frame['type'])) {
            switch ($frame['type']) {
                case '::':
                    $this->type = 'staticMethod';
                    break;

                case '->':
                    $this->type = 'objectMethod';
                    break;
            }
        } elseif ($this->namespace !== null) {
            $this->type = 'namespaceFunction';
        } elseif ($this->function) {
            $this->type = 'globalFunction';
        }

        if (isset($frame['args'])) {
            $this->args = (array)$frame['args'];
        }
    }



    /**
     * Get detected frame type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Get type method invoke type
     */
    public function getInvokeType(): ?string
    {
        switch ($this->type) {
            case 'staticMethod':
                return '::';

            case 'objectMethod':
                return '->';
        }

        return null;
    }

    /**
     * Is type static method?
     */
    public function isStaticMethod(): bool
    {
        return $this->type === 'staticMethod';
    }

    /**
     * Is type object method?
     */
    public function isObjectMethod(): bool
    {
        return $this->type === 'objectMethod';
    }

    /**
     * Is type namespace function?
     */
    public function isNamespaceFunction(): bool
    {
        return $this->type === 'namespaceFunction';
    }

    /**
     * Is type global function?
     */
    public function isGlobalFunction(): bool
    {
        return $this->type === 'globalFunction';
    }




    /**
     * Get frame namespace if applicable
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * Is there a namespace?
     */
    public function hasNamespace(): bool
    {
        return $this->namespace !== null;
    }



    /**
     * Get containing class (qualified) if applicable
     */
    public function getClass(): ?string
    {
        if ($this->className === null) {
            return null;
        }

        $output = $this->namespace !== null ?
            $this->namespace.'\\' : '';

        $output .= $this->className;
        return $output;
    }

    /**
     * Get containing class name
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * Is there a class?
     */
    public function hasClass(): bool
    {
        return $this->className !== null;
    }

    /**
     * Normalize a classname
     */
    public static function normalizeClassName(string $class): string
    {
        $name = [];
        $parts = explode(':', $class);

        while (!empty($parts)) {
            $part = trim(array_shift($parts));

            if (preg_match('/^class@anonymous(.+)(\(([0-9]+)\))/', $part, $matches)) {
                //$name[] = core\fs\Dir::stripPathLocation($matches[1]).' : '.($matches[3] ?? null);
                $name[] = $matches[1].' : '.($matches[3] ?? null);
            } elseif (preg_match('/^eval\(\)\'d/', $part)) {
                $name = ['eval[ '.implode(' : ', $name).' ]'];
            } else {
                $name[] = $part;
            }
        }

        return implode(' : ', $name);
    }



    /**
     * Get function name
     */
    public function getFunctionName(): ?string
    {
        return $this->function;
    }

    /**
     * Get args array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Are there args?
     */
    public function hasArgs(): bool
    {
        return !empty($this->args);
    }

    /**
     * How many args?
     */
    public function countArgs(): int
    {
        return count($this->args);
    }

    /**
     * Generate a string representation of args
     */
    public function getArgString(): string
    {
        $output = [];

        if (!is_array($this->args)) {
            $this->args = [$this->args];
        }

        foreach ($this->args as $arg) {
            if (is_string($arg)) {
                if (strlen($arg) > 16) {
                    $arg = substr($arg, 0, 16).'...';
                }

                $arg = '\''.$arg.'\'';
            } elseif (is_array($arg)) {
                $arg = '['.count($arg).']';
            } elseif (is_object($arg)) {
                $arg = self::normalizeClassName(get_class($arg)).' Object';
            } elseif (is_bool($arg)) {
                $arg = $arg ? 'true' : 'false';
            } elseif (is_null($arg)) {
                $arg = 'null';
            }

            $output[] = $arg;
        }

        return '('.implode(', ', $output).')';
    }


    /**
     * Generate a full frame signature
     */
    public function getSignature(?bool $argString=false): string
    {
        $output = '';

        if ($this->namespace !== null) {
            $output = $this->namespace.'\\';
        }

        if ($this->className !== null) {
            $output .= self::normalizeClassName($this->className);
        }

        if ($this->type) {
            $output .= $this->getInvokeType();
        }

        $output .= $this->function;

        if ($argString) {
            $output .= $this->getArgString();
        } elseif ($argString !== null) {
            $output .= '(';

            if (!empty($this->args)) {
                $output .= count($this->args);
            }

            $output .= ')';
        }

        return $output;
    }




    /**
     * Get origin file
     */
    public function getFile(): ?string
    {
        return $this->originFile;
    }

    /**
     * Get origin line
     */
    public function getLine(): ?int
    {
        return $this->originLine;
    }

    /**
     * Get calling file
     */
    public function getCallingFile(): ?string
    {
        return $this->callingFile;
    }

    /**
     * Get calling line
     */
    public function getCallingLine(): ?int
    {
        return $this->callingLine;
    }


    /**
     * Convert to a generic array
     */
    public function toArray(): array
    {
        return [
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'function' => $this->function,
            'class' => $this->className,
            'namespace' => $this->namespace,
            'type' => $this->type,
            'args' => $this->args
        ];
    }
}
