<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Renderer;

use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\RendererTrait;
use DecodeLabs\Nuance\Renderer\Text as NuanceTextRenderer;

class Text extends NuanceTextRenderer implements Renderer
{
    use RendererTrait;

    public const bool RenderInProduction = true;
    public const int Spaces = 2;

    public const bool RenderStack = true;
}
