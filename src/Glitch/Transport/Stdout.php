<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch\Transport;

use Glitch\ITransport;

class Stdout implements ITransport
{
    /**
     * Send dump straight to output
     */
    public function sendDump(string $packet): void
    {
        echo $packet;
    }
}
