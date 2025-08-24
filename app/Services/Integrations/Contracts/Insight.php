<?php

namespace App\Services\Integrations\Contracts;

class Insight
{
    public function __construct(
        public readonly string $campaignId,
        public readonly string $date,
        public readonly int $impressions,
        public readonly int $clicks,
        public readonly float $spend,
        public readonly int $conversions,
        public readonly ?float $ctr = null,
        public readonly ?float $cpc = null,
        public readonly ?float $cpa = null,
        public readonly ?float $roas = null
    ) {}
}
