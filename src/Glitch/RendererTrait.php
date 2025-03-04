<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use DecodeLabs\Coercion;
use DecodeLabs\Exceptional\Exception as ExceptionalException;
use DecodeLabs\Glitch\Context;
use DecodeLabs\Glitch\Dumper\Dump;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\LazyType;
use DecodeLabs\Glitch\Packet;
use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\Glitch\Stat;
use Exception;
use Throwable;

/**
 * @phpstan-require-implements Renderer
 */
trait RendererTrait
{
    protected Context $context;
    protected bool $productionOverride = false;

    /**
     * Construct with Context
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }


    /**
     * Override production rendering
     *
     * @return $this
     */
    public function setProductionOverride(
        bool $flag
    ): static {
        $this->productionOverride = $flag;
        return $this;
    }

    /**
     * Get production override
     */
    public function getProductionOverride(): bool
    {
        return $this->productionOverride;
    }

    /**
     * Should render in production?
     */
    protected function shouldRenderInProduction(): bool
    {
        return Coercion::toBool(static::RenderInProduction);
    }


    /**
     * Should dump / exception body dump?
     */
    protected function shouldRender(): bool
    {
        return
            !$this->context->isProduction() ||
            $this->shouldRenderInProduction() ||
            $this->productionOverride;
    }


    /**
     * Convert Dump object to HTML string
     */
    public function renderDump(
        Dump $dump,
        bool $final
    ): Packet {
        if (!$this->shouldRender()) {
            return $this->exportDumpBuffer([], $final);
        }

        $output = [];

        if (!empty($header = $this->renderHeader('dump'))) {
            $output[] = $header;
        }

        $output[] = $this->renderStats($dump->getStats());
        $output[] = $this->renderDumpEntities($dump);

        if (static::RenderStack) {
            $output[] = $this->renderTrace($dump->getTrace(), false);
        }

        if (!empty($footer = $this->renderFooter())) {
            $output[] = $footer;
        }

        return $this->exportDumpBuffer($output, $final);
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

        if (!$this->shouldRender()) {
            $output[] = $this->renderProductionExceptionMessage($exception);
        } else {
            $output[] = $this->renderStats($dataDump->getStats());
            $output[] = $this->renderExceptionMessage($exception);
            $output[] = $this->renderExceptionEntity($entity);
            $output[] = $this->renderTrace($dataDump->getTrace(), true);
        }

        if (!empty($footer = $this->renderFooter())) {
            $output[] = $footer;
        }

        return $this->exportExceptionBuffer($output);
    }




    /**
     * Render dump header
     */
    protected function renderHeader(
        string $class
    ): string {
        return '';
    }

    /**
     * Render basic stat list
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

            $output[] = $statString;
        }

        return implode(' | ', $output);
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
            $head[] = '#' . $code;
        }
        if ($httpCode) {
            $head[] = 'HTTP ' . $httpCode;
        }

        $output = '';

        if (!empty($head)) {
            $output .= implode(' | ', $head) . "\n";
        }

        $output .= $this->esc($message);
        return $output;
    }

    /**
     * Render a default message in production mode
     */
    protected function renderProductionExceptionMessage(
        Throwable $exception
    ): string {
        return '';
    }


    /**
     * Render main list of entities
     */
    protected function renderDumpEntities(
        Dump $dump
    ): string {
        $output = [];

        foreach ($dump->getEntities() as $value) {
            if ($value instanceof Entity) {
                $output[] = $this->renderEntity($value);
            } else {
                /** @var bool|float|int|resource|string|null $value */
                $output[] = $this->renderScalar($value);
            }
        }

        return implode("\n\n", $output);
    }


    /**
     * Render exception entity
     */
    protected function renderExceptionEntity(
        Entity $entity
    ): string {
        return $this->renderEntity($entity, 0, [
            'info' => true,
            'meta' => false,
            'text' => false,
            'props' => true,
            'values' => true,
            'stack' => false
        ]);
    }



    /**
     * Render final trace
     */
    protected function renderTrace(
        Trace $trace,
        bool $open = false
    ): string {
        return $this->renderEntity(
            (new Entity('stack'))
                ->setName('stack')
                ->setStackTrace($trace)
                ->setOpen($open)
                ->setLength($trace->count())
        );
    }

    /**
     * Render dump footer
     */
    protected function renderFooter(): string
    {
        return '';
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
        $output = implode("\n\n", $buffer);
        return new Packet($output, 'text/plain');
    }

    /**
     * Flatten dump buffer for final render
     *
     * @param array<string> $buffer
     */
    protected function exportDumpBuffer(
        array $buffer,
        bool $final
    ): Packet {
        return $this->exportBuffer($buffer, $final);
    }

    /**
     * Flatten dump buffer for final render
     *
     * @param array<string> $buffer
     */
    protected function exportExceptionBuffer(
        array $buffer
    ): Packet {
        return $this->exportBuffer($buffer, true);
    }



    /**
     * Render a scalar value
     *
     * @param bool|float|int|resource|string|null $value
     */
    protected function renderScalar(
        $value,
        ?string $class = null,
        bool $asIdentifier = false
    ): string {
        switch (true) {
            case $value === null:
                $output = $this->renderNull($class);
                break;

            case is_bool($value):
                $output = $this->renderBool($value, $class);
                break;

            case is_int($value):
                $output = $this->renderInt($value, $class);
                break;

            case is_float($value):
                $output = $this->renderFloat($value, $class);
                break;

            case is_string($value):
                $output = $this->renderString($value, $class, null, $asIdentifier);
                break;

            default:
                $output = '';
                break;
        }

        return $output;
    }


    /**
     * Passthrough null
     */
    protected function renderNull(
        ?string $class = null
    ): string {
        return 'null';
    }

    /**
     * Passthrough boolean
     */
    protected function renderBool(
        bool $value,
        ?string $class = null
    ): string {
        return $value ? 'true' : 'false';
    }

    /**
     * Passthrough integer
     */
    protected function renderInt(
        int $value,
        ?string $class = null
    ): string {
        return (string)$value;
    }

    /**
     * Passthrough float
     */
    protected function renderFloat(
        float $value,
        ?string $class = null
    ): string {
        return $this->normalizeFloat($value);
    }


    /**
     * Convert a float value to string ensuring decimals
     */
    protected function normalizeFloat(
        float $number
    ): string {
        $output = (string)$number;

        if (false === strpos($output, '.')) {
            $output .= '.0';
        }

        return $output;
    }

    /**
     * Render standard string
     */
    protected function renderString(
        string $string,
        ?string $class = null,
        ?int $forceSingleLineMax = null,
        bool $asIdentifier = false
    ): string {
        if ($asIdentifier) {
            return $this->renderIdentifierString($string, $class, $forceSingleLineMax);
        }

        $isMultiLine = $forceSingleLineMax === null && false !== strpos($string, "\n");

        if ($isMultiLine) {
            return $this->renderMultiLineString($string, $class);
        } else {
            return $this->renderSingleLineString($string, $class, $forceSingleLineMax);
        }
    }

    /**
     * Passthrough string
     */
    protected function renderIdentifierString(
        string $string,
        ?string $class,
        ?int $forceSingleLineMax = null
    ): string {
        return $string;
    }

    /**
     * Passthrough string
     */
    protected function renderMultiLineString(
        string $string,
        ?string $class = null
    ): string {
        return $string;
    }

    /**
     * Passthrough string
     */
    protected function renderSingleLineString(
        string $string,
        ?string $class = null,
        ?int $forceSingleLineMax = null
    ): string {
        return $string;
    }


    /**
     * render string for rendering
     */
    protected function renderStringLine(
        string $line,
        ?int $maxLength = null
    ): string {
        $shorten = false;

        if ($maxLength !== null && strlen($line) > $maxLength) {
            $shorten = true;
            $line = mb_substr($line, 0, $maxLength);
        }

        $output = $this->esc($line);

        $output = preg_replace_callback('/[[:cntrl:]]/u', function ($matches) {
            if (false === ($packed = unpack("H*", $matches[0]))) {
                throw new Exception('Unable to unpack control characters');
            }

            $hex = implode($packed);
            $output = $this->normalizeHex($hex);
            return $this->wrapControlCharacter($output);
        }, $output) ?? $output;

        if ($shorten) {
            $output .= $this->renderGrammar('…'); // @ignore-non-ascii
        }

        return $output;
    }

    /**
     * render binary string chunk
     */
    protected function renderBinaryStringChunk(
        string $chunk
    ): string {
        return $chunk;
    }


    /**
     * Normalize a hex value for output
     */
    protected function normalizeHex(
        string $hex
    ): string {
        switch ($hex) {
            case '07':
                $output = '\\a';
                break;

            case '1B':
                $output = '\\e';
                break;

            case '0C':
                $output = '\\f';
                break;

            case '0A':
                $output = '\\n';
                break;

            case '0D':
                $output = '\\r';
                break;

            case '09':
                $output = '\\t';
                break;

            default:
                $output = '\\x' . $hex;
                break;
        }

        return $output;
    }


    /**
     * Render character
     */
    protected function wrapControlCharacter(
        string $control
    ): string {
        return $control;
    }

    /**
     * Passthrough resource
     *
     * @param resource $value
     */
    protected function renderResource(
        $value,
        ?string $class = null
    ): string {
        return 'resource';
    }

    /**
     * Render grammar
     */
    protected function renderGrammar(
        string $grammar
    ): string {
        return $grammar;
    }

    /**
     * Render pointer
     */
    protected function renderPointer(
        string $pointer
    ): string {
        return $pointer;
    }

    /**
     * Render line number
     */
    protected function renderLineNumber(
        int $number
    ): string {
        return str_pad((string)$number, 2);
    }

    /**
     * Render file path
     */
    protected function renderSourceFile(
        string $path,
        ?string $class = null
    ): string {
        return $path;
    }

    /**
     * Render source line
     */
    protected function renderSourceLine(
        int $number
    ): string {
        return (string)$number;
    }





    /**
     * Split const name for rendering
     */
    protected function renderConstName(
        string $const
    ): string {
        $parts = explode('::', $const, 2);
        $const = (string)array_pop($parts);

        if (empty($parts)) {
            $class = null;
            $parts = explode('\\', $const);
            $const = (string)array_pop($parts);
        } else {
            $parts = explode('\\', array_shift($parts));
            $class = array_pop($parts);
        }

        $namespace = implode('\\', $parts);

        if (empty($namespace)) {
            $namespace = '\\';
        }

        $output = [];

        if (substr((string)$class, 0, 1) !== '~') {
            $parts = explode('\\', $namespace);

            foreach ($parts as $i => $part) {
                $parts[$i] = empty($part) ? null : $this->renderSignatureNamespace($part);
            }

            $output[] = implode($this->renderGrammar('\\'), $parts);
        }

        if ($class !== null) {
            $output[] = $this->renderSignatureClass($class);
            $output[] = $this->renderGrammar('::');
        }

        $output[] = $this->renderSignatureConstant($const);

        return $this->wrapSignature(implode('', $output), 'const');
    }


    /**
     * Render stack frame signature
     */
    protected function renderStackFrameSignature(
        Frame $frame
    ): string {
        $output = [];

        // Namespace
        if (null !== ($class = $frame->class)) {
            $class = $frame::normalizeClassName($class);

            if (substr((string)$class, 0, 1) === '~') {
                $class = ltrim($class, '~');
                $output[] = $this->renderPointer('~');
            }

            $parts = explode('\\', $class);
            $class = array_pop($parts);

            if (!empty($parts)) {
                $parts[] = '';
            }

            foreach ($parts as $i => $part) {
                $parts[$i] = empty($part) ? null : $this->renderSignatureNamespace($part);
            }

            $output[] = implode($this->renderGrammar('\\'), $parts);
            $output[] = $this->renderSignatureClass($class);
        }

        // Type
        if ($frame->invokeType !== null) {
            $output[] = $this->renderGrammar($frame->invokeType);
        }

        // Function
        $function = $frame->function;

        if ($function === null || false !== strpos($function, '{closure}')) {
            $output[] = $this->wrapSignatureFunction($this->renderSignatureClosure(), 'closure');
        } else {
            if (false !== strpos($function, ',')) {
                $parts = explode(',', $function);
                $parts = array_map('trim', $parts);
                $function = [];
                $fArgs = [];

                $function[] = $this->renderGrammar('{');

                foreach ($parts as $part) {
                    $fArgs[] = $this->renderIdentifierString($part, 'identifier');
                }

                $function[] = implode($this->renderGrammar(',') . ' ', $fArgs);
                $function[] = $this->renderGrammar('}');
                $function = implode($function);
            } else {
                $function = $this->esc($function);
            }

            $output[] = $this->wrapSignatureFunction($function);
        }

        // Args
        $output[] = $this->renderGrammar('(');
        $args = [];

        foreach ($frame->arguments as $arg) {
            if (is_object($arg)) {
                $args[] = $this->renderSignatureObject($frame::normalizeClassName(get_class($arg)));
            } elseif (is_array($arg)) {
                $args[] = $this->wrapSignatureArray(
                    $this->renderGrammar('[') . count($arg) . $this->renderGrammar(']')
                );
            } else {
                switch (true) {
                    case $arg === null:
                        $args[] = $this->renderNull();
                        break;

                    case is_bool($arg):
                        $args[] = $this->renderBool($arg);
                        break;

                    case is_int($arg):
                        $args[] = $this->renderInt($arg);
                        break;

                    case is_float($arg):
                        $args[] = $this->renderFloat($arg);
                        break;

                    case is_string($arg):
                        $args[] = $this->renderString($arg, null, 16);
                        break;

                    case is_resource($arg):
                        $args[] = $this->renderResource($arg);
                        break;

                    default:
                        $args[] = '';
                        break;
                }
            }
        }

        $output[] = implode($this->renderGrammar(', ') . ' ', $args);
        $output[] = $this->renderGrammar(')');

        return implode('', $output);
    }


    /**
     * Passthrough signature
     */
    protected function wrapSignature(
        string $signature,
        ?string $class = null
    ): string {
        return $signature;
    }

    /**
     * Passthrough namespace
     */
    protected function renderSignatureNamespace(
        string $namespace
    ): string {
        return $namespace;
    }

    /**
     * Passthrough class
     */
    protected function renderSignatureClass(
        string $class
    ): string {
        return $class;
    }

    /**
     * Passthrough constant
     */
    protected function renderSignatureConstant(
        string $constant
    ): string {
        return $constant;
    }

    /**
     * Passthrough function
     */
    protected function wrapSignatureFunction(
        string $function,
        ?string $class = null
    ): string {
        return $function;
    }

    /**
     * Passthrough closure
     */
    protected function renderSignatureClosure(): string
    {
        return 'closure';
    }

    /**
     * Passthrough
     */
    protected function wrapSignatureArray(
        string $array,
        ?string $class = null
    ): string {
        return $array;
    }

    /**
     * Passthrough
     */
    protected function renderSignatureObject(
        string $object
    ): string {
        return $object;
    }





    /**
     * Render an individual entity
     *
     * @param array<string, bool>|null $overrides
     */
    protected function renderEntity(
        Entity $entity,
        int $level = 0,
        ?array $overrides = null
    ): string {
        $id = $linkId = (string)$entity->getId();
        $name = $entity->getName() ?? $entity->getType();
        $isRef = $forceBody = false;
        $open = $entity->isOpen();

        $sections = [
            'info' => true,
            'meta' => (bool)$entity->getMetaList(),
            'text' => $entity->getText() !== null,
            'def' => $entity->getDefinition() !== null,
            'props' => (bool)$entity->getProperties(),
            'values' => (bool)$entity->getValues(),
            'stack' => (bool)$entity->getStackTrace()
        ];

        switch ($type = $entity->getType()) {
            case 'arrayReference':
                $name = 'array';

                // no break
            case 'objectReference':
                $linkId = 'ref-' . $id . '-' . spl_object_id($entity);
                $isRef = true;
                break;

            case 'resource':
                $sections['info'] = false;
                break;

            case 'class':
            case 'interface':
            case 'trait':
                $sections['info'] = false;
                break;

            case 'stack':
                if (!$entity->getClass()) {
                    $sections['info'] = false;
                }
                break;

            case 'flags':
                $sections['info'] = false;
                break;

            case 'const':
                $sections['info'] = false;
                break;

            case 'array':
                if (!$entity->getLength()) {
                    $sections['info'] = false;
                    $forceBody = true;
                }
                break;
        }

        $keys = ['info', 'meta', 'text', 'def', 'props', 'values', 'stack'];

        if (($overrides['info'] ?? null) === true) {
            $sections['info'] = true;
        }

        foreach ($keys as $key) {
            $overrides[$key] =
                $overrides[$key] ??
                /** @var array<string,bool> */
                static::RenderSections[$key] ??
                true;

            $check = true;

            if ($key == 'stack') {
                $check = $type !== 'stack';
            }

            if (
                !$overrides[$key] &&
                $check
            ) {
                $sections[$key] = false;
            }
        }



        $header = [];

        if ($type === 'const') {
            $name = $this->renderConstName($entity->getName() ?? 'const');
        } else {
            $nameParts = explode('|', $name);

            foreach ($nameParts as $i => $part) {
                $nameParts[$i] = $this->renderEntityNamePart(
                    trim($part),
                    $entity->isSensitive()
                );
            }

            $g = $this->renderGrammar('|');
            $name = implode(' ' . $g . ' ', $nameParts);
        }

        // Lazy
        if($entity->isLazy()) {
            $header[] = $this->renderPointer(
                ($entity->getLazyType() ?? LazyType::Unknown)->value);
        }

        // Name
        if ($isRef) {
            $name = $this->wrapReferenceName($name);
            $header[] = $this->wrapEntityNameReference($name, $open, $id, $entity->isSensitive());
        } else {
            $header[] = $this->wrapEntityName($name, $open, $linkId, $entity->isSensitive());
        }

        // Class
        if (null !== ($className = $entity->getClassName())) {
            $header[] = $this->renderPointer('~');
            $header[] = $this->renderEntityClassName($className);
        }

        // Length
        if (null !== ($length = $entity->getLength())) {
            $header[] = $this->renderPointer(':');
            $header[] = $this->renderEntityLength($length);
        }

        $buttons = [];
        $visibility = $entity->getSectionVisibility();

        // Info
        if ($sections['info']) {
            $buttons[] = $this->renderEntityInfoButton($isRef, $visibility['info']);
        }

        // Meta
        if ($sections['meta']) {
            $buttons[] = $this->renderEntityMetaButton($visibility['meta']);
        }

        // Text
        if ($sections['text']) {
            $buttons[] = $this->renderEntityTextButton($visibility['text']);
        }

        // Definition
        if ($sections['def']) {
            $buttons[] = $this->renderEntityDefinitionButton($visibility['definition']);
        }

        // Properties
        if ($sections['props']) {
            $buttons[] = $this->renderEntityPropertiesButton($visibility['properties']);
        }

        // Values
        if ($sections['values']) {
            $buttons[] = $this->renderEntityValuesButton($visibility['values']);
        }

        // Stack
        if ($sections['stack']) {
            $buttons[] = $this->renderEntityStackButton($type, $visibility['stack']);
        }

        // Buttons
        if (!empty($buttons)) {
            $header[] = $this->wrapEntityButtons(implode(' ', $buttons));
        }

        // Bracket
        if (
            // @phpstan-ignore-next-line
            ($hasBody = in_array(true, $sections, true)) ||
            $forceBody
        ) {
            $header[] = $this->renderGrammar('{');
        }

        // Object id
        if (null !== ($objectId = $entity->getObjectId())) {
            $header[] = $this->renderEntityOid($objectId, $isRef, $id);
        }


        $output = [];
        $output[] = $this->wrapEntityHeader(implode(' ', array_filter($header)), $type, $linkId);


        $hasBodyContent =
            $sections['text'] ||
            $sections['def'] ||
            $sections['props'] ||
            $sections['values'] ||
            $sections['stack'];

        $renderClosed = static::RenderClosed;

        if (
            !$open &&
            !$renderClosed &&
            $level > 4
        ) {
            $hasBody = false;
        }

        $classes = [];

        // Body
        if ($hasBody) {
            $body = [];

            // Info
            if ($sections['info']) {
                if ($visibility['info']) {
                    $classes[] = 'w-t-info';
                }

                if (
                    $renderClosed ||
                    $visibility['info']
                ) {
                    $body[] = $this->renderInfoBlock($entity, $level, $visibility['info']);
                }
            }

            // Meta
            if ($sections['meta']) {
                if ($visibility['meta']) {
                    $classes[] = 'w-t-meta';
                }

                if (
                    $renderClosed ||
                    $visibility['meta']
                ) {
                    $body[] = $this->renderMetaBlock($entity, $level, $visibility['meta']);
                }
            }

            // Text
            if ($sections['text']) {
                if ($visibility['text']) {
                    $classes[] = 'w-t-text';
                }

                if (
                    $renderClosed ||
                    $visibility['text']
                ) {
                    $body[] = $this->renderTextBlock($entity, $level, $visibility['text']);
                }
            }

            // Definition
            if ($sections['def']) {
                if ($visibility['definition']) {
                    $classes[] = 'w-t-def';
                }

                if (
                    $renderClosed ||
                    $visibility['definition']
                ) {
                    $body[] = $this->renderDefinitionBlock($entity, $level, $visibility['definition']);
                }
            }

            // Properties
            if ($sections['props']) {
                if ($visibility['properties']) {
                    $classes[] = 'w-t-props';
                }

                if (
                    $renderClosed ||
                    $visibility['properties']
                ) {
                    $body[] = $this->renderPropertiesBlock($entity, $level, $visibility['properties']);
                }
            }

            // Values
            if ($sections['values']) {
                if ($visibility['values']) {
                    $classes[] = 'w-t-values';
                }

                if (
                    $renderClosed ||
                    $visibility['values']
                ) {
                    $body[] = $this->renderValuesBlock($entity, $level, $visibility['values']);
                }
            }

            // Stack
            if ($sections['stack']) {
                if ($visibility['stack']) {
                    $classes[] = 'w-t-stack';
                }

                if (
                    $renderClosed ||
                    $visibility['stack']
                ) {
                    $body[] = $this->renderStackBlock($entity, $level, $visibility['stack']);
                }
            }

            $output[] = $this->wrapEntityBody(
                body: implode("\n", array_filter($body)),
                open: $open && $hasBodyContent,
                linkId: $linkId
            );
        }

        // Footer
        if (
            $hasBody ||
            $forceBody
        ) {
            $output[] = $this->wrapEntityFooter($this->renderGrammar('}'));
        }

        if ($open && ($hasBody && !empty($classes))) {
            $classes[] = 'w-body';
        } elseif (
            $isRef &&
            $sections['info']
        ) {
            $classes[] = 'w-t-info';
        }


        return $this->wrapEntity(implode($hasBodyContent ? "\n" : ' ', $output), implode(' ', $classes));
    }

    /**
     * Wrap entity
     */
    protected function wrapEntity(
        string $entity,
        ?string $class = null
    ): string {
        return $entity;
    }


    /**
     * Passthrough header
     */
    protected function wrapEntityHeader(
        string $header,
        string $type,
        string $linkId
    ): string {
        return $header;
    }

    /**
     * Passthrough reference name
     */
    protected function wrapReferenceName(
        string $name
    ): string {
        return '&' . $name;
    }

    /**
     * Passthrough entity name
     */
    protected function wrapEntityName(
        string $name,
        bool $open,
        string $linkId,
        bool $sensitive = false
    ): string {
        return $name;
    }


    /**
     * Wrap entity name if reference
     */
    protected function renderEntityNamePart(
        string $name,
        bool $sensitive = false
    ): string {
        return $name;
    }


    /**
     * Passthrough entity name reference
     */
    protected function wrapEntityNameReference(
        string $name,
        bool $open,
        string $id,
        bool $sensitive = false
    ): string {
        return $name;
    }


    /**
     * render entity length
     */
    protected function renderEntityLength(
        int $length
    ): string {
        return (string)$length;
    }

    /**
     * render entity class name
     */
    protected function renderEntityClassName(
        string $class
    ): string {
        return $class;
    }

    /**
     * Wrap buttons
     */
    protected function wrapEntityButtons(
        string $buttons
    ): string {
        return $buttons;
    }


    /**
     * Empty info button stub
     */
    protected function renderEntityInfoButton(
        bool $isRef,
        bool $open
    ): string {
        return '';
    }

    /**
     * Empty meta button stub
     */
    protected function renderEntityMetaButton(
        bool $open
    ): string {
        return '';
    }

    /**
     * Empty text button stub
     */
    protected function renderEntityTextButton(
        bool $open
    ): string {
        return '';
    }

    /**
     * Empty definition button stub
     */
    protected function renderEntityDefinitionButton(
        bool $open
    ): string {
        return '';
    }

    /**
     * Empty properties button stub
     */
    protected function renderEntityPropertiesButton(
        bool $open
    ): string {
        return '';
    }

    /**
     * Empty values button stub
     */
    protected function renderEntityValuesButton(
        bool $open
    ): string {
        return '';
    }

    /**
     * Empty stack button stub
     */
    protected function renderEntityStackButton(
        string $type,
        bool $open
    ): string {
        return '';
    }


    /**
     * render entity object id
     */
    protected function renderEntityOid(
        int $objectId,
        bool $isRef,
        string $id
    ): string {
        return '#' . $objectId;
    }




    /**
     * Render entity info block
     */
    protected function renderInfoBlock(
        Entity $entity,
        int $level,
        bool $open
    ): string {
        $id = $linkId = (string)$entity->getId();

        switch ($entity->getType()) {
            case 'arrayReference':
            case 'objectReference':
                $linkId = 'ref-' . $id . '-' . spl_object_id($entity);
                break;
        }

        $type = $entity->getType();

        if ($type == 'exception') {
            $type = 'object';
        }

        $info = [];
        $showClass = false;

        // Type
        switch ($type) {
            case 'object':
            case 'objectReference':
            case 'const':
                $showClass = true;
                break;

            case 'array':
            case 'arrayReference':
            case 'class':
            case 'interface':
            case 'trait':
                break;

            default:
                $info['type'] = $type;
                break;
        }

        // Class
        if ($showClass && null !== ($class = $entity->getClass())) {
            $info['class'] = $class;
        }

        // Location
        if ($file = $entity->getFile()) {
            $info['location'] = $this->context->normalizePath($file) . ' : ' . $entity->getStartLine();
        }

        // Parents
        if ($parents = $entity->getParentClasses()) {
            $info['parentClasses'] = $parents;
        }

        // Interfaces
        if ($interfaces = $entity->getInterfaces()) {
            $info['interfaces'] = $interfaces;
        }

        // Traits
        if ($traits = $entity->getTraits()) {
            $info['traits'] = $traits;
        }

        // Hash
        if (
            ($hash = $entity->getHash()) ||
            $type == 'array'
        ) {
            $info['hash'] = $hash;
        }

        $output = $this->indent(
            $this->renderList($info, 'info', true, null, $level + 1)
        );

        return $this->wrapEntityBodyBlock($output, 'info', false, $linkId);
    }

    /**
     * Render entity meta block
     */
    protected function renderMetaBlock(
        Entity $entity,
        int $level,
        bool $open
    ): string {
        $id = (string)$entity->getId();

        $output = $this->indent(
            $this->renderList($entity->getMetaList() ?? [], 'meta', true, null, $level + 1)
        );

        return $this->wrapEntityBodyBlock($output, 'meta', false, $id);
    }

    /**
     * Render entity text block
     */
    protected function renderTextBlock(
        Entity $entity,
        int $level,
        bool $open
    ): string {
        $id = (string)$entity->getId();
        $type = $entity->getType();
        $text = (string)$entity->getText();

        if ($type === 'binary') {
            $chunks = explode("\n", trim(chunk_split($text, 2, "\n")));
            $output = [];

            foreach ($chunks as $chunk) {
                $output[] = $this->renderBinaryStringChunk($chunk);
            }

            $output = $this->indent(
                implode($output)
            );
        } elseif ($type === 'exception') {
            $output = $this->indent(
                $this->renderMultiLineString($text, 'exception')
            );
        } else {
            $output = $this->indent(
                $this->renderScalar($text)
            );
        }

        return $this->wrapEntityBodyBlock($output, 'text', true, $id, $type);
    }

    /**
     * Render entity text block
     */
    protected function renderDefinitionBlock(
        Entity $entity,
        int $level,
        bool $open
    ): string {
        $id = (string)$entity->getId();
        $type = $entity->getType();

        $output = $this->indent(
            $this->renderScalar($entity->getDefinition(), 'def')
        );

        return $this->wrapEntityBodyBlock($output, 'def', true, $id, $type);
    }

    /**
     * Render entity properties block
     */
    protected function renderPropertiesBlock(
        Entity $entity,
        int $level,
        bool $open
    ): string {
        $id = (string)$entity->getId();

        $output = $this->indent(
            $this->renderList($entity->getProperties() ?? [], 'props', true, null, $level + 1)
        );

        return $this->wrapEntityBodyBlock($output, 'props', true, $id);
    }

    /**
     * Render entity values block
     */
    protected function renderValuesBlock(
        Entity $entity,
        int $level,
        bool $open
    ): string {
        $id = (string)$entity->getId();

        $output = $this->indent(
            $this->renderList($entity->getValues() ?? [], 'values', $entity->shouldShowKeys(), null, $level + 1)
        );

        return $this->wrapEntityBodyBlock($output, 'values', true, $id);
    }


    /**
     * Render entity stack trace block
     */
    protected function renderStackBlock(
        Entity $entity,
        int $level,
        bool $open
    ): string {
        $id = (string)$entity->getId();
        $type = $entity->getType();

        if (!$trace = $entity->getStackTrace()) {
            return '';
        }

        if ($type == 'stack') {
            $count = count($trace);
            $lines = [];

            foreach ($trace as $i => $frame) {
                $line = [];
                $line[] = $this->renderLineNumber($count - $i);
                $line[] = $this->wrapSignature($this->renderStackFrameSignature($frame));
                $line[] = "\n   ";

                if (null !== ($file = $frame->callingFile)) {
                    $line[] = $this->renderSourceFile((string)$this->context->normalizePath($file));

                    if (null !== ($callingLine = $frame->callingLine)) {
                        $line[] = $this->renderSourceLine($callingLine);
                    }
                } else {
                    $line[] = $this->renderSourceFile('internal', 'internal');
                }

                $lines[] = $this->wrapStackFrame(implode(' ', $line));
            }

            $output = $this->indent(
                $this->renderBasicList($lines, 'stack')
            );

            $blockType = 'stack-list';
        } else {
            $newEntity = (new Entity('stack'))
                ->setName('stack')
                ->setStackTrace($trace)
                ->setLength($trace->count())
                ->setOpen(false);

            $output = $this->indent(
                $this->renderEntity($newEntity, $level + 1)
            );

            $blockType = 'stack';
        }

        return $this->wrapEntityBodyBlock($output, $blockType, true, $id);
    }


    /**
     * Wrap stack frame
     */
    protected function wrapStackFrame(
        string $frame
    ): string {
        return $frame;
    }


    /**
     * Wrap entity body
     */
    protected function wrapEntityBody(
        string $body,
        bool $open,
        string $linkId
    ): string {
        return $body;
    }


    /**
     * Wrap entity body block
     */
    protected function wrapEntityBodyBlock(
        string $block,
        string $type,
        bool $open,
        string $linkId,
        ?string $class = null
    ): string {
        return $block;
    }


    /**
     * Wrap entity footer
     */
    protected function wrapEntityFooter(
        string $footer
    ): string {
        return $footer;
    }


    /**
     * Render list
     *
     * @param array<int|string, mixed> $items
     */
    protected function renderList(
        array $items,
        string $style,
        bool $includeKeys = true,
        ?string $class = null,
        int $level = 0
    ): string {
        $lines = [];
        $pointer = '=>';
        $asIdentifier = $access = false;

        switch ($style) {
            case 'info':
            case 'meta':
                $pointer = ':';
                $asIdentifier = true;
                break;

            case 'props':
                $access = true;
                break;
        }

        foreach ($items as $key => $value) {
            $line = [];
            $key = (string)$key;

            if ($includeKeys) {
                $mod = 'public';

                if ($access) {
                    $first = substr($key, 0, 1);

                    if ($first == '*') {
                        $key = substr($key, 1);
                        $mod = 'protected';
                    } elseif ($first == '!') {
                        $key = substr($key, 1);
                        $mod = 'private';
                    } elseif ($first == '%') {
                        $key = substr($key, 1);
                        $mod = 'virtual';
                    }
                }

                $line[] = $this->renderScalar($key, 'identifier key ' . $style . ' ' . $mod, true);
                $line[] = $this->renderPointer($pointer);
            }

            if ($value instanceof Entity) {
                $line[] = $this->renderEntity($value, $level + 1);
            } elseif (is_array($value)) {
                $isAssoc = $this->arrayIsAssoc($value);
                $line[] = $this->renderGrammar('{') .
                    $this->renderList($value, $style, $isAssoc, $isAssoc ? 'map' : 'inline', $level + 1) .
                    $this->renderGrammar('}');
            } else {
                /** @var bool|float|int|resource|string|null $value */
                $line[] = $this->renderScalar($value, $asIdentifier ? 'identifier' : null, $asIdentifier);
            }

            $lines[] = implode(' ', $line);
        }


        return $this->renderBasicList($lines, 'list ' . $style . ' ' . $class);
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
        } else {
            $classes = [];
        }

        $sep = in_array('inline', $classes) ? ', ' : "\n";
        return implode($sep, $lines);
    }


    /**
     * Apply indents
     */
    protected function indent(
        string $lines
    ): string {
        if ($spaces = static::Spaces) {
            $space = str_repeat(' ', $spaces);
            $lines = $space . str_replace("\n", "\n" . $space, $lines);
        }

        return $lines;
    }


    /**
     * Test if array is associative
     *
     * @param array<mixed> $array
     */
    protected function arrayIsAssoc(
        array $array
    ): bool {
        if (empty($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }


    /**
     * Escape a value for output
     */
    protected function esc(
        ?string $value
    ): string {
        return $value ?? '';
    }
}
