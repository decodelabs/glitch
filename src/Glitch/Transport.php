<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use DecodeLabs\Glitch;

interface Transport
{
    public function __construct(
        Glitch $service
    );

    public function sendDump(
        Packet $packet,
        bool $final
    ): void;

    public function sendException(
        Packet $packet,
        bool $final
    ): void;
}
