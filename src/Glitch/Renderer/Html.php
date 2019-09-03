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
    const SPACES = 0;

    use Base;

    /**
     * Render scripts and styles
     */
    protected function renderHeader(): string
    {
        $output = [];
        $output[] = '<!doctype html>';
        $output[] = '<html lang="en">';
        $output[] = '<head>';

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
        $output[] = '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';


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

        return implode("\n", $output);
    }


    /**
     * Build a stat list header bar
     */
    protected function renderStats(array $stats): string
    {
        $output = [];
        $output[] = '<header class="stats container-fluid">';

        foreach ($stats as $key => $stat) {
            $output[] = '<span class="stat stat-'.$key.' badge badge-'.$stat->getClass().'" title="'.$this->esc($stat->getName()).'">'.$stat->render('html').'</span>';
        }

        $output[] = '</header>';
        return implode("\n", $output);
    }

    /**
     * Render dump entity list
     */
    protected function renderDumpEntities(Dump $dump): string
    {
        $output = [];
        $output[] = '<div class="container-fluid">';

        foreach ($dump->getEntities() as $value) {
            $output[] = '<samp class="dump">';

            if ($value instanceof Entity) {
                $output[] = $this->renderEntity($value);
            } else {
                $output[] = $this->renderScalar($value);
            }

            $output[] = '</samp>';
        }

        $output[] = '</div>';
        return implode("\n", $output);
    }

    /**
     * Render final stack trace
     */
    protected function renderTrace(Trace $trace): string
    {
        $output = [];
        $output[] = '<div class="container-fluid">';
        $output[] = '<samp class="dump trace">';

        $output[] = $this->renderEntity(
            (new Entity('stack'))
                ->setName('stack')
                ->setStackTrace($trace)
                ->setOpen(false)
                ->setLength($trace->count())
        );

        $output[] = '</samp>';
        $output[] = '</div>';
        return implode("\n", $output);
    }

    /**
     * Render final tags
     */
    protected function renderFooter(): string
    {
        $output = [];
        $output[] = '</body>';
        $output[] = '</html>';

        return implode("\n", $output);
    }

    /**
     * Implode buffer and wrap it in JS iframe injector
     */
    protected function exportBuffer(array $buffer): string
    {
        $html = implode("\n", $buffer);
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
     * render a single identifier string
     */
    protected function renderIdentifierString(string $string, string $class, int $forceSingleLineMax=null): string
    {
        return '<span class="string '.$class.'">'.$this->renderStringLine($string, $forceSingleLineMax).'</span>';
    }

    /**
     * render a standard multi line string
     */
    protected function renderMultiLineString(string $string): string
    {
        $string = str_replace("\r", '', $string);
        $parts = explode("\n", $string);
        $count = count($parts);

        $output = [];
        $output[] = '<div class="string m'.($count > 10 ? ' large' : null).'"><span class="length">'.mb_strlen($string).'</span>';

        foreach ($parts as $part) {
            $output[] = '<div class="line">'.$this->renderStringLine($part).'</div>';
        }

        $output[] = '</div>';
        return implode('', $output);
    }

    /**
     * render a standard single line string
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
     * render binary string chunk
     */
    protected function renderBinaryStringChunk(string $chunk): string
    {
        return '<i>'.$chunk.'</i>';
    }


    /**
     * render a detected ascii control character
     */
    protected function wrapControlCharacter(string $control): string
    {
        return '<span class="control">'.$control.'</span>';
    }

    /**
     * render structure grammer
     */
    protected function renderGrammar(string $grammar): string
    {
        return '<span class="g">'.$this->esc($grammar).'</span>';
    }

    /**
     * render structure pointer
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
    protected function wrapEntityBodyBlock(string $block, string $type, bool $open, string $linkId): string
    {
        return '<div id="'.$type.'-'.$linkId.'" class="collapse'.($open ? ' show': null).' inner type-'.$type.'"><div class="'.$type.'">'."\n".
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

        return implode("\n", $output);
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
