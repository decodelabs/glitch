<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Renderer;

use DecodeLabs\Glitch\Context;
use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\Dumper\Dump;
use DecodeLabs\Glitch\Dumper\Entity;

class Html implements Renderer
{
    const SPACES = 2;

    protected $output = [];
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
        $this->output = [];
        $space = str_repeat(' ', self::SPACES);

        // Header
        $this->renderHeader();


        // Stats
        $this->output[] = '<header class="stats container-fluid">';

        foreach ($dump->getStats() as $key => $stat) {
            $this->output[] = $space.'<span class="stat stat-'.$key.' badge badge-'.$stat->getClass().'" title="'.$this->esc($stat->getName()).'">'.$stat->render('html').'</span>';
        }

        $this->output[] = '</header>';


        // Entities
        $this->output[] = '<div class="container-fluid">';

        foreach ($dump->getEntities() as $value) {
            $this->output[] = '<samp class="dump">';

            if ($value instanceof Entity) {
                $this->renderEntity($value);
            } else {
                $this->renderScalar($value);
            }

            $this->output[] = '</samp>';
        }


        // Trace
        if ($trace = $dump->getTrace()) {
            $this->output[] = '<samp class="dump trace">';
            $this->renderEntity(
                (new Entity('stack'))
                    ->setName('stack')
                    ->setStackTrace($trace)
                    ->setOpen(false)
                    ->setLength($trace->count())
            );

            $this->output[] = '</samp>';
        }

        $this->output[] = '</div>';


        // Footer
        $this->output[] = '</body>';
        $this->output[] = '</html>';

        $html = implode("\n", $this->output);
        $this->output = [];


        // Wrap in iframe
        $id = uniqid('glitch-dump');

        $output = [];
        $output[] = '<iframe id="'.$id.'" frameborder="0" class="glitch-dump"></iframe>';
        $output[] = '<style>';
        $output[] = '.glitch-dump { width: 100%; height: 30rem; border: 1px solid #EEE; }';
        $output[] = '.glitch-dump:only-of-type { height: calc(100% - 8px); border: none; }';
        $output[] = '</style>';
        $output[] = '<script>';
        $output[] = 'var doc = document.getElementById(\''.$id.'\').contentWindow.document;';
        $output[] = 'doc.open();doc.write('.json_encode($html).');doc.close();';
        $output[] = '</script>';

        return implode("\n", $output);
    }


    /**
     * Render scripts and styles
     */
    protected function renderHeader(): void
    {
        $this->output[] = '<!doctype html>';
        $this->output[] = '<html lang="en">';
        $this->output[] = '<head>';

        $vendor = $this->context->getVendorPath();

        $css = [
            //'bootstrap-reboot' => $vendor.'/components/bootstrap/css/bootstrap-reboot.min.css',
            'bootstrap' => $vendor.'/components/bootstrap/css/bootstrap.min.css',
            'glitch' => __DIR__.'/assets/dump.css'
        ];

        $js = [
            'jQuery' => $vendor.'/components/jquery/jquery.min.js',
            'bootstrap' => $vendor.'/components/bootstrap/js/bootstrap.bundle.min.js',
            'glitch' => __DIR__.'/assets/dump.js'
        ];


        // Meta
        $this->output[] = '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';


        // Css
        foreach ($css as $name => $path) {
            $this->output[] = '<style id="style-'.$name.'">';
            $this->output[] = file_get_contents($path);
            $this->output[] = '</style>';
        }

        // Js
        foreach ($js as $name => $path) {
            $this->output[] = '<script id="script-'.$name.'">';
            $this->output[] = file_get_contents($path);
            $this->output[] = '</script>';
        }


        $output[] = '</head>';
        $output[] = '<body>';
    }


    /**
     * Render a scalar value
     */
    protected function renderScalar($value, ?string $class=null): void
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

        $this->output[] = $output;
    }



    /**
     * Render a null scalar
     */
    protected function renderNull(?string $class=null): string
    {
        return '<span class="null'.($class !== null ? ' '.$class : null).'">null</span>';
    }

    /**
     * Render a boolean scalar
     */
    protected function renderBool(bool $value, ?string $class=null): string
    {
        return '<span class="bool'.($class !== null ? ' '.$class : null).'">'.($value ? 'true' : 'false').'</span>';
    }

    /**
     * Render a integer scalar
     */
    protected function renderInt(int $value, ?string $class=null): string
    {
        return '<span class="int'.($class !== null ? ' '.$class : null).'">'.$value.'</span>';
    }

    /**
     * Render a float scalar
     */
    protected function renderFloat(float $value, ?string $class=null): string
    {
        return '<span class="float'.($class !== null ? ' '.$class : null).'">'.$this->formatFloat($value).'</span>';
    }

    protected function formatFloat(float $number): string
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
            return '<span class="string '.$class.'">'.$this->prepareStringLine($string, $forceSingleLineMax).'</span>';
        } elseif ($isMultiLine) {
            $string = str_replace("\r", '', $string);
            $parts = explode("\n", $string);
            $count = count($parts);

            $output = [];
            $output[] = '<div class="string m'.($count > 10 ? ' large' : null).'"><span class="length">'.mb_strlen($string).'</span>';

            foreach ($parts as $part) {
                $output[] = '<div class="line">'.$this->prepareStringLine($part).'</div>';
            }

            $output[] = '</div>';
            return implode('', $output);
        } else {
            $output = '<span class="string s"><span class="line">'.$this->prepareStringLine($string, $forceSingleLineMax).'</span>';

            if ($forceSingleLineMax === null) {
                $output .= '<span class="length">'.mb_strlen($string).'</span>';
            }

            $output .= '</span>';

            return $output;
        }
    }

    /**
     * Prepare string for rendering
     */
    protected function prepareStringLine(string $line, int $maxLength=null): string
    {
        $shorten = false;

        if ($maxLength !== null && strlen($line) > $maxLength) {
            $shorten = true;
            $line = substr($line, 0, $maxLength);
        }

        $output = $this->esc($line);

        $output = preg_replace_callback('/[[:cntrl:]]/', function ($matches) {
            $hex = implode(unpack("H*", $matches[0]));

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

            return '<span class="control">'.$output.'</span>';
        }, $output);

        if ($shorten) {
            $output .= '<span class="g">…</span>';
        }

        return $output;
    }



    /**
     * Render an individual entity
     */
    protected function renderEntity(Entity $entity): void
    {
        $id = $linkId = $entity->getId();
        $name = $this->esc($entity->getName() ?? $entity->getType());
        $showInfo = true;
        $isRef = $showClass = false;
        $hasText = $entity->getText() !== null;
        $hasProperties = (bool)$entity->getProperties();
        $hasValues = (bool)$entity->getValues();
        $hasStack = (bool)$entity->getStackTrace();
        $open = $entity->isOpen();

        switch ($type = $entity->getType()) {
            case 'arrayReference':
                $name = 'array';

                // no break
            case 'objectReference':
                $linkId = 'ref-'.$id.'-'.spl_object_id($entity);
                $name = '<span class="ref">'.$name.'</span>';
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
                $showInfo = false;
                $showStack = false;
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

        $this->output[] = '<div class="entity title type-'.$type.'" id="'.$linkId.'">';

        // Name
        if ($isRef) {
            $this->output[] = '<a class="name code'.($open ? null : ' collapsed').' ref" href="#'.$id.'">'.$name.'</a>';
        } else {
            $this->output[] = '<a class="name code'.($open ? null : ' collapsed').'" data-target="#body-'.$linkId.'">'.$name.'</a>';
        }

        // Length
        if (null !== ($length = $entity->getLength())) {
            $this->output[] = '<span class="length">'.$length.'</span>';
        }

        // Class
        if ($showClass) {
            $this->output[] = '<span class="pointer">:</span>';
            $this->output[] = '<span class="class">'.$this->esc($entity->getClass()).'</span>';
        }

        // Info
        if ($showInfo) {
            $this->output[] = '<a data-target="#info-'.$linkId.'" class="info badge badge-info collapsed"><i>i</i></a>';
        }

        // Meta
        if ($showMeta = (bool)$entity->getAllMeta()) {
            $this->output[] = '<a data-target="#meta-'.$linkId.'" class="meta badge badge-secondary collapsed"><i>m</i></a>';
        }

        // Text
        if ($hasText) {
            $this->output[] = '<a data-target="#text-'.$linkId.'" class="text body badge badge-danger"><i>t</i></a>';
        }

        // Properties
        if ($hasProperties) {
            $this->output[] = '<a data-target="#properties-'.$linkId.'" class="properties body badge badge-primary"><i>p</i></a>';
        }

        // Values
        if ($hasValues) {
            $this->output[] = '<a data-target="#values-'.$linkId.'" class="values body badge badge-warning"><i>v</i></a>';
        }

        // Stack
        if ($hasStack) {
            if ($type === 'stack') {
                $this->output[] = '<a data-target="#body-'.$linkId.'" class="stack badge badge-dark'.($open ? null : ' collapsed').'"><i>s</i></a>';
            } else {
                $this->output[] = '<a data-target="#stack-'.$linkId.'" class="stack body badge badge-dark"><i>s</i></a>';
            }
        }

        // Bracket
        if ($hasBody = ($showInfo || $showMeta || $hasText || $hasProperties || $hasValues || $hasStack)) {
            $this->output[] = '<span class="g">{</span>';
        }

        // Object id
        if (null !== ($objectId = $entity->getObjectId())) {
            if ($isRef) {
                $this->output[] = '<a href="#'.$id.'" class="ref oid">'.$this->esc((string)$objectId).'</a>';
            } else {
                $this->output[] = '<span class="oid">'.$this->esc((string)$objectId).'</span>';
            }
        }



        $this->output[] = '</div>';


        // Info
        if ($showInfo) {
            $this->renderInfoBlock($entity);
        }

        // Meta
        if ($showMeta) {
            $this->renderMetaBlock($entity);
        }


        // Body
        if ($hasText || $hasProperties || $hasValues || $hasStack) {
            $this->output[] = '<div id="body-'.$linkId.'" class="collapse'.($open ? ' show' : null).' inner body">';

            // Text
            if ($hasText) {
                $this->renderTextBlock($entity);
            }

            // Properties
            if ($hasProperties) {
                $this->renderPropertiesBlock($entity);
            }

            // Values
            if ($hasValues) {
                $this->renderValuesBlock($entity);
            }

            // Stack
            if ($hasStack) {
                $this->renderStackBlock($entity);
            }

            $this->output[] = '</div>';
        }

        // Footer
        if ($hasBody) {
            $this->output[] = '<div class="entity footer">';
            $this->output[] = '<span class="g">}</span>';
            $this->output[] = '</div>';
        }
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
        $output[] = '<span class="const signature">';

        $output[] = '<i class="ns">'.$this->esc($namespace).'</i>';

        if ($class !== null) {
            $output[] = '<i class="cl">'.$this->esc($class).'</i>';
            $output[] = '<i class="ty">::</i>';
        }

        $output[] = '<i class="co">'.$this->esc($const).'</i>';

        $output[] = '</span>';
        return implode('', $output);
    }

    /**
     * Render entity info block
     */
    protected function renderInfoBlock(Entity $entity): void
    {
        $id = $linkId = $entity->getId();

        switch ($entity->getType()) {
            case 'arrayReference':
            case 'objectReference':
                $linkId = 'ref-'.$id.'-'.spl_object_id($entity);
                break;
        }

        $this->output[] = '<div id="info-'.$linkId.'" class="collapse inner"><div class="info">';

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

        $this->renderList($info, 'info');

        $this->output[] = '</div></div>';
    }

    /**
     * Render entity meta block
     */
    protected function renderMetaBlock(Entity $entity): void
    {
        $id = $entity->getId();
        $this->output[] = '<div id="meta-'.$id.'" class="collapse inner"><div class="meta">';
        $this->renderList($entity->getAllMeta(), 'meta');
        $this->output[] = '</div></div>';
    }

    /**
     * Render entity text block
     */
    protected function renderTextBlock(Entity $entity): void
    {
        $id = $entity->getId();
        $type = $entity->getType();

        $this->output[] = '<div id="text-'.$id.'" class="collapse show inner"><div class="text '.$type.'">';

        if ($type === 'binary') {
            $chunks = trim(chunk_split($entity->getText(), 2, "\n"));
            $this->output[] = '<i>'.str_replace("\n", '</i><i>', $chunks).'</i>';
        } else {
            $this->renderScalar($entity->getText());
        }

        $this->output[] = '</div></div>';
    }

    /**
     * Render entity properties block
     */
    protected function renderPropertiesBlock(Entity $entity): void
    {
        $id = $entity->getId();
        $this->output[] = '<div id="properties-'.$id.'" class="collapse show inner"><div class="properties">';
        $this->renderList($entity->getProperties(), 'properties');
        $this->output[] = '</div></div>';
    }

    /**
     * Render entity values block
     */
    protected function renderValuesBlock(Entity $entity): void
    {
        $id = $entity->getId();
        $this->output[] = '<div id="values-'.$id.'" class="collapse show inner"><div class="values">';
        $this->renderList($entity->getValues(), 'values', $entity->shouldShowKeys());
        $this->output[] = '</div></div>';
    }

    /**
     * Render entity stack trace block
     */
    protected function renderStackBlock(Entity $entity): void
    {
        $id = $entity->getId();
        $type = $entity->getType();
        $trace = $entity->getStackTrace();
        $this->output[] = '<div id="stack-'.$id.'" class="collapse show inner type-'.$type.'"><div class="stack">';

        if ($type == 'stack') {
            $this->renderStackList($trace);
        } else {
            $newEntity = (new Entity('stack'))
                ->setName('stack')
                ->setStackTrace($trace)
                ->setLength($trace->count());

            $this->renderEntity($newEntity);
        }

        $this->output[] = '</div></div>';
    }

    /**
     * Render entity stack list
     */
    protected function renderStackList(Trace $trace): void
    {
        $this->output[] = '<ul class="stack">';
        $count = count($trace);

        foreach ($trace as $i => $frame) {
            $this->output[] = '<li>';
            $this->output[] = '<span class="number">'.($count - $i).'</span>';
            $this->output[] = '<span class="signature">';
            $this->renderStackFrameSignature($frame);
            $this->output[] = '</span>';
            $this->output[] = '<span class="file">'.$this->context->normalizePath($frame->getCallingFile()).'</span>';
            $this->output[] = '<span class="line">'.$frame->getCallingLine().'</span>';
            $this->output[] = '</li>';
        }

        $this->output[] = '</ul>';
    }

    /**
     * Render stack frame signature
     */
    protected function renderStackFrameSignature(Frame $frame): void
    {
        $output = [];

        // Namespace
        if (null !== ($class = $frame->getClassName())) {
            $output[] = '<i class="ns">'.$this->esc($frame->getNamespace().'\\').'</i>';
            $output[] = '<i class="cl">'.$this->esc($frame::normalizeClassName($class)).'</i>';
        }

        // Type
        if ($frame->getType() !== null) {
            $output[] = '<i class="ty">'.$this->esc($frame->getInvokeType()).'</i>';
        }

        // Function
        if (false !== strpos($function = $frame->getFunctionName(), '{closure}')) {
            $output[] = '<i class="fn closure">closure</i>';
        } else {
            if (false !== strpos($function, ',')) {
                $parts = explode(',', $function);
                $parts = array_map('trim', $parts);
                $function = [];
                $fArgs = [];

                $function[] = '<i class="br">{</i> ';

                foreach ($parts as $part) {
                    $fArgs[] = $this->renderString($part, 'identifier');
                }

                $function[] = implode(', ', $fArgs);
                $function[] = ' <i class="br">}</i>';
                $function = implode($function);
            } else {
                $function = $this->esc($function);
            }

            $output[] = '<i class="fn">'.$function.'</i>';
        }

        // Args
        $output[] = '<i class="br">(</i>';
        $args = [];

        foreach ($frame->getArgs() as $arg) {
            if (is_object($arg)) {
                $args[] = '<i class="ob">'.$frame::normalizeClassName(get_class($arg)).'</i>';
            } elseif (is_array($arg)) {
                $args[] = '<span class="ar"><i class="br">[</i>'.count($arg).'<i class="br">]</i></span>';
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

        $output[] = implode('<i class="cm">,</i> ', $args);
        $output[] = '<i class="br">)</i>';

        $this->output[] = implode('', $output);
    }


    /**
     * Render list
     */
    protected function renderList(array $items, string $style, bool $includeKeys=true, string $class=null): void
    {
        $this->output[] = '<ul class="list '.$style.' '.$class.'">';
        $pointer = '=&gt;';
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
            $this->output[] = '<li>';

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

                $this->renderScalar($key, 'identifier key '.$mod);
                $this->output[] = '<span class="pointer">'.$pointer.'</span> ';
            }

            if ($value instanceof Entity) {
                $this->renderEntity($value);
            } elseif (is_array($value)) {
                $isAssoc = $this->arrayIsAssoc($value);
                $this->output[] = '<span class="g">{</span>';
                $this->renderList($value, $style, $isAssoc, $isAssoc ? 'map' : 'inline');
                $this->output[] = '<span class="g">}</span>';
            } else {
                $this->renderScalar($value, $asIdentifier ? 'identifier' : null);
            }

            $this->output[] = '</li>';
        }

        $this->output[] = '</ul>';
    }

    protected function arrayIsAssoc(array $arr): bool
    {
        if (array() === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }


    /**
     * Escape a value for HTML
     */
    public function esc(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
