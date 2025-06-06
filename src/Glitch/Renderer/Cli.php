<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Renderer;

use DecodeLabs\Exceptional\Exception as ExceptionalException;

use DecodeLabs\Glitch\Dump;
use DecodeLabs\Glitch\Packet;
use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\RendererTrait;
use DecodeLabs\Glitch\Stat;
use DecodeLabs\Nuance\Entity\NativeString;
use DecodeLabs\Nuance\Structure\ClassList;
use DecodeLabs\Nuance\Renderer\Cli as NuanceCliRenderer;
use Throwable;

class Cli extends NuanceCliRenderer implements Renderer
{
    use RendererTrait;


    public const bool RenderInProduction = true;

    public const bool RenderStack = false;



    /**
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

            $output[] = $this->format($statString, 'cyan');
        }

        return implode(' | ', $output);
    }

    public function renderExceptionView(
        Throwable $exception,
        Dump $dataDump
    ): Packet {
        $output = [];
        $entity = $this->inspector->inspect($exception);

        if (!empty($header = $this->renderHeader('exception'))) {
            $output[] = $header;
        }

        $output[] = $this->renderStats($dataDump->getStats());
        $output[] = $this->renderExceptionEntity($entity);
        $output[] = $this->renderExceptionMessage($exception);
        $output[] = $this->renderTraceSection($dataDump->getTrace());

        if (!empty($footer = $this->renderFooter())) {
            $output[] = $footer;
        }

        return $this->exportExceptionBuffer($output);
    }

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
            $head[] =
                $this->format('#', 'white', null, 'dim') .
                $this->format((string)$code, 'magenta');
        }
        if ($httpCode) {
            $head[] =
                $this->format('HTTP', 'white', null, 'dim') . ' ' .
                $this->format((string)$httpCode, 'magenta');
        }

        $output = '';

        if (!empty($head)) {
            $output .= implode(' | ', $head) . "\n";
        }

        $output .= $this->renderMultiLineString(
            new NativeString($message),
            ClassList::of('exception')
        );

        return $output;
    }

    /**
     * @param array<string> $buffer
     */
    protected function exportBuffer(
        array $buffer,
        bool $final
    ): Packet {
        $output = "\n" . implode("\n\n", $buffer) . "\n\n";
        return new Packet($output, 'text/plain');
    }
}
