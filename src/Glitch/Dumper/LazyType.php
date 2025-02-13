<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper;

enum LazyType: string
{
    case Ghost = 'ghost';
    case Proxy = 'proxy';
    case Unknown = 'lazy';
}
