<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper;

use Glitch\Stack\Trace;

use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Cloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Caster;

class Symfony
{
    /**
     * Register self with Symfony
     */
    public function __construct()
    {
        VarDumper::setHandler([$this, 'dump']);
    }

    /**
     * Dump a single var
     */
    public function dumpOne($var): void
    {
        if ('cli' === PHP_SAPI) {
            $dumper = new CliDumper();
        } else {
            $dumper = new HtmlDumper();
            $dumper->setStyles([
                'default' => 'background-color:#fff; color:#bbb; line-height:1.2; font-weight:normal; font:12px Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:100000',
                'num' => 'color:#a814e3',
                'const' => 'color:#b50acc',
                'str' => 'color:#cc2123',
                'cchr' => 'color:#222',
                'note' => 'color:#0cb300',
                'ref' => 'color:#a0a0a0',
                'public' => 'color:#795da3',
                'protected' => 'color:#795da3',
                'private' => 'color:#795da3',
                'meta' => 'color:#0cb300',
                'key' => 'color:#df5000',
                'index' => 'color:#a71d5d',
            ]);

            $dumper->setDisplayOptions([
                'maxDepth' => 3
            ]);
        }


        $cloner = new Cloner\VarCloner();
        $dumper->dump($cloner->cloneVar($var));
    }

    /**
     * Dump a list of vars
     */
    public function dump(...$vars): void
    {
        $trace = Trace::create();

        foreach ($trace as $frame) {
            if (substr($frame->getFunctionName(), 0, 4) != 'dump'
            && false === strpos($frame->getCallingFile() ?? '', 'var-dumper')) {
                break;
            }
        }


        $attrs = [
            'time' => self::formatMicrotime(microtime(true) - \Glitch::getContext()->getStartTime()),
            'memory' => self::formatFilesize(memory_get_usage()),
            'location' => \Glitch::normalizePath($frame->getCallingFile()).' : '.$frame->getCallingLine()
        ];

        if ('cli' === PHP_SAPI) {
            echo implode(' | ', $attrs)."\n\n";
        } else {
            echo '<pre class="sf-dump">'.implode(' | ', $attrs).'</pre>';
        }

        foreach ($vars as $var) {
            $this->dumpOne($var);
        }
    }

    /**
     * Dump vars and die
     */
    public function dumpDie(...$vars): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(500);
        $this->dump(...$vars);
        die(1);
    }



    /**
     * TODO: move these to a shared location
     */
    private static function formatFilesize($bytes)
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    private static function formatMicrotime($time)
    {
        return number_format($time * 1000, 2).' ms';
    }
}
