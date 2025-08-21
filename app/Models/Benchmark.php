<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Benchmark extends Model
{
    protected $fillable = [
        'tenant_id',
        'industry',
        'region',
        'platform',
        'objective',
        'metric_name',
        'benchmark_value',
        'percentile_25',
        'percentile_50',
        'percentile_75',
        'percentile_90',
        'sample_size',
        'period_start',
        'period_end',
        'additional_filters',
        'data_source',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'benchmark_value' => 'decimal:4',
        'percentile_25' => 'decimal:4',
        'percentile_50' => 'decimal:4',
        'percentile_75' => 'decimal:4',
        'percentile_90' => 'decimal:4',
        'period_start' => 'date',
        'period_end' => 'date',
        'additional_filters' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    public function scopeForRegion(Builder $query, string $region): Builder
    {
        return $query->where('region', $region);
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function scopeForObjective(Builder $query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }

    public function scopeForMetric(Builder $query, string $metric): Builder
    {
        return $query->where('metric_name', $metric);
    }

    public function scopeRecent(Builder $query, int $days = 90): Builder
    {
        return $query->where('period_end', '>=', now()->subDays($days));
    }

    // Helper methods
    public function getPerformanceLevel(float $actualValue): string
    {
        if ($actualValue <= $this->percentile_25) {
            return 'poor';
        } elseif ($actualValue <= $this->percentile_50) {
            return 'below_average';
        } elseif ($actualValue <= $this->percentile_75) {
            return 'average';
        } elseif ($actualValue <= $this->percentile_90) {
            return 'good';
        } else {
            return 'excellent';
        }
    }

    public function getPerformanceScore(float $actualValue): int
    {
        $level = $this->getPerformanceLevel($actualValue);
        
        return match($level) {
            'poor' => 1,
            'below_average' => 2,
            'average' => 3,
            'good' => 4,
            'excellent' => 5,
        };
    }

    public function getImprovementSuggestion(float $actualValue): string
    {
        $level = $this->getPerformanceLevel($actualValue);
        
        return match($level) {
            'poor' => "Your {$this->metric_name} is significantly below industry average. Consider reviewing your targeting and creative strategy.",
            'below_average' => "Your {$this->metric_name} is below average. Small optimizations could yield significant improvements.",
            'average' => "Your {$this->metric_name} is performing at industry average. Look for opportunities to optimize further.",
            'good' => "Your {$this->metric_name} is performing well above average. Continue current strategies.",
            'excellent' => "Your {$this->metric_name} is in the top 10% of performers. Excellent work!",
        };
    }

    // Static methods for common queries
    public static function getIndustryBenchmarks(string $industry, string $region = 'gcc'): array
    {
        return self::active()
            ->forIndustry($industry)
            ->forRegion($region)
            ->recent()
            ->get()
            ->groupBy(['platform', 'objective', 'metric_name'])
            ->toArray();
    }

    public static function comparePerformance(string $industry, string $region, string $platform, string $objective, array $metrics): array
    {
        $benchmarks = self::active()
            ->forIndustry($industry)
            ->forRegion($region)
            ->forPlatform($platform)
            ->forObjective($objective)
            ->recent()
            ->get()
            ->keyBy('metric_name');

        $comparison = [];
        
        foreach ($metrics as $metricName => $actualValue) {
            if (isset($benchmarks[$metricName])) {
                $benchmark = $benchmarks[$metricName];
                $comparison[$metricName] = [
                    'actual_value' => $actualValue,
                    'benchmark_value' => $benchmark->benchmark_value,
                    'percentile_50' => $benchmark->percentile_50,
                    'performance_level' => $benchmark->getPerformanceLevel($actualValue),
                    'performance_score' => $benchmark->getPerformanceScore($actualValue),
                    'improvement_suggestion' => $benchmark->getImprovementSuggestion($actualValue),
                    'vs_benchmark' => $actualValue - $benchmark->benchmark_value,
                    'vs_median' => $actualValue - $benchmark->percentile_50,
                ];
            }
        }

        return $comparison;
    }
}
