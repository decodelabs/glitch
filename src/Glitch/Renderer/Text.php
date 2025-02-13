<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Renderer;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\RendererTrait;

class Text implements Renderer
{
    use RendererTrait;

    public const bool RenderInProduction = true;
    public const int Spaces = 2;
    public const bool RenderClosed = false;

    public const array RenderSections = [
        'info' => true,
        'meta' => true,
        'text' => true,
        'properties' => true,
        'values' => true,
        'stack' => true
    ];

    public const bool RenderStack = true;


    /**
     * Render entity info block
     * Not used for Text rendering
     */
    protected function renderInfoBlock(
        Entity $entity,
        int $level,
        bool $open
    ): string {
        return '';
    }
}
