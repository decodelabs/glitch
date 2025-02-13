<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use DecodeLabs\Glitch\Dumper\Dump;
use DecodeLabs\Glitch\Dumper\Entity;

use Throwable;

interface Renderer
{
    public const bool RenderInProduction = false;
    public const int Spaces = 2;
    public const bool RenderClosed = true;

    /** @var array<string,bool> */
    public const array RenderSections = [
        'info' => true,
        'meta' => true,
        'text' => true,
        'props' => true,
        'values' => true,
        'stack' => true
    ];

    public const bool RenderStack = true;

    /**
     * Override production rendering
     *
     * @return $this
     */
    public function setProductionOverride(
        bool $flag
    ): static;

    /**
     * Get production override
     */
    public function getProductionOverride(): bool;

    public function renderDump(
        Dump $dump,
        bool $final
    ): Packet;

    public function renderException(
        Throwable $exception,
        Entity $entity,
        Dump $dataDump
    ): Packet;
}
