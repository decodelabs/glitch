<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */

use Glitch\Stack\Frame;
use Glitch\Stack\Trace;

interface EGlitch
{
    public function setData($data);
    public function getData();

    public function setHttpCode(?int $code);
    public function getHttpCode(): ?int;

    public function getStackFrame(): Frame;
    public function getStackTrace(): Trace;
}
