<?php

namespace App\Security;

final class TicketTokenHasher
{
    public function __construct(private readonly string $pepper)
    {
    }

    public function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken.$this->pepper);
    }
}
