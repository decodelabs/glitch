<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Exception;

interface EIncomplete
{
    public function getReflection(): ?\ReflectionFunctionAbstract;
}
