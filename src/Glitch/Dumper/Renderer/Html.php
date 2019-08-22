<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper\Renderer;

use Glitch\Context;
use Glitch\Dumper\IRenderer;
use Glitch\Dumper\Dump;
use Glitch\Dumper\Entity;

class Html implements IRenderer
{
    const SPACES = 2;

    protected $headerRendered = false;
    protected $fullRender = true;

    protected $output = [];
    protected $context;


    /**
     * Construct with Context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;

        if (headers_sent()) {
            $this->fullRender = false;
        }
    }


    /**
     * Convert Dump object to HTML string
     */
    public function render(Dump $dump, bool $isFinal=false): string
    {
        $this->output = [];
        $space = str_repeat(' ', self::SPACES);

        // Header
        if (!$this->headerRendered) {
            $this->renderHeader();
        }


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

        $this->output[] = '</div>';


        // Footer
        if ($isFinal && $this->fullRender) {
            $this->output[] = '</body>';
            $this->output[] = '</html>';
        }

        $html = implode("\n", $this->output);
        $this->output = [];


        // Wrap in iframe
        $id = uniqid('glitch-dump');

        $output = [];
        $output[] = '<iframe id="'.$id.'" frameborder="0" class="glitch-dump"></iframe>';
        $output[] = '<style>';
        $output[] = '.glitch-dump { width: 100%; height: calc(100% - 8px); border: none; }';
        $output[] = '</style>';
        $output[] = '<script>';
        $output[] = 'var doc = document.getElementById(\''.$id.'\').contentWindow.document';
        $output[] = 'doc.open();doc.write('.json_encode($html).');doc.close();';
        $output[] = '</script>';

        return implode("\n", $output);
    }


    /**
     * Render scripts and styles
     */
    protected function renderHeader(): void
    {
        if (!$sent = headers_sent()) {
            $this->output[] = '<!doctype html>';
            $this->output[] = '<html lang="en">';
            $this->output[] = '<head>';
        }

        $vendor = $this->context->getVendorPath();

        $css = [
            //'bootstrap-reboot' => $vendor.'/components/bootstrap/css/bootstrap-reboot.min.css',
            'bootstrap' => $vendor.'/components/bootstrap/css/bootstrap.min.css',
            'glitch' => __DIR__.'/assets/dump.css'
        ];

        $js = [
            'jQuery' => $vendor.'/components/jquery/jquery.slim.min.js',
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


        if (!$sent) {
            $output[] = '</head>';
            $output[] = '<body>';
        }

        $this->headerRendered = true;
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
    protected function renderString(string $string, ?string $class=null): string
    {
        $isMultiLine = false !== strpos($string, "\n");

        if ($class !== null) {
            return '<span class="string '.$class.'">'.$this->esc($string).'</span>';
        } elseif ($isMultiLine) {
            $string = str_replace("\r", '', $string);
            $parts = explode("\n", $string);

            $output = [];
            $output[] = '<div class="string multi-line" title="'.mb_strlen($string).' characters">';

            foreach ($parts as $part) {
                $output[] = '<div class="line">'.$this->esc($part).'</div>';
            }

            $output[] = '</div>';
            return implode('', $output);
        } else {
            return '<span class="string" title="Length: '.mb_strlen($string).' characters">'.$this->esc($string).'</span>';
        }
    }



    /**
     * Render an individual entity
     */
    protected function renderEntity(Entity $entity): void
    {
        $id = $linkId = $entity->getId();
        $name = $this->esc($entity->getName() ?? $entity->getType());
        $showInfo = true;
        $isRef = false;

        switch ($entity->getType()) {
            case 'arrayReference':
                $name = 'array';

                // no break
            case 'objectReference':
                $linkId = 'ref-'.$id;
                $name = '<span class="ref">'.$name.'</span>';
                $isRef = true;
                break;

            case 'resource':
                $showInfo = false;
                break;
        }

        $this->output[] = '<div class="entity title" id="'.$linkId.'">';

        // Name
        if ($isRef) {
            $this->output[] = '<a class="name code ref" href="#'.$id.'">'.$name.'</a>';
        } else {
            $this->output[] = '<a class="name code" data-toggle="collapse" href="#body-'.$linkId.'">'.$name.'</a>';
        }

        // Length
        if (null !== ($length = $entity->getLength())) {
            $this->output[] = '<span class="length">'.$length.'</span>';
        }

        // Info
        if ($showInfo) {
            $this->output[] = '<a href="#info-'.$linkId.'" data-toggle="collapse" class="info badge badge-info">i</a>';
        }

        // Meta
        if ($showMeta = (bool)$entity->getAllMeta()) {
            $this->output[] = '<a href="#meta-'.$linkId.'" data-toggle="collapse" class="meta badge badge-danger">m</a>';
        }

        // Bracket
        $this->output[] = '<span class="g">{</span>';

        // Object id
        if (null !== ($objectId = $entity->getObjectId())) {
            if ($isRef) {
                $this->output[] = '<a href="#'.$id.'" class="oid">'.$this->esc((string)$objectId).'</a>';
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

        // Text
        if ($entity->getText() !== null) {
            $this->renderTextBlock($entity);
        }

        // Properties
        if ((bool)$entity->getProperties()) {
            $this->renderPropertiesBlock($entity);
        }

        // Values
        if ((bool)$entity->getValues()) {
            $this->renderValuesBlock($entity);
        }

        // Footer
        $this->output[] = '<div class="entity footer">';
        $this->output[] = '<span class="g">}</span>';
        $this->output[] = '</div>';
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
                $linkId = 'ref-'.$id;
                break;
        }

        $this->output[] = '<div id="info-'.$linkId.'" class="collapse inner"><div class="info">';

        $type = $entity->getType();
        $info = [];

        // Type
        switch ($type) {
            case 'object':
            case 'array':
                break;

            default:
                $info['type'] = $type;
                break;
        }

        // Class
        if ($type == 'object') {
            $info['class'] = $entity->getClass();
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
        if ($hash = $entity->getHash()) {
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

        $open = $entity->getOpen();
        $this->output[] = '<div id="text-'.$id.'" class="collapse'.($open ? ' show' : null).' inner"><div class="text '.$type.'">';

        if ($type === 'binary') {
            $chunks = trim(chunk_split($entity->getText(), 2, "\n"));
            $this->output[] = '<i>'.str_replace("\n", '</i><i>', $chunks).'</i>';
        } else {
            $this->output[] = $this->esc($entity->getText());
        }

        $this->output[] = '</div></div>';
    }

    /**
     * Render entity properties block
     */
    protected function renderPropertiesBlock(Entity $entity): void
    {
        $id = $entity->getId();
        $open = $entity->getOpen();
        $this->output[] = '<div id="body-'.$id.'" class="collapse'.($open ? ' show' : null).' inner"><div class="properties">';
        $this->renderList($entity->getProperties(), 'properties');
        $this->output[] = '</div></div>';
    }

    /**
     * Render entity values block
     */
    protected function renderValuesBlock(Entity $entity): void
    {
        $id = $entity->getId();
        $open = $entity->getOpen();
        $this->output[] = '<div id="body-'.$id.'" class="collapse'.($open ? ' show' : null).' inner"><div class="values">';
        $this->renderList($entity->getValues(), 'values');
        $this->output[] = '</div></div>';
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
