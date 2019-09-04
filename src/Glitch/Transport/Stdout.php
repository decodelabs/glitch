<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Transport;

use DecodeLabs\Glitch\Transport;

class Stdout implements Transport
{
    /**
     * Send dump straight to output
     */
    public function sendDump(string $packet): void
    {
        echo $packet;
    }

    /**
     * Send exception dump straight to output
     */
    public function sendException(string $packet): void
    {
        echo $packet;
    }
}
