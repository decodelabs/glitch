<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch;

use DecodeLabs\Glitch\Context;
use DecodeLabs\Glitch\Dumper\Dump;
use DecodeLabs\Glitch\Dumper\Entity;

interface Renderer
{
    public function renderDump(Dump $dump): string;
    public function renderException(\Throwable $exception, Entity $entity, Dump $dataDump): string;
}
