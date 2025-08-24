<?php

namespace App\Services\Integrations\Contracts;

class Creative
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $status,
        public readonly string $campaignId,
        public readonly ?string $type = null,
        public readonly ?string $url = null,
        public readonly ?array $assets = null
    ) {}
}
