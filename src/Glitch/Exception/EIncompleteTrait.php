<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Exception;

trait EIncompleteTrait
{
    /**
     * Get Reflection object for active function in stack frame
     */
    public function getReflection(): ?\ReflectionFunctionAbstract
    {
        return $this->getStackTrace()[1]->getReflection();
    }
}
