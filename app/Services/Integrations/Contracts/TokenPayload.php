<?php

namespace App\Services\Integrations\Contracts;

class TokenPayload
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly int $expiresIn,
        public readonly array $scopes = []
    ) {}
}
