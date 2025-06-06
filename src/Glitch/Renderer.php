<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use DecodeLabs\Glitch\Dump;
use DecodeLabs\Nuance\Renderer as NuanceRenderer;

use Throwable;

interface Renderer extends NuanceRenderer
{
    public const bool RenderInProduction = false;
    public const bool RenderStack = true;

    /**
     * @return $this
     */
    public function setProductionOverride(
        bool $flag
    ): static;

    public function getProductionOverride(): bool;

    public function renderDumpView(
        Dump $dump,
        bool $final
    ): Packet;

    public function renderExceptionView(
        Throwable $exception,
        Dump $dataDump
    ): Packet;
}
