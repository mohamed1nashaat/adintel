<?php

namespace App\Services\Integrations\Contracts;

class Ad
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $adSetId,
        public readonly Creative $creative,
        public readonly string $status,
        public readonly ?string $trackingUrl = null,
        public readonly ?array $conversionSpecs = []
    ) {}
}
