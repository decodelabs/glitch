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
    //const SPACES = 2;
    //const RENDER_CLOSED = true;
    /*
    const RENDER_SECTIONS = [
        'info' => true,
        'meta' => true,
        'text' => true,
        'properties' => true,
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
     * Convert Dump object to HTML string
     */
    public function renderDump(Dump $dump, bool $isFinal=false): string
    {
        $output = [];

        if (!empty($header = $this->renderHeader())) {
            $output[] = $header;
        }

        $output[] = $this->renderStats($dump->getStats());
        $output[] = $this->renderDumpEntities($dump);

        if ((static::RENDER_STACK ?? true) && $trace = $dump->getTrace()) {
            $output[] = $this->renderTrace($trace);
        }

        if (!empty($footer = $this->renderFooter())) {
            $output[] = $footer;
        }

        return $this->exportBuffer($output);
    }

    /**
     * Render dump header
     */
    protected function renderHeader(): string
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
            $output[] = $stat->render('text');
        }

        return implode(' | ', $output);
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
     * Render final trace
     */
    protected function renderTrace(Trace $trace): string
    {
        return $this->renderEntity(
            (new Entity('stack'))
                ->setName('stack')
                ->setStackTrace($trace)
                ->setOpen(false)
                ->setLength($trace->count()),
            true
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
    protected function renderSourceFile(string $path): string
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
            $output[] = $this->renderSignatureCallType('::');
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
            $output[] = $this->renderSignatureCallType($frame->getInvokeType());
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

                $function[] = $this->renderSignatureBracket('{');

                foreach ($parts as $part) {
                    $fArgs[] = $this->renderString($part, 'identifier');
                }

                $function[] = implode(', ', $fArgs);
                $function[] = $this->renderSignatureBracket('}');
                $function = implode($function);
            } else {
                $function = $this->esc($function);
            }

            $output[] = $this->wrapSignatureFunction($function);
        }

        // Args
        $output[] = $this->renderSignatureBracket('(');
        $args = [];

        foreach ($frame->getArgs() as $arg) {
            if (is_object($arg)) {
                $args[] = $this->renderSignatureObject($frame::normalizeClassName(get_class($arg)));
            } elseif (is_array($arg)) {
                $args[] = $this->wrapSignatureArray(
                    $this->renderSignatureBracket('[').count($arg).$this->renderSignatureBracket(']')
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

        $output[] = implode($this->renderSignatureComma().' ', $args);
        $output[] = $this->renderSignatureBracket(')');

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
     * Passthrough call type
     */
    protected function renderSignatureCallType(string $type): string
    {
        return $type;
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
     * Passthrough bracket
     */
    protected function renderSignatureBracket(string $bracket): string
    {
        return $bracket;
    }

    /**
     * Passthrough comma
     */
    protected function renderSignatureComma(): string
    {
        return ',';
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
    protected function renderEntity(Entity $entity, bool $forceOpen=false, int $level=0): string
    {
        $id = $linkId = $entity->getId();
        $name = $this->esc($entity->getName() ?? $entity->getType());
        $showInfo = true;
        $isRef = $showClass = false;
        $showText = $entity->getText() !== null;
        $showProperties = (bool)$entity->getProperties();
        $showValues = (bool)$entity->getValues();
        $showStack = (bool)$entity->getStackTrace();
        $open = $entity->isOpen();

        switch ($type = $entity->getType()) {
            case 'arrayReference':
                $name = 'array';

                // no break
            case 'objectReference':
                $linkId = 'ref-'.$id.'-'.spl_object_id($entity);
                $name = $this->wrapReferenceName($name);
                $isRef = true;
                break;

            case 'resource':
                $showInfo = false;
                break;

            case 'class':
            case 'interface':
            case 'trait':
                $showClass = true;
                $showInfo = false;
                break;

            case 'stack':
                if (!$entity->getClass()) {
                    $showInfo = false;
                }
                break;

            case 'flags':
                $showInfo = false;
                break;

            case 'const':
                $showInfo = false;
                $const = $entity->getName();
                $name = $this->renderConstName($const);
                break;
        }

        if (!static::RENDER_SECTIONS['info'] ?? true) {
            $showInfo = false;
        }
        if (!static::RENDER_SECTIONS['meta'] ?? true) {
            $showMeta = false;
        }
        if (!static::RENDER_SECTIONS['text'] ?? true) {
            $showText = false;
        }
        if (!static::RENDER_SECTIONS['properties'] ?? true) {
            $showProperties = false;
        }
        if (!static::RENDER_SECTIONS['values'] ?? true) {
            $showValues = false;
        }
        if (!static::RENDER_SECTIONS['stack'] ?? true && $type !== 'stack') {
            $showStack = false;
        }



        $header = [];

        // Name
        if ($isRef) {
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

        // Info
        if ($showInfo) {
            $header[] = $this->renderEntityInfoButton($linkId);
        }

        // Meta
        if ($showMeta = (bool)$entity->getAllMeta()) {
            $header[] = $this->renderEntityMetaButton($linkId);
        }

        // Text
        if ($showText) {
            $header[] = $this->renderEntityTextButton($linkId);
        }

        // Properties
        if ($showProperties) {
            $header[] = $this->renderEntityPropertiesButton($linkId);
        }

        // Values
        if ($showValues) {
            $header[] = $this->renderEntityValuesButton($linkId);
        }

        // Stack
        if ($showStack) {
            $header[] = $this->renderEntityStackButton($type, $open, $linkId);
        }

        // Bracket
        if ($hasBody = ($showInfo || $showMeta || $showText || $showProperties || $showValues || $showStack)) {
            $header[] = $this->renderGrammar('{');
        }

        // Object id
        if (null !== ($objectId = $entity->getObjectId())) {
            $header[] = $this->renderEntityOid($objectId, $isRef, $id);
        }


        $output = [];
        $output[] = $this->wrapEntityHeader(implode(' ', array_filter($header)), $type, $linkId);


        $hasBodyContent = $showText || $showProperties || $showValues || $showStack;
        $renderClosed = static::RENDER_CLOSED ?? true;

        if (!$open && !$renderClosed && !$forceOpen && $level > 4) {
            $hasBodyContent = false;
        }

        // Body
        if ($hasBodyContent) {
            $body = [];

            // Info
            if ($showInfo) {
                $body[] = $this->renderInfoBlock($entity, $level);
            }

            // Meta
            if ($showMeta) {
                $body[] = $this->renderMetaBlock($entity, $level);
            }

            // Text
            if ($showText) {
                $body[] = $this->renderTextBlock($entity, $level);
            }

            // Properties
            if ($showProperties) {
                $body[] = $this->renderPropertiesBlock($entity, $level);
            }

            // Values
            if ($showValues) {
                $body[] = $this->renderValuesBlock($entity, $level);
            }

            // Stack
            if ($showStack) {
                $body[] = $this->renderStackBlock($entity, $level);
            }

            $output[] = $this->wrapEntityBody(implode("\n", array_filter($body)), $open, $linkId);
        }

        // Footer
        if ($hasBody) {
            $output[] = $this->wrapEntityFooter($this->renderGrammar('}'));
        }

        return implode($hasBodyContent ? "\n" : ' ', $output);
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
     * Empty info button stub
     */
    protected function renderEntityInfoButton(string $linkId): string
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
        } else {
            $output = $this->indent(
                $this->renderScalar($entity->getText())
            );
        }

        return $this->wrapEntityBodyBlock($output, 'text', true, $id, $type);
    }

    /**
     * Render entity properties block
     */
    protected function renderPropertiesBlock(Entity $entity, int $level=0): string
    {
        $id = $entity->getId();

        $output = $this->indent(
            $this->renderList($entity->getProperties(), 'properties', true, null, $level + 1)
        );

        return $this->wrapEntityBodyBlock($output, 'properties', true, $id);
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
                $line[] = $this->renderSourceFile($this->context->normalizePath($frame->getCallingFile()));
                $line[] = $this->renderSourceLine($frame->getCallingLine());
                $lines[] = implode(' ', $line);
            }

            $output = $this->indent(
                $this->renderBasicList($lines, 'stack')
            );
        } else {
            //die('oi2');
            $newEntity = (new Entity('stack'))
                ->setName('stack')
                ->setStackTrace($trace)
                ->setLength($trace->count());

            $output = $this->renderEntity($newEntity, false, $level + 1);
        }

        return $this->wrapEntityBodyBlock($output, 'stack', true, $id);
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

            case 'properties':
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
                $line[] = $this->renderEntity($value, false, $level + 1);
            } elseif (is_array($value)) {
                $isAssoc = $this->arrayIsAssoc($value);
                $line[] = $this->renderGrammar('{');
                $line[] = $this->renderList($value, $style, $isAssoc, $isAssoc ? 'map' : 'inline', $level + 1);
                $line[] = $this->renderGrammar('}');
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
        return implode("\n", $lines);
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
