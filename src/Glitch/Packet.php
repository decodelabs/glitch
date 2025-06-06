<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

class Packet
{
    protected string $body;
    protected string $contentType;

    public function __construct(
        string $body,
        string $contentType
    ) {
        $this->body = $body;
        $this->contentType = $contentType;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }
}
