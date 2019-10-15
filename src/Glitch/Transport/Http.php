<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Transport;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Transport;
use DecodeLabs\Glitch\Packet;

class Http implements Transport
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
        if ($final && !headers_sent()) {
            header('HTTP/1.1 501');
            header('Content-Type: '.$packet->getContentType().'; charset=UTF-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');

            if ($headerBufferSender = Glitch::getHeaderBufferSender()) {
                $headerBufferSender();
            }
        }

        echo $packet->getBody();
    }
}
