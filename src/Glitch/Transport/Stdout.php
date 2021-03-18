<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Transport;

use DecodeLabs\Glitch\Packet;
use DecodeLabs\Glitch\Transport;

class Stdout implements Transport
{
    /**
     * Send dump straight to output
     */
    public function sendDump(Packet $packet, bool $final): void
    {
        $this->sendPacket($packet, $final);
    }

    /**
     * Send exception dump straight to output
     */
    public function sendException(Packet $packet, bool $final): void
    {
        $this->sendPacket($packet, $final);
    }

    /**
     * Send packet
     */
    protected function sendPacket(Packet $packet, bool $final): void
    {
        echo $packet->getBody();
    }
}
