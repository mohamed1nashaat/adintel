<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

abstract class BaseObjectiveCalculator implements ObjectiveCalculatorInterface
{
    public function aggregateMetrics(Collection $metrics): array
    {
        if ($metrics->isEmpty()) {
            return [];
        }

        return [
            'spend' => $metrics->sum('spend'),
            'impressions' => $metrics->sum('impressions'),
            'reach' => $metrics->sum('reach'),
            'clicks' => $metrics->sum('clicks'),
            'video_views' => $metrics->sum('video_views'),
            'conversions' => $metrics->sum('conversions'),
            'revenue' => $metrics->sum('revenue'),
            'purchases' => $metrics->sum('purchases'),
            'leads' => $metrics->sum('leads'),
            'calls' => $metrics->sum('calls'),
            'sessions' => $metrics->sum('sessions'),
            'atc' => $metrics->sum('atc'),
        ];
    }

    public function getAllKpis(): array
    {
        return array_merge($this->getPrimaryKpis(), $this->getSecondaryKpis());
    }

    protected function safeDivide(float $numerator, float $denominator, int $precision = 2): ?float
    {
        if ($denominator == 0) {
            return null;
        }

        return round($numerator / $denominator, $precision);
    }

    protected function safePercentage(float $numerator, float $denominator, int $precision = 2): ?float
    {
        $result = $this->safeDivide($numerator, $denominator, $precision + 2);
        return $result !== null ? round($result * 100, $precision) : null;
    }

    protected function calculateCpm(array $metrics): ?float
    {
        if ($metrics['impressions'] == 0) return null;
        return $this->safeDivide($metrics['spend'], $metrics['impressions'] / 1000);
    }

    protected function calculateCpc(array $metrics): ?float
    {
        return $this->safeDivide($metrics['spend'], $metrics['clicks']);
    }

    protected function calculateCtr(array $metrics): ?float
    {
        return $this->safePercentage($metrics['clicks'], $metrics['impressions']);
    }

    protected function calculateCvr(array $metrics): ?float
    {
        return $this->safePercentage($metrics['conversions'], $metrics['clicks']);
    }

    protected function calculateVtr(array $metrics): ?float
    {
        return $this->safePercentage($metrics['video_views'], $metrics['impressions']);
    }

    protected function calculateFrequency(array $metrics): ?float
    {
        return $this->safeDivide($metrics['impressions'], $metrics['reach']);
    }

    protected function calculateCpl(array $metrics): ?float
    {
        return $this->safeDivide($metrics['spend'], $metrics['leads']);
    }

    protected function calculateRoas(array $metrics): ?float
    {
        return $this->safeDivide($metrics['revenue'], $metrics['spend']);
    }

    protected function calculateCpa(array $metrics): ?float
    {
        return $this->safeDivide($metrics['spend'], $metrics['purchases']);
    }

    protected function calculateAov(array $metrics): ?float
    {
        return $this->safeDivide($metrics['revenue'], $metrics['purchases']);
    }

    protected function calculateCostPerCall(array $metrics): ?float
    {
        return $this->safeDivide($metrics['spend'], $metrics['calls']);
    }
}
