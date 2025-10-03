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
use DecodeLabs\Glitch\Dump;
use DecodeLabs\Glitch\Packet;
use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\Renderer\Html\ZestManifest;
use DecodeLabs\Glitch\RendererTrait;
use DecodeLabs\Glitch\Stat;
use DecodeLabs\Nuance\Entity\NativeObject\Throwable as ThrowableEntity;
use DecodeLabs\Nuance\Entity\NativeString;
use DecodeLabs\Nuance\Entity\Structured as StructureEntity;
use DecodeLabs\Nuance\Renderer\Html as NuanceHtmlRenderer;
use DecodeLabs\Nuance\Structure\ClassList;
use DecodeLabs\Remnant\Frame;
use DecodeLabs\Remnant\Location;
use DecodeLabs\Remnant\Trace;
use Throwable;

class Html extends NuanceHtmlRenderer implements Renderer
{
    use RendererTrait;

    public const bool RenderInProduction = false;
    public const int Spaces = 0;
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
     * @return $this
     */
    public function setCustomCssFile(
        ?string $path
    ): static {
        $this->customCssFile = $path;
        return $this;
    }

    public function getCustomCssFile(): ?string
    {
        return $this->customCssFile;
    }


    public function renderDumpView(
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
        $output[] = $this->renderTraceSection($dump->getTrace());
        $output[] = '</div>';

        $output[] = '</div>';

        $output[] = $this->renderFooter();
        return $this->exportDumpBuffer($output, $final);
    }


    public function renderExceptionView(
        Throwable $exception,
        Dump $dataDump
    ): Packet {
        /** @var ThrowableEntity $entity */
        $entity = $this->inspector->inspect($exception);
        $output = [];

        $class = 'exception';
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
            $output[] = $this->renderTraceSection($dataDump->getTrace());
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


    protected function renderExceptionMessage(
        Throwable $exception
    ): string {
        $message = $exception->getMessage();
        $code = $exception->getCode();
        $file = $exception->getFile();
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
        $output[] = '<div class="message">' . $this->renderMultiLineString(
            new NativeString($message)
        ) . '</div>';

        if ($file) {
            $output[] = '<span class="attr file"><span class="label">File</span> ' . $this->renderStackFrameLocation(new Location($file, $line)) . '</span>';
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

    protected function renderProductionExceptionMessage(
        Throwable $exception
    ): string {
        return
            '<section class="production message">There was a problem serving your request - please try again later</section>' . "\n" .
            '<section class="production exception">' . (string)$exception . '</section>';
    }

    protected function renderDumpEntities(
        Dump $dump
    ): string {
        $output = [];
        $output[] = '<section class="dump entity">';
        $output[] = '<h3>Dump</h3>';

        foreach ($dump->getEntities() as $value) {
            $output[] = '<samp class="dump">';
            $output[] = $this->renderValue($value);
            $output[] = '</samp>';
        }

        $output[] = '</section>';

        return implode("\n", $output);
    }

    protected function renderExceptionEntity(
        ThrowableEntity $entity
    ): string {
        $entity->sections->disable('text');
        $entity->sections->disable('stack');

        $output = [];
        $output[] = '<section class="dump object">';
        $output[] = '<h3>Exception object</h3>';
        $output[] = '<samp class="dump">';
        $output[] = $this->renderObject($entity);
        $output[] = '</samp>';
        $output[] = '</section>';

        return implode("\n", $output);
    }

    /**
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

        foreach ($array as $key => $value) {
            $array[$key] = $entity = $this->inspector->inspect($value);

            if ($entity instanceof StructureEntity) {
                $entity->open = false;
            }
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

    protected function renderTraceSection(
        Trace $trace,
    ): string {
        $output = [];
        $output[] = '<section class="stack">';
        $output[] = '<h3>Stack trace</h3>';

        $output[] = $this->el(
            tag: 'div',
            content: $this->el(
                tag: 'label',
                content: implode('', [
                    'Filter: ',
                    $this->el(
                        tag: 'input',
                        attributes: [
                            'type' => 'checkbox',
                            'id' => 'filter',
                            'checked' => true,
                        ]
                    )
                ])
            ),
            classes: ClassList::of('filter')
        );


        $output[] = '<div><div class="frame">';
        $output[] = $this->renderStackTrace($trace);
        $output[] = '</div></div>';
        $output[] = '</section>';

        return implode("\n", $output);
    }



    protected function renderFooter(): string
    {
        $output = [];
        $output[] = '</div>';
        $output[] = '</body>';
        $output[] = '</html>';

        return implode("\n", $output);
    }

    /**
     * @param array<string> $buffer
     */
    protected function exportBuffer(
        array $buffer,
        bool $final
    ): Packet {
        $html = implode("\n", $buffer);

        if (
            $final &&
            !$this->glitch->hasDumpedInBuffer()
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




    public function renderStackFrameSource(
        Frame $frame
    ): ?string {
        if (
            null === ($location = $frame->callSite ?? $frame->location) ||
            $location->line === null
        ) {
            return null;
        }

        return (new Highlighter())->extractFromFile($location->file, $location->line);
    }
}
