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

class Text implements Renderer
{
    const RENDER_IN_PRODUCTION = true;
    const SPACES = 2;
    const RENDER_CLOSED = false;

    const RENDER_SECTIONS = [
        'info' => true,
        'meta' => true,
        'text' => true,
        'properties' => true,
        'values' => true,
        'stack' => true
    ];

    const RENDER_STACK = true;

    use Base;


    /**
     * Render entity info block
     * Not used for Text rendering
     */
    protected function renderInfoBlock(Entity $entity): string
    {
        return '';
    }
}
