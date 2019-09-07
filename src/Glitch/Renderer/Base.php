<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Renderer;

use DecodeLabs\Glitch\Context;
use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Dumper\Dump;
use DecodeLabs\Glitch\Dumper\Entity;

trait Base
{
    //const RENDER_IN_PRODUCTION = false;
    //const SPACES = 2;
    //const RENDER_CLOSED = true;
    /*
    const RENDER_SECTIONS = [
        'info' => true,
        'meta' => true,
        'text' => true,
        'props' => true,
        'values' => true,
        'stack' => true
    ];
    */
    //const RENDER_STACK = true;

    protected $context;

    /**
     * Construct with Context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }


    /**
     * Should render in production?
     */
    protected function shouldRenderInProduction(): bool
    {
        return static::RENDER_IN_PRODUCTION;
    }


    /**
     * Should dump / exception body dump?
     */
    protected function shouldRender(): bool
    {
        return !$this->context->isProduction() || $this->shouldRenderInProduction();
    }


    /**
     * Convert Dump object to HTML string
     */
    public function renderDump(Dump $dump): string
    {
        if (!$this->shouldRender()) {
            return '';
        }

        $output = [];

        if (!empty($header = $this->renderHeader('dump'))) {
            $output[] = $header;
        }

        $output[] = $this->renderStats($dump->getStats());
        $output[] = $this->renderDumpEntities($dump);

        if ((static::RENDER_STACK ?? true) && $trace = $dump->getTrace()) {
            $output[] = $this->renderTrace($trace, false);
        }

        if (!empty($footer = $this->renderFooter())) {
            $output[] = $footer;
        }

        return $this->exportDumpBuffer($output);
    }


    /**
     * Inspect handled exception
     */
    public function renderException(\Throwable $exception, Entity $entity, Dump $dataDump): string
    {
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

            if ($trace = $dataDump->getTrace()) {
                $output[] = $this->renderTrace($trace, true);
            }
        }

        if (!empty($footer = $this->renderFooter())) {
            $output[] = $footer;
        }

        return $this->exportExceptionBuffer($output);
    }




    /**
     * Render dump header
     */
    protected function renderHeader(string $class): string
    {
        return '';
    }

    /**
     * Render basic stat list
     */
    protected function renderStats(array $stats): string
    {
        $output = [];

        foreach ($stats as $stat) {
            if (null === ($statString = $stat->render('text'))) {
                continue;
            }

            $output[] = $statString;
        }

        return implode(' | ', $output);
    }


    /**
     * Render exception message
     */
    protected function renderExceptionMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();
        $code = $exception->getCode();
        $httpCode = $exception instanceof \EGlitch ? $exception->getHttpCode() : null;

        $head = [];

        if ($code) {
            $head[] = '#'.$code;
        }
        if ($httpCode) {
            $head[] = 'HTTP '.$httpCode;
        }

        $output = '';

        if (!empty($head)) {
            $output .= implode(' | ', $head)."\n";
        }

        $output .= $this->esc($message);
        return $output;
    }

    /**
     * Render a default message in production mode
     */
    protected function renderProductionExceptionMessage(\Throwable $exception): string
    {
        return '';
    }


    /**
     * Render main list of entities
     */
    protected function renderDumpEntities(Dump $dump): string
    {
        $output = [];

        foreach ($dump->getEntities() as $value) {
            if ($value instanceof Entity) {
                $output[] = $this->renderEntity($value);
            } else {
                $output[] = $this->renderScalar($value);
            }
        }

        return implode("\n\n", $output);
    }


    /**
     * Render exception entity
     */
    protected function renderExceptionEntity(Entity $entity): string
    {
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
    protected function renderTrace(Trace $trace, bool $open=false): string
    {
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
     */
    protected function exportBuffer(array $buffer): string
    {
        return implode("\n\n", $buffer);
    }

    /**
     * Flatten dump buffer for final render
     */
    protected function exportDumpBuffer(array $buffer): string
    {
        return $this->exportBuffer($buffer);
    }

    /**
     * Flatten dump buffer for final render
     */
    protected function exportExceptionBuffer(array $buffer): string
    {
        return $this->exportBuffer($buffer);
    }



    /**
     * Render a scalar value
     */
    protected function renderScalar($value, ?string $class=null): string
    {
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
                $output = $this->renderString($value, $class);
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
    protected function renderNull(?string $class=null): string
    {
        return 'null';
    }

    /**
     * Passthrough boolean
     */
    protected function renderBool(bool $value, ?string $class=null): string
    {
        return $value ? 'true' : 'false';
    }

    /**
     * Passthrough integer
     */
    protected function renderInt(int $value, ?string $class=null): string
    {
        return (string)$value;
    }

    /**
     * Passthrough float
     */
    protected function renderFloat(float $value, ?string $class=null): string
    {
        return $this->normalizeFloat($value);
    }


    /**
     * Convert a float value to string ensuring decimals
     */
    protected function normalizeFloat(float $number): string
    {
        $output = (string)$number;

        if (false === strpos($output, '.')) {
            $output .= '.0';
        }

        return $output;
    }

    /**
     * Render standard string
     */
    protected function renderString(string $string, ?string $class=null, int $forceSingleLineMax=null): string
    {
        $isMultiLine = $forceSingleLineMax === null && false !== strpos($string, "\n");

        if ($class !== null) {
            return $this->renderIdentifierString($string, $class, $forceSingleLineMax);
        } elseif ($isMultiLine) {
            return $this->renderMultiLineString($string);
        } else {
            return $this->renderSingleLineString($string, $forceSingleLineMax);
        }
    }

    /**
     * Passthrough string
     */
    protected function renderIdentifierString(string $string, string $class, int $forceSingleLineMax=null): string
    {
        return $string;
    }

    /**
     * Passthrough string
     */
    protected function renderMultiLineString(string $string): string
    {
        return $string;
    }

    /**
     * Passthrough string
     */
    protected function renderSingleLineString(string $string, int $forceSingleLineMax=null): string
    {
        return $string;
    }


    /**
     * render string for rendering
     */
    protected function renderStringLine(string $line, int $maxLength=null): string
    {
        $shorten = false;

        if ($maxLength !== null && strlen($line) > $maxLength) {
            $shorten = true;
            $line = substr($line, 0, $maxLength);
        }

        $output = $this->esc($line);

        $output = preg_replace_callback('/[[:cntrl:]]/', function ($matches) {
            $hex = implode(unpack("H*", $matches[0]));
            $output = $this->normalizeHex($hex);
            return $this->wrapControlCharacter($output);
        }, $output);

        if ($shorten) {
            $output .= $this->renderGrammar('…');
        }

        return $output;
    }

    /**
     * render binary string chunk
     */
    protected function renderBinaryStringChunk(string $chunk): string
    {
        return $chunk;
    }


    /**
     * Normalize a hex value for output
     */
    protected function normalizeHex(string $hex): string
    {
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
                $output = '\\x'.$hex;
                break;
        }

        return $output;
    }


    /**
     * Render character
     */
    protected function wrapControlCharacter(string $control): string
    {
        return $control;
    }

    /**
     * Render grammar
     */
    protected function renderGrammar(string $grammar): string
    {
        return $grammar;
    }

    /**
     * Render pointer
     */
    protected function renderPointer(string $pointer): string
    {
        return $pointer;
    }

    /**
     * Render line number
     */
    protected function renderLineNumber(int $number): string
    {
        return str_pad((string)$number, 2);
    }

    /**
     * Render file path
     */
    protected function renderSourceFile(string $path, ?string $class=null): string
    {
        return $path;
    }

    /**
     * Render source line
     */
    protected function renderSourceLine(int $number): string
    {
        return (string)$number;
    }





    /**
     * Split const name for rendering
     */
    protected function renderConstName(string $const): string
    {
        $parts = explode('::', $const, 2);
        $const = array_pop($parts);

        if (empty($parts)) {
            $class = null;
            $parts = explode('\\', $const);
            $const = array_pop($parts);
        } else {
            $parts = explode('\\', array_shift($parts));
            $class = array_pop($parts);
        }

        $namespace = implode('\\', $parts);

        if (empty($namespace)) {
            $namespace = '\\';
        }

        $output = [];

        $output[] = $this->renderSignatureNamespace($namespace);

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
    protected function renderStackFrameSignature(Frame $frame): string
    {
        $output = [];

        // Namespace
        if (null !== ($class = $frame->getClassName())) {
            $output[] = $this->renderSignatureNamespace($frame->getNamespace().'\\');
            $output[] = $this->renderSignatureClass($frame::normalizeClassName($class));
        }

        // Type
        if ($frame->getInvokeType() !== null) {
            $output[] = $this->renderGrammar($frame->getInvokeType());
        }

        // Function
        if (false !== strpos($function = $frame->getFunctionName(), '{closure}')) {
            $output[] = $this->wrapSignatureFunction($this->renderSignatureClosure(), 'closure');
        } else {
            if (false !== strpos($function, ',')) {
                $parts = explode(',', $function);
                $parts = array_map('trim', $parts);
                $function = [];
                $fArgs = [];

                $function[] = $this->renderGrammar('{');

                foreach ($parts as $part) {
                    $fArgs[] = $this->renderString($part, 'identifier');
                }

                $function[] = implode($this->renderGrammar(',').' ', $fArgs);
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

        foreach ($frame->getArgs() as $arg) {
            if (is_object($arg)) {
                $args[] = $this->renderSignatureObject($frame::normalizeClassName(get_class($arg)));
            } elseif (is_array($arg)) {
                $args[] = $this->wrapSignatureArray(
                    $this->renderGrammar('[').count($arg).$this->renderGrammar(']')
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

                    default:
                        $args[] = '';
                        break;
                }
            }
        }

        $output[] = implode($this->renderGrammar(', ').' ', $args);
        $output[] = $this->renderGrammar(')');

        return implode('', $output);
    }


    /**
     * Passthrough signature
     */
    protected function wrapSignature(string $signature, ?string $class=null): string
    {
        return $signature;
    }

    /**
     * Passthrough namespace
     */
    protected function renderSignatureNamespace(string $namespace): string
    {
        return $namespace;
    }

    /**
     * Passthrough class
     */
    protected function renderSignatureClass(string $class): string
    {
        return $class;
    }

    /**
     * Passthrough constant
     */
    protected function renderSignatureConstant(string $constant): string
    {
        return $constant;
    }

    /**
     * Passthrough function
     */
    protected function wrapSignatureFunction(string $function, ?string $class=null): string
    {
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
    protected function wrapSignatureArray(string $array, ?string $class=null): string
    {
        return $array;
    }

    /**
     * Passthrough
     */
    protected function renderSignatureObject(string $object): string
    {
        return $object;
    }





    /**
     * Render an individual entity
     */
    protected function renderEntity(Entity $entity, int $level=0, array $overrides=null): string
    {
        $id = $linkId = $entity->getId();
        $name = $this->esc($entity->getName() ?? $entity->getType());
        $isRef = $showClass = $forceBody = false;
        $open = $entity->isOpen();

        $sections = [
            'info' => true,
            'meta' => (bool)$entity->getAllMeta(),
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
                $linkId = 'ref-'.$id.'-'.spl_object_id($entity);
                $isRef = true;
                break;

            case 'resource':
                $sections['info'] = false;
                break;

            case 'class':
            case 'interface':
            case 'trait':
                $showClass = true;
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

        if ($overrides['info'] ?? null === true) {
            $sections['info'] = true;
        }

        foreach ($keys as $key) {
            $overrides[$key] = $overrides[$key] ?? static::RENDER_SECTIONS[$key] ?? true;
            $check = true;

            if ($key == 'stack') {
                $check = $type !== 'stack';
            }

            if (!$overrides[$key] && $check) {
                $sections[$key] = false;
            }
        }



        $header = [];

        if ($type === 'const') {
            $name = $this->renderConstName($entity->getName());
        } else {
            $nameParts = explode('|', $name);

            foreach ($nameParts as $i => $part) {
                $nameParts[$i] = $this->renderEntityNamePart(trim($part));
            }

            $g = $this->renderGrammar('|');
            $name = implode(' '.$g.' ', $nameParts);
        }


        // Name
        if ($isRef) {
            $name = $this->wrapReferenceName($name);
            $header[] = $this->wrapEntityNameReference($name, $open, $id);
        } else {
            $header[] = $this->wrapEntityName($name, $open, $linkId);
        }

        // Length
        if (null !== ($length = $entity->getLength())) {
            $header[] = $this->renderEntityLength($length);
        }

        // Class
        if ($showClass) {
            $header[] = $this->renderPointer(':');
            $header[] = $this->renderEntityClassName($entity->getClass());
        }

        $buttons = [];

        // Info
        if ($sections['info']) {
            $buttons[] = $this->renderEntityInfoButton($linkId, $isRef);
        }

        // Meta
        if ($sections['meta']) {
            $buttons[] = $this->renderEntityMetaButton($linkId);
        }

        // Text
        if ($sections['text']) {
            $buttons[] = $this->renderEntityTextButton($linkId);
        }

        // Definition
        if ($sections['def']) {
            $buttons[] = $this->renderEntityDefinitionButton($linkId);
        }

        // Properties
        if ($sections['props']) {
            $buttons[] = $this->renderEntityPropertiesButton($linkId);
        }

        // Values
        if ($sections['values']) {
            $buttons[] = $this->renderEntityValuesButton($linkId);
        }

        // Stack
        if ($sections['stack']) {
            $buttons[] = $this->renderEntityStackButton($type, $open, $linkId);
        }

        // Buttons
        if (!empty($buttons)) {
            $header[] = $this->wrapEntityButtons(implode(' ', $buttons));
        }

        // Bracket
        if ($hasBody = $forceBody || in_array(true, $sections, true)) {
            $header[] = $this->renderGrammar('{');
        }

        // Object id
        if (null !== ($objectId = $entity->getObjectId())) {
            $header[] = $this->renderEntityOid($objectId, $isRef, $id);
        }


        $output = [];
        $output[] = $this->wrapEntityHeader(implode(' ', array_filter($header)), $type, $linkId);


        $hasBodyContent = $sections['text'] || $sections['def'] || $sections['props'] || $sections['values'] || $sections['stack'];
        $renderClosed = static::RENDER_CLOSED ?? true;

        if (!$open && !$renderClosed && $level > 4) {
            $hasBody = false;
        }

        $classes = [];

        // Body
        if ($hasBody) {
            $body = [];

            // Info
            if ($sections['info']) {
                $body[] = $this->renderInfoBlock($entity, $level);
            }

            // Meta
            if ($sections['meta']) {
                $body[] = $this->renderMetaBlock($entity, $level);
            }

            // Text
            if ($sections['text']) {
                $classes[] = 'w-t-text';
                $body[] = $this->renderTextBlock($entity, $level);
            }

            // Definition
            if ($sections['def']) {
                $classes[] = 'w-t-def';
                $body[] = $this->renderDefinitionBlock($entity, $level);
            }

            // Properties
            if ($sections['props']) {
                $classes[] = 'w-t-props';
                $body[] = $this->renderPropertiesBlock($entity, $level);
            }

            // Values
            if ($sections['values']) {
                $classes[] = 'w-t-values';
                $body[] = $this->renderValuesBlock($entity, $level);
            }

            // Stack
            if ($sections['stack']) {
                $classes[] = 'w-t-stack';
                $body[] = $this->renderStackBlock($entity, $level);
            }

            $output[] = $this->wrapEntityBody(implode("\n", array_filter($body)), $open && $hasBodyContent, $linkId);
        }

        // Footer
        if ($hasBody) {
            $output[] = $this->wrapEntityFooter($this->renderGrammar('}'));
        }

        if ($open && ($hasBody && !empty($classes))) {
            $classes[] = 'w-body';
        } elseif ($isRef && $sections['info']) {
            $classes[] = 'w-t-info';
        }


        return $this->wrapEntity(implode($hasBodyContent ? "\n" : ' ', $output), implode(' ', $classes));
    }

    /**
     * Wrap entity
     */
    protected function wrapEntity(string $entity): string
    {
        return $entity;
    }


    /**
     * Passthrough header
     */
    protected function wrapEntityHeader(string $header, string $type, string $linkId): string
    {
        return $header;
    }

    /**
     * Passthrough reference name
     */
    protected function wrapReferenceName(string $name): string
    {
        return '&'.$name;
    }

    /**
     * Passthrough entity name
     */
    protected function wrapEntityName(string $name, bool $open, string $linkId): string
    {
        return $name;
    }


    /**
     * Wrap entity name if reference
     */
    protected function renderEntityNamePart(string $name): string
    {
        return $name;
    }


    /**
     * Passthrough entity name reference
     */
    protected function wrapEntityNameReference(string $name, bool $open, string $id): string
    {
        return $name;
    }


    /**
     * render entity length
     */
    protected function renderEntityLength(int $length): string
    {
        return (string)$length;
    }

    /**
     * render entity class name
     */
    protected function renderEntityClassName(string $class): string
    {
        return $class;
    }

    /**
     * Wrap buttons
     */
    protected function wrapEntityButtons(string $buttons): string
    {
        return $buttons;
    }


    /**
     * Empty info button stub
     */
    protected function renderEntityInfoButton(string $linkId, bool $isRef): string
    {
        return '';
    }

    /**
     * Empty meta button stub
     */
    protected function renderEntityMetaButton(string $linkId): string
    {
        return '';
    }

    /**
     * Empty text button stub
     */
    protected function renderEntityTextButton(string $linkId): string
    {
        return '';
    }

    /**
     * Empty definition button stub
     */
    protected function renderEntityDefinitionButton(string $linkId): string
    {
        return '';
    }

    /**
     * Empty properties button stub
     */
    protected function renderEntityPropertiesButton(string $linkId): string
    {
        return '';
    }

    /**
     * Empty values button stub
     */
    protected function renderEntityValuesButton(string $linkId): string
    {
        return '';
    }

    /**
     * Empty stack button stub
     */
    protected function renderEntityStackButton(string $type, bool $open, string $linkId): string
    {
        return '';
    }


    /**
     * render entity object id
     */
    protected function renderEntityOid(int $objectId, bool $isRef, string $id): string
    {
        return '#'.$objectId;
    }




    /**
     * Render entity info block
     */
    protected function renderInfoBlock(Entity $entity, int $level=0): string
    {
        $id = $linkId = $entity->getId();

        switch ($entity->getType()) {
            case 'arrayReference':
            case 'objectReference':
                $linkId = 'ref-'.$id.'-'.spl_object_id($entity);
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
            $info['location'] = $this->context->normalizePath($file).' : '.$entity->getStartLine();
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
        if (($hash = $entity->getHash()) || $type == 'array') {
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
    protected function renderMetaBlock(Entity $entity, int $level=0): string
    {
        $id = $entity->getId();

        $output = $this->indent(
            $this->renderList($entity->getAllMeta(), 'meta', true, null, $level + 1)
        );

        return $this->wrapEntityBodyBlock($output, 'meta', false, $id);
    }

    /**
     * Render entity text block
     */
    protected function renderTextBlock(Entity $entity, int $level=0): string
    {
        $id = $entity->getId();
        $type = $entity->getType();

        if ($type === 'binary') {
            $chunks = explode("\n", trim(chunk_split($entity->getText(), 2, "\n")));
            $output = [];

            foreach ($chunks as $chunk) {
                $output[] = $this->renderBinaryStringChunk($chunk);
            }

            $output = $this->indent(
                implode($output)
            );
        } elseif ($type === 'exception') {
            $output = $this->indent(
                $this->renderMultiLineString($entity->getText(), true)
            );
        } else {
            $output = $this->indent(
                $this->renderScalar($entity->getText())
            );
        }

        return $this->wrapEntityBodyBlock($output, 'text', true, $id, $type);
    }

    /**
     * Render entity text block
     */
    protected function renderDefinitionBlock(Entity $entity, int $level=0): string
    {
        $id = $entity->getId();
        $type = $entity->getType();

        $output = $this->indent(
            $this->renderIdentifierString($entity->getDefinition(), 'def')
        );

        return $this->wrapEntityBodyBlock($output, 'def', true, $id, $type);
    }

    /**
     * Render entity properties block
     */
    protected function renderPropertiesBlock(Entity $entity, int $level=0): string
    {
        $id = $entity->getId();

        $output = $this->indent(
            $this->renderList($entity->getProperties(), 'props', true, null, $level + 1)
        );

        return $this->wrapEntityBodyBlock($output, 'props', true, $id);
    }

    /**
     * Render entity values block
     */
    protected function renderValuesBlock(Entity $entity, int $level=0): string
    {
        $id = $entity->getId();

        $output = $this->indent(
            $this->renderList($entity->getValues(), 'values', $entity->shouldShowKeys(), null, $level + 1)
        );

        return $this->wrapEntityBodyBlock($output, 'values', true, $id);
    }


    /**
     * Render entity stack trace block
     */
    protected function renderStackBlock(Entity $entity, int $level=0): string
    {
        $id = $entity->getId();
        $type = $entity->getType();
        $trace = $entity->getStackTrace();

        if ($type == 'stack') {
            $count = count($trace);
            $lines = [];

            foreach ($trace as $i => $frame) {
                $line = [];
                $line[] = $this->renderLineNumber($count - $i);
                $line[] = $this->wrapSignature($this->renderStackFrameSignature($frame));
                $line[] = "\n   ";

                if (null !== ($file = $frame->getCallingFile())) {
                    $line[] = $this->renderSourceFile($this->context->normalizePath($file));
                    $line[] = $this->renderSourceLine($frame->getCallingLine());
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
    protected function wrapStackFrame(string $frame): string
    {
        return $frame;
    }


    /**
     * Wrap entity body
     */
    protected function wrapEntityBody(string $body, bool $open, string $linkId): string
    {
        return $body;
    }


    /**
     * Wrap entity body block
     */
    protected function wrapEntityBodyBlock(string $block, string $type, bool $open, string $linkId, ?string $class=null): string
    {
        return $block;
    }


    /**
     * Wrap entity footer
     */
    protected function wrapEntityFooter(string $footer): string
    {
        return $footer;
    }


    /**
     * Render list
     */
    protected function renderList(array $items, string $style, bool $includeKeys=true, string $class=null, int $level=0): string
    {
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

                $line[] = $this->renderScalar($key, 'identifier key '.$style.' '.$mod);
                $line[] = $this->renderPointer($pointer);
            }

            if ($value instanceof Entity) {
                $line[] = $this->renderEntity($value, $level + 1);
            } elseif (is_array($value)) {
                $isAssoc = $this->arrayIsAssoc($value);
                $line[] = $this->renderGrammar('{').
                    $this->renderList($value, $style, $isAssoc, $isAssoc ? 'map' : 'inline', $level + 1).
                    $this->renderGrammar('}');
            } else {
                $line[] = $this->renderScalar($value, $asIdentifier ? 'identifier' : null);
            }

            $lines[] = implode(' ', $line);
        }


        return $this->renderBasicList($lines, 'list '.$style.' '.$class);
    }


    /**
     * Render basic list
     */
    protected function renderBasicList(array $lines, ?string $class=null): string
    {
        $classes = explode(' ', $class);

        $sep = in_array('inline', $classes) ? ', ' : "\n";
        return implode($sep, $lines);
    }


    /**
     * Apply indents
     */
    protected function indent(string $lines): string
    {
        if ($spaces = static::SPACES ?? 2) {
            $space = str_repeat(' ', $spaces);
            $lines = $space.str_replace("\n", "\n".$space, $lines);
        }

        return $lines;
    }


    /**
     * Test if array is associative
     */
    protected function arrayIsAssoc(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }


    /**
     * Escape a value for output
     */
    protected function esc(?string $value): string
    {
        return $value ?? '';
    }
}
