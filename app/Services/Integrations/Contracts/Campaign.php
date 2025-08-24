<?php

namespace App\Services\Integrations\Contracts;

class Campaign
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $status,
        public readonly string $accountId,
        public readonly ?string $objective = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?float $budget = null
    ) {}
}
