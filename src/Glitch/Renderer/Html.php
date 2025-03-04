<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Renderer;

use DecodeLabs\Coercion;
use DecodeLabs\Enlighten\Highlighter;
use DecodeLabs\Exceptional\Exception as ExceptionalException;
use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dumper\Dump;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;
use DecodeLabs\Glitch\IncompleteException;
use DecodeLabs\Glitch\Packet;
use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\RendererTrait;
use DecodeLabs\Glitch\Renderer\Html\ZestManifest;
use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\Glitch\Stat;
use Throwable;

class Html implements Renderer
{
    use RendererTrait;

    public const bool RenderInProduction = false;
    public const int Spaces = 0;
    public const bool RenderClosed = true;

    public const array RenderSections = [
        'info' => true,
        'meta' => true,
        'text' => true,
        'props' => true,
        'values' => true,
        'stack' => true
    ];

    public const bool RenderStack = true;

    protected const HttpStatuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    protected ?string $customCssFile = null;

    /**
     * Set custom css file
     *
     * @return $this
     */
    public function setCustomCssFile(
        ?string $path
    ): static {
        $this->customCssFile = $path;
        return $this;
    }

    /**
     * Get custom css file
     */
    public function getCustomCssFile(): ?string
    {
        return $this->customCssFile;
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

        $output[] = $this->renderHeader('dump');

        $output[] = '<header class="title">';
        $output[] = '<h1>Glitch <span class="version">' . Glitch::getVersion() . '</span></h1>';
        $output[] = '</header>';

        $output[] = '<div class="cols">';

        $output[] = '<div class="left"><div class="frame">';
        $output[] = $this->renderDumpEntities($dump);
        $output[] = $this->renderEnvironment($dump->getStats());
        $output[] = '</div></div>';

        $output[] = '<div class="right">';
        $output[] = $this->renderTrace($dump->getTrace());
        $output[] = '</div>';

        $output[] = '</div>';

        $output[] = $this->renderFooter();
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

        $class = 'exception';

        if ($exception instanceof IncompleteException) {
            $class .= ' incomplete';
        }

        $output[] = $this->renderHeader($class);

        if (!$this->shouldRender()) {
            $output[] = $this->renderProductionExceptionMessage($exception);
        } else {
            $output[] = '<header class="title">';
            $output[] = '<h1>Glitch <span class="version">' . Glitch::getVersion() . '</span></h1>';
            $output[] = '</header>';

            $output[] = '<div class="cols">';

            $output[] = '<div class="left">';
            $output[] = $this->renderExceptionMessage($exception);
            $output[] = $this->renderTrace($dataDump->getTrace(), true);
            $output[] = '</div>';

            $output[] = '<div class="right"><div class="frame">';
            $output[] = $this->renderExceptionEntity($entity);
            $output[] = $this->renderEnvironment($dataDump->getStats());
            $output[] = '</div></div>';

            $output[] = '</div>';
        }

        $output[] = $this->renderFooter();
        return $this->exportExceptionBuffer($output);
    }



    /**
     * Render scripts and styles
     */
    protected function renderHeader(
        string $class
    ): string {
        $output = [];
        $output[] = '<!doctype html>';
        $output[] = '<html lang="en" class="' . $class . '">';
        $output[] = '<head>';


        // Meta
        $output[] = '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
        $css = $js = [];


        // Zest
        $manifest = new ZestManifest();
        $css = $manifest->getCssData();
        $js = $manifest->getJsData();


        // Css
        if (isset($this->customCssFile)) {
            $css[$this->customCssFile] = [
                'id' => 'style-custom'
            ];
        }

        foreach ($css as $cssPath => $attrs) {
            if (file_exists($cssPath)) {
                $output[] = '<style' . $this->prepareAttrs($attrs) . '>';
                $output[] = file_get_contents($cssPath);
                $output[] = '</style>';
            } elseif (
                str_starts_with($cssPath, 'http://') ||
                str_starts_with($cssPath, 'https://')
            ) {
                $output[] = '<link rel="stylesheet" href="' . $cssPath . '"' . $this->prepareAttrs($attrs) . ' />';
            }
        }

        // Js
        //if ($this->shouldRender()) {
        foreach ($js as $jsPath => $attrs) {
            if (file_exists($jsPath)) {
                $output[] = '<script' . $this->prepareAttrs($attrs) . '>';
                $output[] = file_get_contents($jsPath);
                $output[] = '</script>';
            } elseif (
                str_starts_with($jsPath, 'http://') ||
                str_starts_with($jsPath, 'https://')
            ) {
                $output[] = '<script src="' . $jsPath . '"' . $this->prepareAttrs($attrs) . '></script>';
            }
        }
        //}


        $output[] = '</head>';
        $output[] = '<body>';
        $output[] = '<div class="container-fluid">';

        return implode("\n", $output);
    }

    /**
     * @param array<string,mixed> $attrs
     * @return string
     */
    protected function prepareAttrs(
        array $attrs
    ): string {
        $output = [];

        foreach ($attrs as $key => $value) {
            $output[] = $key . '="' . Coercion::toString($value) . '"';
        }

        if (!empty($output)) {
            return ' ' . implode(' ', $output);
        }

        return '';
    }


    /**
     * Render exception message
     */
    protected function renderExceptionMessage(
        Throwable $exception
    ): string {
        $message = $exception->getMessage();
        $code = $exception->getCode();
        $file = $this->context->normalizePath($exception->getFile());
        $line = $exception->getLine();

        if ($exception instanceof ExceptionalException) {
            $httpCode = $exception->http;
        } else {
            $httpCode = null;
        }


        $output = [];
        $output[] = '<section class="exception">';
        $output[] = '<h3>Exception</h3>';
        $output[] = '<samp class="dump exception">';
        $output[] = '<div class="message">' . $this->renderMultiLineString($message) . '</div>';

        if ($file) {
            $output[] = '<span class="attr file"><span class="label">File</span> ' . $file . ' <span class="g">:</span> ' . $line . '</span>';
        }

        if ($code) {
            $output[] = '<span class="attr code"><span class="label">Code</span> ' . $code . '</span>';
        }

        if ($httpCode) {
            if (isset(self::HttpStatuses[$httpCode])) {
                $httpCode .= ' ' . self::HttpStatuses[$httpCode];
            }

            $output[] = '<div class="attr http"><span class="label">HTTP</span> ' . $httpCode . '</div>';
        }

        $output[] = '</samp>';
        $output[] = '</section>';
        return implode("\n", $output);
    }

    /**
     * Render a default message in production mode
     */
    protected function renderProductionExceptionMessage(
        Throwable $exception
    ): string {
        return
            '<section class="production message">There was a problem serving your request - please try again later</section>'."\n".
            '<section class="production exception">' . (string)$exception . '</section>';
    }

    /**
     * Render dump entity list
     */
    protected function renderDumpEntities(
        Dump $dump
    ): string {
        $output = [];
        $output[] = '<section class="dump entity">';
        $output[] = '<h3>Dump</h3>';

        foreach ($dump->getEntities() as $value) {
            $output[] = '<samp class="dump">';

            if ($value instanceof Entity) {
                $output[] = $this->renderEntity($value);
            } else {
                /** @var bool|float|int|resource|string|null $value */
                $output[] = $this->renderScalar($value);
            }

            $output[] = '</samp>';
        }

        $output[] = '</section>';

        return implode("\n", $output);
    }

    /**
     * Render exception entity
     */
    protected function renderExceptionEntity(
        Entity $entity
    ): string {
        $entity->setSectionVisible('info', true);

        $output = [];
        $output[] = '<section class="dump object">';
        $output[] = '<h3>Exception object</h3>';
        $output[] = '<samp class="dump">';
        $output[] = $this->renderEntity($entity, 0, [
            'info' => true,
            'meta' => false,
            'text' => false,
            'props' => true,
            'values' => true,
            'stack' => false
        ]);
        $output[] = '</samp>';
        $output[] = '</section>';

        return implode("\n", $output);
    }

    /**
     * Render environment vars
     *
     * @param array<Stat> $stats
     */
    protected function renderEnvironment(
        array $stats
    ): string {
        $array = [];

        foreach ($stats as $name => $stat) {
            $array[$name] = $stat->render();
        }

        $array = array_merge($array, [
            'php' => phpversion(),
            'headers' => getallheaders(),
            /*
            'includes' => array_map(function ($val) {
                return $this->context->normalizePath($val);
            }, get_included_files()),*/
            '$_SERVER' => $_SERVER,
            '$_ENV' => $_ENV,
            '$_GET' => $_GET,
            '$_POST' => $_POST,
            '$_FILES' => $_FILES,
            '$_COOKIE' => $_COOKIE
        ]);

        $globals = [];
        $filter = [
            '_SERVER', '_ENV', '_GET', '_POST',
            '_FILES', '_COOKIE', '_REQUEST', 'GLOBALS'
        ];

        foreach ($GLOBALS as $key => $value) {
            if (in_array($key, $filter)) {
                continue;
            }

            $globals[$key] = $value;
        }

        $array['$GLOBALS'] = $globals;
        $inspector = new Inspector($this->context);

        foreach ($array as $key => $value) {
            $array[$key] = $inspector($value, function (
                Entity $entity
            ) {
                $entity->setOpen(false);
            });
        }

        $output = [];
        $output[] = '<section class="dump environment">';
        $output[] = '<h3>Environment</h3>';
        $output[] = '<samp class="dump environment">';

        $output[] = $this->renderList($array, 'meta');

        $output[] = '</samp>';
        $output[] = '</section>';
        return implode("\n", $output);
    }

    /**
     * Render final stack trace
     */
    protected function renderTrace(
        Trace $trace,
        bool $open = false
    ): string {
        $output = [];
        $output[] = '<section class="stack">';
        $output[] = '<h3>Stack trace</h3>';
        $output[] = '<div><div class="frame">';
        $count = count($trace);
        $lines = [];
        $first = true;

        foreach ($trace as $i => $frame) {
            $line = $sig = [];
            $line[] = '<div class="stack-frame group' . ($first ? ' w-source' : null) . '">';
            $line[] = '<samp class="dump trace" data-open="source">';

            $sig[] = $this->renderLineNumber($count - $i);
            $sig[] = $this->wrapSignature($this->renderStackFrameSignature($frame));
            $sig[] = "\n   ";

            if (null !== ($file = $frame->callingFile)) {
                $sig[] = $this->renderSourceFile((string)$this->context->normalizePath($file));

                if (null !== ($callingLine = $frame->callingLine)) {
                    $sig[] = $this->renderSourceLine($callingLine);
                }
            } else {
                $sig[] = $this->renderSourceFile('internal', 'internal');
            }

            $line[] = implode(' ', $sig);
            $line[] = '</samp>';

            if (null !== ($source = $this->renderFrameSource($frame))) {
                $line[] = $source;
            }

            $line[] = '</div>';

            $lines[] = implode("\n", $line);
            $first = false;
        }

        $output[] = $this->renderBasicList($lines, 'stack');
        $output[] = '</div></div>';
        $output[] = '</section>';

        return implode("\n", $output);
    }

    /**
     * Render stack frame calling code from source file
     */
    protected function renderFrameSource(
        Frame $frame
    ): ?string {
        if ($path = $frame->callingFile) {
            $line = $frame->callingLine;
        } elseif ($path = $frame->file) {
            $line = $frame->line;
        } else {
            return null;
        }

        if ($line === null) {
            return null;
        }

        return (new Highlighter())->extractFromFile($path, $line);
    }

    /**
     * Render final tags
     */
    protected function renderFooter(): string
    {
        $output = [];
        $output[] = '</div>';
        $output[] = '</body>';
        $output[] = '</html>';

        return implode("\n", $output);
    }

    /**
     * Implode buffer and wrap it in JS iframe injector
     *
     * @param array<string> $buffer
     */
    protected function exportBuffer(
        array $buffer,
        bool $final
    ): Packet {
        $html = implode("\n", $buffer);

        if (
            $final &&
            !$this->context->hasDumpedInBuffer()
        ) {
            $output = $html;
        } else {
            $id = uniqid('glitch-dump');
            $output = [];

            $output[] = '<div class="glitch-dump">';
            $output[] = '<style>';
            $output[] = '.glitch-dump > iframe { width: 100%; max-width: 100vw; min-width: 100%; height: 100%; resize: both; }';
            $output[] = 'body > .glitch-dump > iframe { height: 50vh; }';
            $output[] = 'body > .glitch-dump:only-child > iframe { height:100vh; }';
            $output[] = 'body > .glitch-dump:only-child { height:100%; border: none; resize: none; position: absolute; width: 100%; top: 0; left: 0; }';
            $output[] = '</style>';
            $output[] = '<iframe id="' . $id . '" width="100%" height="100%" frameborder="0"></iframe>';
            $output[] = '<script>';
            $output[] = 'var doc = document.getElementById(\'' . $id . '\').contentWindow.document;';
            $output[] = 'doc.open();doc.write(' . json_encode($html) . ');doc.close();';
            $output[] = '</script>';
            $output[] = '</div>';

            $output = implode("\n", $output);
        }

        return new Packet($output, 'text/html');
    }


    /**
     * Render a null scalar
     */
    protected function renderNull(
        ?string $class = null
    ): string {
        return '<span class="null' . ($class !== null ? ' ' . $class : null) . '">null</span>';
    }

    /**
     * Render a boolean scalar
     */
    protected function renderBool(
        bool $value,
        ?string $class = null
    ): string {
        return '<span class="bool' . ($class !== null ? ' ' . $class : null) . '">' . ($value ? 'true' : 'false') . '</span>';
    }

    /**
     * Render a integer scalar
     */
    protected function renderInt(
        int $value,
        ?string $class = null
    ): string {
        return '<span class="int' . ($class !== null ? ' ' . $class : null) . '">' . $value . '</span>';
    }

    /**
     * Render a float scalar
     */
    protected function renderFloat(
        float $value,
        ?string $class = null
    ): string {
        return '<span class="float' . ($class !== null ? ' ' . $class : null) . '">' . $this->normalizeFloat($value) . '</span>';
    }



    /**
     * Render a single identifier string
     */
    protected function renderIdentifierString(
        string $string,
        ?string $class,
        ?int $forceSingleLineMax = null
    ): string {
        return '<span class="string ' . $class . '">' . $this->renderStringLine($string, $forceSingleLineMax) . '</span>';
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
        $count = count($parts);

        $output = [];
        $output[] = '<div class="string m' . ($count > 10 ? ' large' : null) . ' ' . $class . '"><span class="length">' . mb_strlen($string) . '</span>';

        foreach ($parts as $part) {
            $output[] = '<div class="line">' . $this->renderStringLine($part) . '</div>';
        }

        $output[] = '</div>';
        return implode('', $output);
    }

    /**
     * Render a standard single line string
     */
    protected function renderSingleLineString(
        string $string,
        ?string $class = null,
        ?int $forceSingleLineMax = null
    ): string {
        $output = '<span class="string s ' . $class . '"><span class="line">' . $this->renderStringLine($string, $forceSingleLineMax) . '</span>';

        if ($forceSingleLineMax === null) {
            $output .= '<span class="length">' . mb_strlen($string) . '</span>';
        }

        $output .= '</span>';
        return $output;
    }

    /**
     * Render binary string chunk
     */
    protected function renderBinaryStringChunk(
        string $chunk
    ): string {
        return '<i>' . $chunk . '</i>';
    }


    /**
     * Render a detected ascii control character
     */
    protected function wrapControlCharacter(
        string $control
    ): string {
        $class = 'control';

        if ($control === '\\t') {
            $class .= ' tab';
        }

        return '<span class="' . $class . '">' . $control . '</span>';
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
        return '<span class="resource">resource</span>';
    }


    /**
     * Render structure grammar
     */
    protected function renderGrammar(
        string $grammar
    ): string {
        return '<span class="g">' . $this->esc($grammar) . '</span>';
    }

    /**
     * Render structure pointer
     */
    protected function renderPointer(
        string $pointer
    ): string {
        return '<span class="pointer">' . $this->esc($pointer) . '</span>';
    }

    /**
     * Render line number
     */
    protected function renderLineNumber(
        int $number
    ): string {
        return '<span class="number">' . $number . '</span>';
    }

    /**
     * Render file path
     */
    protected function renderSourceFile(
        string $path,
        ?string $class = null
    ): string {
        return '<span class="file ' . $class . '">' . $path . '</span>';
    }

    /**
     * Render source line
     */
    protected function renderSourceLine(
        int $number
    ): string {
        return '<span class="line">' . $number . '</span>';
    }



    /**
     * render a signature block
     */
    protected function wrapSignature(
        string $signature,
        ?string $class = null
    ): string {
        return '<span class="signature source' . ($class ? ' ' . $class : null) . '">' . $signature . '</span>';
    }

    /**
     * render signature namespace part
     */
    protected function renderSignatureNamespace(
        string $namespace
    ): string {
        return '<span class="namespace">' . $this->esc($namespace) . '</span>';
    }

    /**
     * render signature class part
     */
    protected function renderSignatureClass(
        string $class
    ): string {
        return '<span class="class">' . $this->esc($class) . '</span>';
    }

    /**
     * render signature constant part
     */
    protected function renderSignatureConstant(
        string $constant
    ): string {
        return '<span class="constant">' . $this->esc($constant) . '</span>';
    }

    /**
     * Wrap signature function block
     */
    protected function wrapSignatureFunction(
        string $function,
        ?string $class = null
    ): string {
        return '<span class="function' . ($class ? ' ' . $class : null) . '">' . $function . '</span>';
    }

    /**
     * Wrap signature array
     */
    protected function wrapSignatureArray(
        string $array,
        ?string $class = null
    ): string {
        return '<span class="ar' . ($class ? ' ' . $class : null) . '">' . $array . '</span>';
    }

    /**
     * render signature object name
     */
    protected function renderSignatureObject(
        string $object
    ): string {
        return '<span class="class param">' . $this->esc($object) . '</span>';
    }


    /**
     * Wrap entity
     */
    protected function wrapEntity(
        string $entity,
        ?string $class = null
    ): string {
        return '<div class="entity group ' . $class . '">' . $entity . '</div>';
    }


    /**
     * Begin entity block
     */
    protected function wrapEntityHeader(
        string $header,
        string $type,
        string $linkId
    ): string {
        return '<div class="title t-' . $type . '" id="' . $linkId . '">' . $header . '</div>';
    }

    /**
     * Wrap entity name if reference
     */
    protected function wrapReferenceName(
        string $name
    ): string {
        return '<span class="ref">' . $name . '</span>';
    }



    /**
     * Wrap entity name link
     */
    protected function wrapEntityName(
        string $name,
        bool $open,
        string $linkId,
        bool $sensitive = false
    ): string {
        return '<a class="name code' . ($sensitive ? ' sensitive' : '') . '" data-open="body">' . $name . '</a>';
    }

    /**
     * Wrap entity name if reference
     */
    protected function renderEntityNamePart(
        string $name,
        bool $sensitive = false
    ): string {
        return '<i>' . $this->esc($name) . '</i>';
    }

    /**
     * Wrap entity name link reference
     */
    protected function wrapEntityNameReference(
        string $name,
        bool $open,
        string $id,
        bool $sensitive = false
    ): string {
        return '<a class="name code ref' . ($sensitive ? ' sensitive' : '') . '" href="#' . $id . '">' . $name . '</a>';
    }

    /**
     * render entity length tag
     */
    protected function renderEntityLength(
        int $length
    ): string {
        return '<span class="length">' . $length . '</span>';
    }

    /**
     * render entity class name
     */
    protected function renderEntityClassName(
        string $class
    ): string {
        return '<span class="class">' . $this->esc($class) . '</span>';
    }


    /**
     * Wrap buttons
     */
    protected function wrapEntityButtons(
        string $buttons
    ): string {
        return '<span class="buttons">' . $buttons . '</span>';
    }

    /**
     * Render info toggle button
     */
    protected function renderEntityInfoButton(
        bool $isRef,
        bool $open
    ): string {
        if ($isRef) {
            return '<a data-open="body" class="info badge"><i>i</i></a>';
        } else {
            return '<a data-open="t-info" class="info badge"><i>i</i></a>';
        }
    }

    /**
     * Render meta toggle button
     */
    protected function renderEntityMetaButton(
        bool $open
    ): string {
        return '<a data-open="t-meta" class="meta badge"><i>m</i></a>';
    }

    /**
     * Render text toggle button
     */
    protected function renderEntityTextButton(
        bool $open
    ): string {
        return '<a data-open="t-text" class="text primary badge"><i>t</i></a>';
    }

    /**
     * Render text toggle button
     */
    protected function renderEntityDefinitionButton(
        bool $open
    ): string {
        return '<a data-open="t-def" class="def primary badge"><i>d</i></a>';
    }

    /**
     * Render properties toggle button
     */
    protected function renderEntityPropertiesButton(
        bool $open
    ): string {
        return '<a data-open="t-props" class="props primary badge"><i>p</i></a>';
    }

    /**
     * Render values toggle button
     */
    protected function renderEntityValuesButton(
        bool $open
    ): string {
        return '<a data-open="t-values" class="values badge primary"><i>v</i></a>';
    }

    /**
     * Render stack toggle button
     */
    protected function renderEntityStackButton(
        string $type,
        bool $open
    ): string {
        if ($type === 'stack') {
            return '<a data-open="body" class="stack badge"><i>s</i></a>';
        } else {
            return '<a data-open="t-stack" class="stack primary badge"><i>s</i></a>';
        }
    }


    /**
     * Render object id tag
     */
    protected function renderEntityOid(
        int $objectId,
        bool $isRef,
        string $id
    ): string {
        if ($isRef) {
            return '<a href="#' . $id . '" class="ref oid">' . $this->esc((string)$objectId) . '</a>';
        } else {
            return '<span class="oid">' . $this->esc((string)$objectId) . '</span>';
        }
    }

    /**
     * Wrap entity body
     */
    protected function wrapEntityBody(
        string $body,
        bool $open,
        string $linkId
    ): string {
        return '<div class="inner body">' . $body . '</div>';
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
        if (
            $class &&
            $class !== $type
        ) {
            $class .= ' ' . $type;
        } else {
            $class = $type;
        }

        return '<div class="inner t-' . $type . '"><div class="' . $class . '">' . "\n" .
            $block . "\n" .
        '</div></div>';
    }

    /**
     * Wrap entity footer
     */
    protected function wrapEntityFooter(
        string $footer
    ): string {
        return '<div class="footer">' . $footer . '</div>';
    }

    /**
     * Wrap stack frame
     */
    protected function wrapStackFrame(
        string $frame
    ): string {
        return '<div class="stack-frame"><samp class="dump trace">' . $frame . '</samp></div>';
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
        $output = [];
        $output[] = '<ul class="' . $class . '">';

        foreach ($lines as $line) {
            $output[] = '<li>' . "\n" . $line . "\n" . '</li>';
        }

        $output[] = '</ul>';

        return "\n" . implode("\n", $output) . "\n";
    }


    /**
     * Escape a value for HTML
     */
    protected function esc(
        ?string $value
    ): string {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
