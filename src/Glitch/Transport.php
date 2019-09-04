<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch;

interface Transport
{
    public function sendDump(string $packet): void;
    public function sendException(string $packet): void;
}
