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

class Http implements Transport
{
    public function __construct(
        protected Glitch $glitch
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
        if ($final && !headers_sent()) {
            header('HTTP/1.1 501');
            header('Content-Type: ' . $packet->getContentType() . '; charset=UTF-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, HEAD');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-HTTP-Method-Override, Accept, Accept-Encoding, Accept-Language, Connection, Host, Origin, Referer, User-Agent');

            if ($headerBufferSender = $this->glitch->getHeaderBufferSender()) {
                $headerBufferSender();
            }
        }

        echo $packet->getBody();
    }
}
