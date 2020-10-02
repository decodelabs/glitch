<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Exception;

use ReflectionFunctionAbstract;

trait IncompleteExceptionTrait
{
    /**
     * Get Reflection object for active function in stack frame
     */
    public function getReflection(): ?ReflectionFunctionAbstract
    {
        return $this->getStackTrace()[1]->getReflection();
    }
}
