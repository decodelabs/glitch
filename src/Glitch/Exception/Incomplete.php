<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Exception;

use DecodeLabs\Exceptional;
use ReflectionFunctionAbstract;

interface IncompleteException extends Exceptional\Exception
{
    public function getReflection(): ?ReflectionFunctionAbstract;
}
