<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Renderer;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Renderer;

class Text implements Renderer
{
    use Base;

    protected const RenderInProduction = true;
    protected const Spaces = 2;
    protected const RenderClosed = false;

    protected const RenderSections = [
        'info' => true,
        'meta' => true,
        'text' => true,
        'properties' => true,
        'values' => true,
        'stack' => true
    ];

    protected const RenderStack = true;


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
