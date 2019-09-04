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
use DecodeLabs\Glitch\Dumper\Inspector;

class Html implements Renderer
{
    const RENDER_IN_PRODUCTION = false;
    const SPACES = 0;
    const RENDER_CLOSED = true;

    const RENDER_SECTIONS = [
        'info' => true,
        'meta' => true,
        'text' => true,
        'properties' => true,
        'values' => true,
        'stack' => true
    ];

    const RENDER_STACK = true;

    const DEV = true;

    const HTTP_STATUSES = [
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

    use Base;



    /**
     * Inspect handled exception
     */
    public function renderException(\Throwable $exception, Entity $entity, Dump $dataDump): string
    {
        $output = [];
        $output[] = $this->renderHeader('exception');

        if (!$this->shouldRender()) {
            $output[] = $this->renderProductionExceptionMessage($exception);
        } else {
            $output[] = '<header class="title">';
            $output[] = '<h1>Glitch</h1>';
            $output[] = '</header>';
            //$output[] = $this->renderStats($dataDump->getStats());

            $output[] = '<section class="exception"><div class="exception">';
            $http = $exception instanceof \EGlitch ? $exception->getHttpCode() : null;
            $output[] = $this->renderExceptionMessage($exception->getMessage(), $exception->getCode(), $http);
            $output[] = '</div></section>';

            $output[] = '<section class="cols">';

            $output[] = '<div class="left"><div class="frame">';
            $output[] = $this->renderTrace($dataDump->getTrace(), true);
            $output[] = '</div></div>';

            $output[] = '<div class="right"><div class="frame">';
            $output[] = $this->renderExceptionEntity($entity);
            $output[] = $this->renderEnvironment($dataDump->getStats());
            $output[] = '</div></div>';



            $output[] = '</section>';
        }

        $output[] = $this->renderFooter();
        return $this->exportExceptionBuffer($output);
    }



    /**
     * Render scripts and styles
     */
    protected function renderHeader(string $class): string
    {
        $output = [];
        $output[] = '<!doctype html>';
        $output[] = '<html lang="en" class="'.$class.'">';
        $output[] = '<head>';

        $vendor = $this->context->getVendorPath();
        $isDev = is_link($vendor.'/decodelabs/glitch');

        $css = $scss = [];
        $css = ['glitch' => $vendor.'/decodelabs/glitch/src/Glitch/Renderer/assets/glitch.css'];
        $scss = ['glitch' => $vendor.'/decodelabs/glitch/src/Glitch/Renderer/assets/glitch.scss'];

        $js = [
            'jQuery' => $vendor.'/bower-asset/jquery/dist/jquery.min.js',
            'bootstrap' => $vendor.'/bower-asset/bootstrap/dist/js/bootstrap.bundle.min.js',
            'glitch' => __DIR__.'/assets/glitch.js'
        ];


        // Meta
        $output[] = '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';


        // Scss
        if (static::DEV && $isDev) {
            foreach ($scss as $name => $path) {
                if (!file_exists($path)) {
                    continue;
                }

                $cssPath = $css[$name];
                $build = false;

                if (file_exists($cssPath)) {
                    $cssTime = filemtime($cssPath);
                    $scssTime = filemtime($path);

                    if ($scssTime > $cssTime) {
                        $build = true;
                    }
                } else {
                    $build = true;
                }

                if ($build) {
                    exec('cd '.$vendor.'; sassc --style=expanded '.$path.' '.$cssPath.' 2>&1', $execOut);

                    if (!empty($execOut)) {
                        die('<pre>'.print_r($execOut, true));
                    }
                }
            }
        }

        // Css
        foreach ($css as $name => $path) {
            $output[] = '<style id="style-'.$name.'">';
            $output[] = file_get_contents($path);
            $output[] = '</style>';
        }

        // Js
        foreach ($js as $name => $path) {
            $output[] = '<script id="script-'.$name.'">';
            $output[] = file_get_contents($path);
            $output[] = '</script>';
        }


        $output[] = '</head>';
        $output[] = '<body>';
        $output[] = '<div class="container-fluid">';

        return implode("\n", $output);
    }


    /**
     * Build a stat list header bar
     */
    protected function renderStats(array $stats): string
    {
        $output = [];
        $output[] = '<header class="stats"><div class="stats">';

        foreach ($stats as $key => $stat) {
            if (null === ($statString = $stat->render('html'))) {
                continue;
            }

            $output[] = '<span class="stat stat-'.$key.' badge badge-'.$stat->getClass().'" title="'.$this->esc($stat->getName()).'">'.$statString.'</span>';
        }

        $output[] = '</div></header>';
        return implode("\n", $output);
    }

    /**
     * Render exception message
     */
    protected function renderExceptionMessage(string $message, ?int $code, ?int $httpCode): string
    {
        $output = [];
        $output[] = '<samp class="dump exception">';
        $output[] = '<div class="message">'.$this->renderMultiLineString($message).'</div>';

        if ($code) {
            $output[] = '<span class="attr code"><span class="label">Code</span> '.$code.'</span>';
        }

        if ($httpCode) {
            if (isset(static::HTTP_STATUSES[$httpCode])) {
                $httpCode .= ' '.static::HTTP_STATUSES[$httpCode];
            }

            $output[] = '<div class="attr http"><span class="label">HTTP</span> '.$httpCode.'</div>';
        }

        $output[] = '</samp>';
        return implode("\n", $output);
    }

    /**
     * Render dump entity list
     */
    protected function renderDumpEntities(Dump $dump): string
    {
        $output = [];

        foreach ($dump->getEntities() as $value) {
            $output[] = '<samp class="dump">';

            if ($value instanceof Entity) {
                $output[] = $this->renderEntity($value);
            } else {
                $output[] = $this->renderScalar($value);
            }

            $output[] = '</samp>';
        }

        return implode("\n", $output);
    }

    /**
     * Render exception entity
     */
    protected function renderExceptionEntity(Entity $entity): string
    {
        $output = [];
        $output[] = '<samp class="dump">';
        $output[] = '<h3>Exception object</h3>';
        $output[] = $this->renderEntity($entity, 0, [
            'info' => true,
            'meta' => false,
            'text' => false,
            'properties' => true,
            'values' => true,
            'stack' => false
        ]);
        $output[] = '</samp>';

        return implode("\n", $output);
    }

    /**
     * Render environment vars
     */
    protected function renderEnvironment(array $stats): string
    {
        $array = [];

        foreach ($stats as $name => $stat) {
            $array[$name] = $stat->render('text');
        }

        $array = array_merge($array, [
            'php' => phpversion(),
            'headers' => getallheaders(),
            'includes' => array_map(function ($val) {
                return $this->context->normalizePath($val);
            }, get_included_files()),
            '$_SERVER' => $_SERVER,
            '$_GET' => $_GET,
            '$_POST' => $_POST,
            '$_FILES' => $_FILES,
            '$_COOKIE' => $_COOKIE
        ]);

        $inspector = new Inspector($this->context);

        foreach ($array as $key => $value) {
            $array[$key] = $inspector($value, function ($entity) {
                $entity->setOpen(false);
            });
        }

        $output = [];
        $output[] = '<samp class="dump environment">';
        $output[] = '<h3>Environment</h3>';

        $output[] = $this->renderList($array, 'meta');

        $output[] = '</samp>';
        return implode("\n", $output);
    }

    /**
     * Render final stack trace
     */
    protected function renderTrace(Trace $trace, bool $open=false): string
    {
        $output = [];
        $output[] = '<samp class="dump trace">';

        if ($open) {
            $output[] = '<h3>Stack trace</h3>';
        }

        $output[] = $this->renderEntity(
            (new Entity('stack'))
                ->setName('stack')
                ->setStackTrace($trace)
                ->setOpen($open)
                ->setLength($trace->count())
        );

        $output[] = '</samp>';
        return implode("\n", $output);
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
     */
    protected function exportDumpBuffer(array $buffer): string
    {
        $html = implode("\n", $buffer);
        $id = uniqid('glitch-dump');

        $output = [];
        $output[] = '<iframe id="'.$id.'" frameborder="0" class="glitch-dump"></iframe>';
        $output[] = '<style>';
        $output[] = 'body { padding: 0; margin: 0; }';
        $output[] = '.glitch-dump { width: 100%; height: 30rem; border: 1px solid #EEE; }';
        $output[] = '.glitch-dump:only-of-type { height:100%; border: none; }';
        $output[] = '</style>';
        $output[] = '<script>';
        $output[] = 'var doc = document.getElementById(\''.$id.'\').contentWindow.document;';
        $output[] = 'doc.open();doc.write('.json_encode($html).');doc.close();';
        $output[] = '</script>';

        return implode("\n", $output);
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
        return '<span class="float'.($class !== null ? ' '.$class : null).'">'.$this->normalizeFloat($value).'</span>';
    }



    /**
     * Render a single identifier string
     */
    protected function renderIdentifierString(string $string, string $class, int $forceSingleLineMax=null): string
    {
        return '<span class="string '.$class.'">'.$this->renderStringLine($string, $forceSingleLineMax).'</span>';
    }

    /**
     * Render a standard multi line string
     */
    protected function renderMultiLineString(string $string, bool $asException=false): string
    {
        $string = str_replace("\r", '', $string);
        $parts = explode("\n", $string);
        $count = count($parts);

        $output = [];
        $output[] = '<div class="string m'.($count > 10 ? ' large' : null).($asException ? ' exception' : null).'"><span class="length">'.mb_strlen($string).'</span>';

        foreach ($parts as $part) {
            $output[] = '<div class="line">'.$this->renderStringLine($part).'</div>';
        }

        $output[] = '</div>';
        return implode('', $output);
    }

    /**
     * Render a standard single line string
     */
    protected function renderSingleLineString(string $string, int $forceSingleLineMax=null): string
    {
        $output = '<span class="string s"><span class="line">'.$this->renderStringLine($string, $forceSingleLineMax).'</span>';

        if ($forceSingleLineMax === null) {
            $output .= '<span class="length">'.mb_strlen($string).'</span>';
        }

        $output .= '</span>';

        return $output;
    }

    /**
     * Render binary string chunk
     */
    protected function renderBinaryStringChunk(string $chunk): string
    {
        return '<i>'.$chunk.'</i>';
    }


    /**
     * Render a detected ascii control character
     */
    protected function wrapControlCharacter(string $control): string
    {
        return '<span class="control">'.$control.'</span>';
    }

    /**
     * Render structure grammer
     */
    protected function renderGrammar(string $grammar): string
    {
        return '<span class="g">'.$this->esc($grammar).'</span>';
    }

    /**
     * Render structure pointer
     */
    protected function renderPointer(string $pointer): string
    {
        return '<span class="pointer">'.$this->esc($pointer).'</span>';
    }

    /**
     * Render line number
     */
    protected function renderLineNumber(int $number): string
    {
        return '<span class="number">'.$number.'</span>';
    }

    /**
     * Render file path
     */
    protected function renderSourceFile(string $path): string
    {
        return '<span class="file">'.$path.'</span>';
    }

    /**
     * Render source line
     */
    protected function renderSourceLine(int $number): string
    {
        return '<span class="line">'.$number.'</span>';
    }



    /**
     * render a signature block
     */
    protected function wrapSignature(string $signature, ?string $class=null): string
    {
        return '<span class="signature'.($class ? ' '.$class : null).'">'.$signature.'</span>';
    }

    /**
     * render signature namespace part
     */
    protected function renderSignatureNamespace(string $namespace): string
    {
        return '<i class="ns">'.$this->esc($namespace).'</i>';
    }

    /**
     * render signature class part
     */
    protected function renderSignatureClass(string $class): string
    {
        return '<i class="cl">'.$this->esc($class).'</i>';
    }

    /**
     * render signature call type part (:: or ->)
     */
    protected function renderSignatureCallType(string $type): string
    {
        return '<i class="ty">'.$this->esc($type).'</i>';
    }

    /**
     * render signature constant part
     */
    protected function renderSignatureConstant(string $constant): string
    {
        return '<i class="co">'.$this->esc($constant).'</i>';
    }

    /**
     * Wrap signature function block
     */
    protected function wrapSignatureFunction(string $function, ?string $class=null): string
    {
        return '<i class="fn'.($class ? ' '.$class : null).'">'.$function.'</i>';
    }

    /**
     * render signature bracket string
     */
    protected function renderSignatureBracket(string $bracket): string
    {
        return '<i class="br">'.$this->esc($bracket).'</i>';
    }

    /**
     * render signature arg comma
     */
    protected function renderSignatureComma(): string
    {
        return '<i class="cm">,</i>';
    }

    /**
     * Wrap signature array
     */
    protected function wrapSignatureArray(string $array, ?string $class=null): string
    {
        return '<span class="ar'.($class ? ' '.$class : null).'">'.$array.'</span>';
    }

    /**
     * render signature object name
     */
    protected function renderSignatureObject(string $object): string
    {
        return '<i class="ob">'.$this->esc($object).'</i>';
    }




    /**
     * Begin entity block
     */
    protected function wrapEntityHeader(string $header, string $type, string $linkId): string
    {
        return '<div class="entity title type-'.$type.'" id="'.$linkId.'">'.$header.'</div>';
    }

    /**
     * Wrap entity name if reference
     */
    protected function wrapReferenceName(string $name): string
    {
        return '<span class="ref">'.$name.'</span>';
    }



    /**
     * Wrap entity name link
     */
    protected function wrapEntityName(string $name, bool $open, string $linkId): string
    {
        return '<a class="name code'.($open ? null : ' collapsed').'" data-target="#body-'.$linkId.'">'.$name.'</a>';
    }

    /**
     * Wrap entity name if reference
     */
    protected function renderEntityNamePart(string $name): string
    {
        return '<i>'.$this->esc($name).'</i>';
    }

    /**
     * Wrap entity name link reference
     */
    protected function wrapEntityNameReference(string $name, bool $open, string $id): string
    {
        return '<a class="name code'.($open ? null : ' collapsed').' ref" href="#'.$id.'">'.$name.'</a>';
    }

    /**
     * render entity length tag
     */
    protected function renderEntityLength(int $length): string
    {
        return '<span class="length">'.$length.'</span>';
    }

    /**
     * render entity class name
     */
    protected function renderEntityClassName(string $class): string
    {
        return '<span class="class">'.$this->esc($class).'</span>';
    }



    /**
     * render info toggle button
     */
    protected function renderEntityInfoButton(string $linkId): string
    {
        return '<a data-target="#info-'.$linkId.'" class="info body badge badge-info collapsed"><i>i</i></a>';
    }

    /**
     * render meta toggle button
     */
    protected function renderEntityMetaButton(string $linkId): string
    {
        return '<a data-target="#meta-'.$linkId.'" class="meta body badge badge-secondary collapsed"><i>m</i></a>';
    }

    /**
     * render text toggle button
     */
    protected function renderEntityTextButton(string $linkId): string
    {
        return '<a data-target="#text-'.$linkId.'" class="text body primary badge badge-danger"><i>t</i></a>';
    }

    /**
     * render properties toggle button
     */
    protected function renderEntityPropertiesButton(string $linkId): string
    {
        return '<a data-target="#properties-'.$linkId.'" class="properties body primary badge badge-primary"><i>p</i></a>';
    }

    /**
     * render values toggle button
     */
    protected function renderEntityValuesButton(string $linkId): string
    {
        return '<a data-target="#values-'.$linkId.'" class="values body badge primary badge-warning"><i>v</i></a>';
    }

    /**
     * render stack toggle button
     */
    protected function renderEntityStackButton(string $type, bool $open, string $linkId): string
    {
        if ($type === 'stack') {
            return '<a data-target="#body-'.$linkId.'" class="stack badge badge-dark'.($open ? null : ' collapsed').'"><i>s</i></a>';
        } else {
            return '<a data-target="#stack-'.$linkId.'" class="stack body primary badge badge-dark"><i>s</i></a>';
        }
    }


    /**
     * render object id tag
     */
    protected function renderEntityOid(int $objectId, bool $isRef, string $id): string
    {
        if ($isRef) {
            return '<a href="#'.$id.'" class="ref oid">'.$this->esc((string)$objectId).'</a>';
        } else {
            return '<span class="oid">'.$this->esc((string)$objectId).'</span>';
        }
    }

    /**
     * Wrap entity body
     */
    protected function wrapEntityBody(string $body, bool $open, string $linkId): string
    {
        return '<div id="body-'.$linkId.'" class="collapse'.($open ? ' show' : null).' inner body">'.$body.'</div>';
    }

    /**
     * Wrap entity body block
     */
    protected function wrapEntityBodyBlock(string $block, string $type, bool $open, string $linkId, ?string $class=null): string
    {
        if ($class && $class !== $type) {
            $class = $class.' '.$type;
        } else {
            $class = $type;
        }

        return '<div id="'.$type.'-'.$linkId.'" class="collapse'.($open ? ' show': null).' inner type-'.$type.'"><div class="'.$class.'">'."\n".
            $block."\n".
        '</div></div>';
    }

    /**
     * Wrap entity footer
     */
    protected function wrapEntityFooter(string $footer): string
    {
        return '<div class="entity footer">'.$footer.'</div>';
    }


    /**
     * Render basic list
     */
    protected function renderBasicList(array $lines, ?string $class=null): string
    {
        $output = [];
        $output[] = '<ul class="'.$class.'">';

        foreach ($lines as $line) {
            $output[] = '<li>'."\n".$line."\n".'</li>';
        }

        $output[] = '</ul>';

        return "\n".implode("\n", $output)."\n";
    }


    /**
     * Escape a value for HTML
     */
    protected function esc(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
