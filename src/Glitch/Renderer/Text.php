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
    public const RENDER_IN_PRODUCTION = true;
    public const SPACES = 2;
    public const RENDER_CLOSED = false;

    public const RENDER_SECTIONS = [
        'info' => true,
        'meta' => true,
        'text' => true,
        'properties' => true,
        'values' => true,
        'stack' => true
    ];

    public const RENDER_STACK = true;

    use Base;


    /**
     * Render entity info block
     * Not used for Text rendering
     */
    protected function renderInfoBlock(Entity $entity, int $level = 0, bool $open): string
    {
        return '';
    }
}
