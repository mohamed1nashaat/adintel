<?php

namespace App\Services\Integrations\Contracts;

class RateLimitInfo
{
    public function __construct(
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $resetTime,
        public readonly ?string $window = null
    ) {}
}
