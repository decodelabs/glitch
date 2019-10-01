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
    public function sendDump(string $packet, ?callable $headerBufferSender): void
    {
        $this->sendPacket($packet, $headerBufferSender);
    }

    /**
     * Send exception dump straight to output
     */
    public function sendException(string $packet, ?callable $headerBufferSender): void
    {
        $this->sendPacket($packet, $headerBufferSender);
    }

    protected function sendPacket(string $packet, ?callable $headerBufferSender): void
    {
        if (!in_array(\PHP_SAPI, ['cli', 'phpdbg']) && !headers_sent() && $headerBufferSender) {
            $headerBufferSender();
        }

        echo $packet;
    }
}
