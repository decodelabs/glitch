<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Transport;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Packet;
use DecodeLabs\Glitch\Transport;

class Stdout implements Transport
{
    public function __construct(
        protected Glitch $service
    ) {
    }

    public function sendDump(
        Packet $packet,
        bool $final
    ): void {
        $this->sendPacket($packet, $final);
    }

    public function sendException(
        Packet $packet,
        bool $final
    ): void {
        $this->sendPacket($packet, $final);
    }

    protected function sendPacket(
        Packet $packet,
        bool $final
    ): void {
        echo $packet->getBody();
    }
}
