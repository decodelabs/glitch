<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Dumper;

use Glitch\Context;

interface Renderer
{
    public function render(Dump $dump, bool $isFinal=false): string;
}
