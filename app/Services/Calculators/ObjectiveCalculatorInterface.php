<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

interface ObjectiveCalculatorInterface
{
    /**
     * Calculate KPIs for the given objective
     */
    public function calculateKpis(Collection $metrics): array;

    /**
     * Get the primary KPIs for this objective
     */
    public function getPrimaryKpis(): array;

    /**
     * Get the secondary KPIs for this objective
     */
    public function getSecondaryKpis(): array;

    /**
     * Get all available KPIs for this objective
     */
    public function getAllKpis(): array;

    /**
     * Calculate aggregated metrics from raw data
     */
    public function aggregateMetrics(Collection $metrics): array;

    /**
     * Validate if the objective can be calculated with given metrics
     */
    public function canCalculate(array $aggregatedMetrics): bool;
}
