<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Renderer;

use DecodeLabs\Exceptional\Exception as ExceptionalException;

use DecodeLabs\Glitch\Dumper\Dump;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Packet;
use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\RendererTrait;
use DecodeLabs\Glitch\Stat;
use Throwable;

class Cli implements Renderer
{
    use RendererTrait;


    public const bool RenderInProduction = true;
    public const bool RenderClosed = false;

    public const array RenderSections = [
        'info' => false,
        'meta' => false,
        'text' => true,
        'properties' => true,
        'values' => true,
        'stack' => true
    ];

    public const bool RenderStack = false;

    protected const FgColors = [
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'white' => 37,
        'default' => 38,
        'reset' => 39
    ];

    protected const BgColors = [
        'black' => 40,
        'red' => 41,
        'green' => 42,
        'yellow' => 43,
        'blue' => 44,
        'magenta' => 45,
        'cyan' => 46,
        'white' => 47,
        'default' => 48,
        'reset' => 49
    ];

    protected const Options = [
        'bold' => [1, 22],
        'dim' => [2, 22],
        'underline' => [4, 24],
        'blink' => [5, 25],
        'reverse' => [7, 27],
        'private' => [8, 28]
    ];

    /**
     * @var array<array<string|null>>
     */
    protected array $formatStack = [];


    /**
     * Build a stat list header bar
     *
     * @param array<Stat> $stats
     */
    protected function renderStats(
        array $stats
    ): string {
        $output = [];

        foreach ($stats as $stat) {
            if (null === ($statString = $stat->render())) {
                continue;
            }

            $output[] = $this->format($statString, 'cyan');
        }

        return implode(' | ', $output);
    }

    /**
     * Inspect handled exception
     */
    public function renderException(
        Throwable $exception,
        Entity $entity,
        Dump $dataDump
    ): Packet {
        $output = [];

        if (!empty($header = $this->renderHeader('exception'))) {
            $output[] = $header;
        }

        $output[] = $this->renderStats($dataDump->getStats());
        $output[] = $this->renderExceptionEntity($entity);
        $output[] = $this->renderExceptionMessage($exception);
        $output[] = $this->renderTrace($dataDump->getTrace(), true);

        if (!empty($footer = $this->renderFooter())) {
            $output[] = $footer;
        }

        return $this->exportExceptionBuffer($output);
    }

    /**
     * Render exception message
     */
    protected function renderExceptionMessage(
        Throwable $exception
    ): string {
        $message = $exception->getMessage();
        $code = $exception->getCode();

        if ($exception instanceof ExceptionalException) {
            $httpCode = $exception->http;
        } else {
            $httpCode = null;
        }

        $head = [];

        if ($code) {
            $head[] =
                $this->format('#', 'white', null, 'dim') .
                $this->format((string)$code, 'magenta');
        }
        if ($httpCode) {
            $head[] =
                $this->format('HTTP', 'white', null, 'dim') . ' ' .
                $this->format((string)$httpCode, 'magenta');
        }

        $output = '';

        if (!empty($head)) {
            $output .= implode(' | ', $head) . "\n";
        }

        $output .= $this->renderMultiLineString($message, 'exception');
        return $output;
    }

    /**
     * Flatten buffer for final render
     *
     * @param array<string> $buffer
     */
    protected function exportBuffer(
        array $buffer,
        bool $final
    ): Packet {
        $output = "\n" . implode("\n\n", $buffer) . "\n\n";
        return new Packet($output, 'text/plain');
    }

    /**
     * Render a null scalar
     */
    protected function renderNull(
        ?string $class = null
    ): string {
        return $this->format('null', 'magenta', null, 'bold');
    }

    /**
     * Render a boolean scalar
     */
    protected function renderBool(
        bool $value,
        ?string $class = null
    ): string {
        return $this->format($value ? 'true' : 'false', 'magenta', null, 'bold');
    }

    /**
     * Render a integer scalar
     */
    protected function renderInt(
        int $value,
        ?string $class = null
    ): string {
        return $this->format((string)$value, 'blue', null, 'bold');
    }

    /**
     * Render a float scalar
     */
    protected function renderFloat(
        float $value,
        ?string $class = null
    ): string {
        return $this->format($this->normalizeFloat($value), 'blue', null, 'bold');
    }

    /**
     * Render a single identifier string
     */
    protected function renderIdentifierString(
        string $string,
        ?string $class,
        ?int $forceSingleLineMax = null
    ): string {
        $options = [];

        if ($class !== null) {
            $parts = explode(' ', $class);
            $mod = array_pop($parts);
            $style = array_pop($parts);
        } else {
            $mod = 'public';
            $style = 'values';
        }

        $color = 'white';
        $output = '';

        switch ($style) {
            case 'info':
                $color = 'cyan';
                break;

            case 'meta':
                $color = 'white';
                break;

            case 'properties':
                $color = 'white';
                $options[] = 'bold';

                switch ($mod) {
                    case 'public':
                        break;

                    case 'protected':
                        $output .= $this->format('*', 'blue', null, 'bold');
                        break;

                    case 'private':
                        $output .= $this->format('!', 'red', null, 'bold');
                        break;
                }
                break;

            case 'values':
                $color = 'yellow';
                break;
        }

        $output .= $this->stackFormat($color, null, ...$options);
        $output .= $this->renderStringLine($string, $forceSingleLineMax);
        $output .= $this->popFormat();
        return $output;
    }

    /**
     * Render a standard multi line string
     */
    protected function renderMultiLineString(
        string $string,
        ?string $class = null
    ): string {
        $string = str_replace("\r", '', $string);
        $parts = explode("\n", $string);
        $quotes = $class === 'exception' ? '!!!' : '"""';

        $output = [];
        $output[] = $this->format($quotes . ' ' . mb_strlen($string), 'white', null, 'dim');

        foreach ($parts as $part) {
            $output[] = $this->format($this->renderStringLine($part), 'red', null, 'bold') .
                $this->format('⏎', 'white', null, 'dim'); // @ignore-non-ascii
        }

        $output[] = $this->format($quotes, 'white', null, 'dim');

        return implode("\n", $output);
    }

    /**
     * Render a standard single line string
     */
    protected function renderSingleLineString(
        string $string,
        ?string $class = null,
        ?int $forceSingleLineMax = null
    ): string {
        $output = $this->format('"', 'white', null, 'dim');
        $output .= $this->stackFormat('red', null, 'bold');
        $output .= $this->renderStringLine($string, $forceSingleLineMax);
        $output .= $this->popFormat();
        $output .= $this->format('"', 'white', null, 'dim');

        return $output;
    }

    /**
     * Render binary string chunk
     */
    protected function renderBinaryStringChunk(
        string $chunk
    ): string {
        return $this->format($chunk, 'magenta') . ' ';
    }

    /**
     * Render a detected ascii control character
     */
    protected function wrapControlCharacter(
        string $control
    ): string {
        return $this->format($control, 'white', 'red', 'bold');
    }


    /**
     * Render structure grammar
     */
    protected function renderGrammar(
        string $grammar
    ): string {
        return $this->format($grammar, 'white', null, 'dim');
    }

    /**
     * Render structure pointer
     */
    protected function renderPointer(
        string $pointer
    ): string {
        return $this->format($pointer, 'white', null, 'dim');
    }

    /**
     * Render line number
     */
    protected function renderLineNumber(
        int $number
    ): string {
        return $this->format(str_pad((string)$number, 2), 'blue', null, 'bold');
    }

    /**
     * Render file path
     */
    protected function renderSourceFile(
        string $path,
        ?string $class = null
    ): string {
        return $this->format($path, 'yellow');
    }

    /**
     * Render source line
     */
    protected function renderSourceLine(
        int $number
    ): string {
        return $this->format((string)$number, 'magenta', null, 'bold');
    }


    /**
     * render signature namespace part
     */
    protected function renderSignatureNamespace(
        string $namespace
    ): string {
        return $this->format($namespace, 'cyan');
    }

    /**
     * render signature class part
     */
    protected function renderSignatureClass(
        string $class
    ): string {
        return $this->format($class, 'cyan', null, 'bold');
    }

    /**
     * render signature constant part
     */
    protected function renderSignatureConstant(
        string $constant
    ): string {
        return $this->format($constant, 'magenta');
    }

    /**
     * Wrap signature function block
     */
    protected function wrapSignatureFunction(
        string $function,
        ?string $class = null
    ): string {
        $output = '';

        if ($class == 'closure') {
            $output .= $this->format('{', 'white', null, 'dim');
        }

        $output .= $this->format($function, 'blue');

        if ($class == 'closure') {
            $output .= $this->format('}', 'white', null, 'dim');
        }

        return $output;
    }

    /**
     * render signature object name
     */
    protected function renderSignatureObject(
        string $object
    ): string {
        return $this->format($object, 'green');
    }

    /**
     * Wrap entity name if reference
     */
    protected function wrapReferenceName(
        string $name
    ): string {
        return
            $this->format('&', 'white', null, 'dim') .
            //$this->format($name, 'green', null, 'bold');
            $name;
    }

    /**
     * Wrap entity name if reference
     */
    protected function renderEntityNamePart(
        string $name,
        bool $sensitive = false
    ): string {
        return $this->format($name, $sensitive ? 'red' : 'green', null, 'bold');
    }

    /**
     * render entity length tag
     */
    protected function renderEntityLength(
        int $length
    ): string {
        return $this->format((string)$length, 'cyan', null, 'bold');
    }

    /**
     * render entity class name
     */
    protected function renderEntityClassName(
        string $class
    ): string {
        return $this->format($class, 'white');
    }

    /**
     * render object id tag
     */
    protected function renderEntityOid(
        int $objectId,
        bool $isRef,
        string $id
    ): string {
        return
            $this->format('#', 'white', null, 'dim') .
            $this->format((string)$objectId, 'white');
    }



    /**
     * Render basic list
     *
     * @param array<string> $lines
     */
    protected function renderBasicList(
        array $lines,
        ?string $class = null
    ): string {
        if ($class !== null) {
            $classes = explode(' ', $class);
            $isInline = in_array('inline', $classes);
        } else {
            $isInline = false;
        }

        $wrap = false;

        if ($isInline) {
            $wrap = true;
            $test = implode(', ', $lines);

            if (strlen($test) > 80) {
                $isInline = false;
            }
        }

        $sep = $isInline ? $this->format(', ', 'white', null, 'dim') : "\n";
        $output = implode($sep, $lines);

        if ($wrap) {
            if ($isInline) {
                $output = ' ' . $output . ' ';
            } else {
                $output = $this->indent("\n" . $output) . "\n";
            }
        }

        return $output;
    }



    /**
     * Format output for colours
     */
    protected function format(
        string $message,
        ?string $fgColor,
        ?string $bgColor = null,
        string ...$options
    ): string {
        $output = $this->setFormat($fgColor, $bgColor, ...$options);
        $output .= $message;

        /** @var array<?string> $args */
        $args = array_slice(func_get_args(), 1);
        $output .= $this->applyStackedFormat($args);

        return $output;
    }

    /**
     * Stack a format
     */
    protected function stackFormat(
        ?string $fgColor,
        ?string $bgColor = null,
        string ...$options
    ): string {
        array_unshift($this->formatStack, $args = func_get_args());
        return $this->setFormat($fgColor, $bgColor, ...$options);
    }

    protected function setFormat(
        ?string $fgColor,
        ?string $bgColor = null,
        string ...$options
    ): string {
        $setCodes = [];

        if ($fgColor !== null) {
            $setCodes[] = self::FgColors[$fgColor];
        }

        if ($bgColor !== null) {
            $setCodes[] = self::BgColors[$bgColor];
        }

        foreach ($options as $option) {
            $setCodes[] = self::Options[$option][0];
        }

        return sprintf("\033[%sm", implode(';', $setCodes));
    }

    protected function resetFormat(
        ?string $fgColor,
        ?string $bgColor = null,
        string ...$options
    ): string {
        $setCodes = [];
        $setCodes[] = self::FgColors['reset'];
        $setCodes[] = self::BgColors['reset'];

        foreach ($options as $option) {
            $setCodes[] = self::Options[$option][1];
        }

        return sprintf("\033[%sm", implode(';', $setCodes));
    }

    /**
     * Pop formats
     */
    protected function popFormat(): string
    {
        $args = array_shift($this->formatStack);

        if ($args === null) {
            return '';
        }

        return $this->applyStackedFormat($args);
    }

    /**
     * Apply stacked args
     *
     * @param array<string|null> $args
     */
    protected function applyStackedFormat(
        array $args
    ): string {
        $output = $this->resetFormat(...$args);

        if (isset($this->formatStack[0])) {
            $args = $this->formatStack[0];

            if (!isset($args[0])) {
                $args[0] = 'reset';
            }

            if (!isset($args[1])) {
                $args[1] = 'reset';
            }

            $output .= $this->setFormat(...$args);
        }

        return $output;
    }
}
