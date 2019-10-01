<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Transport;

use DecodeLabs\Glitch\Transport;
use DecodeLabs\Glitch\Packet;

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
