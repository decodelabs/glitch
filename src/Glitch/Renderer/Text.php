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
    const SPACES = 2;

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
