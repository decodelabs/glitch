<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use DecodeLabs\Coercion;
use DecodeLabs\Exceptional\Exception as ExceptionalException;
use DecodeLabs\Monarch;
use DecodeLabs\Nuance\Entity as NuanceEntity;
use DecodeLabs\Remnant\Trace;
use Throwable;

/**
 * @phpstan-require-implements Renderer
 */
trait RendererTrait
{
    protected Context $context;
    protected bool $productionOverride = false;

    public function __construct(
        Context $context
    ) {
        $this->context = $context;

        parent::__construct();
    }


    /**
     * @return $this
     */
    public function setProductionOverride(
        bool $flag
    ): static {
        $this->productionOverride = $flag;
        return $this;
    }

    public function getProductionOverride(): bool
    {
        return $this->productionOverride;
    }

    protected function shouldRenderInProduction(): bool
    {
        return Coercion::toBool(static::RenderInProduction);
    }


    protected function shouldRender(): bool
    {
        return
            !Monarch::isProduction() ||
            $this->shouldRenderInProduction() ||
            $this->productionOverride;
    }


    public function renderDumpView(
        Dump $dump,
        bool $final
    ): Packet {
        if (!$this->shouldRender()) {
            return $this->exportDumpBuffer([], $final);
        }

        $output = [];

        if (!empty($header = $this->renderHeader('dump'))) {
            $output[] = $header;
        }

        $output[] = $this->renderStats($dump->getStats());
        $output[] = $this->renderDumpEntities($dump);

        if (static::RenderStack) {
            $output[] = $this->renderTraceSection($dump->getTrace());
        }

        if (!empty($footer = $this->renderFooter())) {
            $output[] = $footer;
        }

        return $this->exportDumpBuffer($output, $final);
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

        if (!$this->shouldRender()) {
            $output[] = $this->renderProductionExceptionMessage($exception);
        } else {
            $output[] = $this->renderStats($dataDump->getStats());
            $output[] = $this->renderExceptionMessage($exception);
            $output[] = $this->renderExceptionEntity($entity);
            $output[] = $this->renderTraceSection($dataDump->getTrace());
        }

        if (!empty($footer = $this->renderFooter())) {
            $output[] = $footer;
        }

        return $this->exportExceptionBuffer($output);
    }




    protected function renderHeader(
        string $class
    ): string {
        return '';
    }

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

            $output[] = $statString;
        }

        return implode(' | ', $output);
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
            $head[] = '#' . $code;
        }
        if ($httpCode) {
            $head[] = 'HTTP ' . $httpCode;
        }

        $output = '';

        if (!empty($head)) {
            $output .= implode(' | ', $head) . "\n";
        }

        $output .= $this->escape($message);
        return $output;
    }

    protected function renderProductionExceptionMessage(
        Throwable $exception
    ): string {
        return '';
    }


    protected function renderDumpEntities(
        Dump $dump
    ): string {
        $output = [];

        foreach ($dump->getEntities() as $value) {
            $output[] = $this->renderValue($value);
        }

        return implode("\n\n", $output);
    }


    protected function renderExceptionEntity(
        NuanceEntity $entity
    ): string {
        return $this->renderValue($entity);
    }



    protected function renderTraceSection(
        Trace $trace,
    ): string {
        return $this->renderStackTrace($trace);
    }

    protected function renderFooter(): string
    {
        return '';
    }

    /**
     * @param array<string> $buffer
     */
    protected function exportBuffer(
        array $buffer,
        bool $final
    ): Packet {
        $output = implode("\n\n", $buffer);
        return new Packet($output, 'text/plain');
    }

    /**
     * @param array<string> $buffer
     */
    protected function exportDumpBuffer(
        array $buffer,
        bool $final
    ): Packet {
        return $this->exportBuffer($buffer, $final);
    }

    /**
     * @param array<string> $buffer
     */
    protected function exportExceptionBuffer(
        array $buffer
    ): Packet {
        return $this->exportBuffer($buffer, true);
    }

    abstract protected function prettifyPath(
        string $path
    ): string;
}
