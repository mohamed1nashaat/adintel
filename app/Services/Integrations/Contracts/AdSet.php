<?php

namespace App\Services\Integrations\Contracts;

class AdSet
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $campaignId,
        public readonly Targeting $targeting,
        public readonly string $status,
        public readonly float $dailyBudget,
        public readonly ?string $startTime = null,
        public readonly ?string $endTime = null,
        public readonly ?array $optimizationGoals = []
    ) {}
}
