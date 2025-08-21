<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PerformanceBenchmark extends Model
{
    protected $fillable = [
        'tenant_id',
        'industry',
        'platform',
        'objective',
        'region',
        'audience_size',
        'cpm_benchmark',
        'cpc_benchmark',
        'cpl_benchmark',
        'cpa_benchmark',
        'ctr_benchmark',
        'cvr_benchmark',
        'roas_benchmark',
        'frequency_benchmark',
        'reach_benchmark',
        'additional_metrics',
        'data_source',
        'period_start',
        'period_end',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'additional_metrics' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'is_active' => 'boolean',
        'cpm_benchmark' => 'decimal:4',
        'cpc_benchmark' => 'decimal:4',
        'cpl_benchmark' => 'decimal:4',
        'cpa_benchmark' => 'decimal:4',
        'ctr_benchmark' => 'decimal:4',
        'cvr_benchmark' => 'decimal:4',
        'roas_benchmark' => 'decimal:4',
        'frequency_benchmark' => 'decimal:4',
        'reach_benchmark' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (session('current_tenant_id')) {
                $builder->where('tenant_id', session('current_tenant_id'));
            }
        });
    }

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

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function scopeForObjective(Builder $query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }

    public function scopeForRegion(Builder $query, string $region): Builder
    {
        return $query->where('region', $region);
    }

    public function scopeForAudienceSize(Builder $query, string $audienceSize): Builder
    {
        return $query->where('audience_size', $audienceSize);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        $now = now();
        return $query->where('period_start', '<=', $now)
                    ->where('period_end', '>=', $now);
    }

    public function scopeRecent(Builder $query, int $days = 90): Builder
    {
        return $query->where('period_end', '>=', now()->subDays($days));
    }

    public function scopeByDataSource(Builder $query, string $source): Builder
    {
        return $query->where('data_source', $source);
    }

    // Helper methods
    public function isCurrentlyActive(): bool
    {
        $now = now();
        return $this->is_active && 
               $this->period_start <= $now && 
               $this->period_end >= $now;
    }

    public function getMetricValue(string $metric): ?float
    {
        $metricMap = [
            'cpm' => 'cpm_benchmark',
            'cpc' => 'cpc_benchmark',
            'cpl' => 'cpl_benchmark',
            'cpa' => 'cpa_benchmark',
            'ctr' => 'ctr_benchmark',
            'cvr' => 'cvr_benchmark',
            'roas' => 'roas_benchmark',
            'frequency' => 'frequency_benchmark',
            'reach' => 'reach_benchmark',
        ];

        if (isset($metricMap[$metric])) {
            return $this->{$metricMap[$metric]};
        }

        // Check additional metrics
        if ($this->additional_metrics && isset($this->additional_metrics[$metric])) {
            return (float) $this->additional_metrics[$metric];
        }

        return null;
    }

    public function setMetricValue(string $metric, float $value): void
    {
        $metricMap = [
            'cpm' => 'cpm_benchmark',
            'cpc' => 'cpc_benchmark',
            'cpl' => 'cpl_benchmark',
            'cpa' => 'cpa_benchmark',
            'ctr' => 'ctr_benchmark',
            'cvr' => 'cvr_benchmark',
            'roas' => 'roas_benchmark',
            'frequency' => 'frequency_benchmark',
            'reach' => 'reach_benchmark',
        ];

        if (isset($metricMap[$metric])) {
            $this->{$metricMap[$metric]} = $value;
        } else {
            // Store in additional metrics
            $additionalMetrics = $this->additional_metrics ?? [];
            $additionalMetrics[$metric] = $value;
            $this->additional_metrics = $additionalMetrics;
        }
    }

    public function getAvailableMetrics(): array
    {
        $metrics = [];
        
        $standardMetrics = [
            'cpm' => 'CPM',
            'cpc' => 'CPC',
            'cpl' => 'CPL',
            'cpa' => 'CPA',
            'ctr' => 'CTR',
            'cvr' => 'CVR',
            'roas' => 'ROAS',
            'frequency' => 'Frequency',
            'reach' => 'Reach',
        ];

        foreach ($standardMetrics as $key => $label) {
            if ($this->getMetricValue($key) !== null) {
                $metrics[$key] = $label;
            }
        }

        // Add additional metrics
        if ($this->additional_metrics) {
            foreach ($this->additional_metrics as $key => $value) {
                if ($value !== null) {
                    $metrics[$key] = ucfirst(str_replace('_', ' ', $key));
                }
            }
        }

        return $metrics;
    }

    public function compareWithActual(array $actualMetrics): array
    {
        $comparison = [];
        
        foreach ($actualMetrics as $metric => $actualValue) {
            $benchmarkValue = $this->getMetricValue($metric);
            
            if ($benchmarkValue !== null && $actualValue !== null) {
                $difference = $actualValue - $benchmarkValue;
                $percentageDifference = $benchmarkValue > 0 
                    ? (($actualValue - $benchmarkValue) / $benchmarkValue) * 100 
                    : 0;

                $comparison[$metric] = [
                    'actual' => $actualValue,
                    'benchmark' => $benchmarkValue,
                    'difference' => $difference,
                    'percentage_difference' => $percentageDifference,
                    'performance' => $this->getPerformanceRating($metric, $percentageDifference),
                ];
            }
        }

        return $comparison;
    }

    private function getPerformanceRating(string $metric, float $percentageDifference): string
    {
        // For cost metrics (lower is better)
        $costMetrics = ['cpm', 'cpc', 'cpl', 'cpa'];
        
        if (in_array($metric, $costMetrics)) {
            if ($percentageDifference <= -20) return 'excellent';
            if ($percentageDifference <= -10) return 'good';
            if ($percentageDifference <= 10) return 'average';
            if ($percentageDifference <= 25) return 'below_average';
            return 'poor';
        }
        
        // For performance metrics (higher is better)
        if ($percentageDifference >= 20) return 'excellent';
        if ($percentageDifference >= 10) return 'good';
        if ($percentageDifference >= -10) return 'average';
        if ($percentageDifference >= -25) return 'below_average';
        return 'poor';
    }

    public function getIndustryLabel(): string
    {
        $industries = [
            'ecommerce' => 'E-commerce',
            'saas' => 'SaaS',
            'healthcare' => 'Healthcare',
            'finance' => 'Finance',
            'education' => 'Education',
            'real_estate' => 'Real Estate',
            'automotive' => 'Automotive',
            'travel' => 'Travel & Tourism',
            'food_beverage' => 'Food & Beverage',
            'fashion' => 'Fashion & Apparel',
            'technology' => 'Technology',
            'fitness' => 'Fitness & Wellness',
            'entertainment' => 'Entertainment',
            'b2b_services' => 'B2B Services',
            'retail' => 'Retail',
        ];

        return $industries[$this->industry] ?? ucfirst(str_replace('_', ' ', $this->industry));
    }

    public function getPlatformLabel(): string
    {
        $platforms = [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'google' => 'Google Ads',
            'youtube' => 'YouTube',
            'linkedin' => 'LinkedIn',
            'twitter' => 'Twitter',
            'tiktok' => 'TikTok',
            'snapchat' => 'Snapchat',
            'pinterest' => 'Pinterest',
        ];

        return $platforms[$this->platform] ?? ucfirst($this->platform);
    }

    public function getObjectiveLabel(): string
    {
        $objectives = [
            'awareness' => 'Brand Awareness',
            'leads' => 'Lead Generation',
            'sales' => 'Sales/Conversions',
            'calls' => 'Phone Calls',
            'traffic' => 'Website Traffic',
            'engagement' => 'Engagement',
            'app_installs' => 'App Installs',
            'video_views' => 'Video Views',
        ];

        return $objectives[$this->objective] ?? ucfirst(str_replace('_', ' ', $this->objective));
    }

    public function getRegionLabel(): string
    {
        $regions = [
            'global' => 'Global',
            'us' => 'United States',
            'eu' => 'Europe',
            'uk' => 'United Kingdom',
            'ca' => 'Canada',
            'au' => 'Australia',
            'mena' => 'Middle East & North Africa',
            'apac' => 'Asia Pacific',
            'latam' => 'Latin America',
        ];

        return $regions[$this->region] ?? ucfirst($this->region);
    }

    public function getAudienceSizeLabel(): string
    {
        $sizes = [
            'small' => 'Small (< 100K)',
            'medium' => 'Medium (100K - 1M)',
            'large' => 'Large (> 1M)',
        ];

        return $sizes[$this->audience_size] ?? ucfirst($this->audience_size);
    }

    public function getDataSourceLabel(): string
    {
        $sources = [
            'internal' => 'Internal Data',
            'industry_report' => 'Industry Report',
            'platform_data' => 'Platform Data',
            'third_party' => 'Third Party',
        ];

        return $sources[$this->data_source] ?? ucfirst(str_replace('_', ' ', $this->data_source));
    }

    // Static methods for finding benchmarks
    public static function findBenchmark(
        string $industry,
        string $platform,
        string $objective,
        string $region = 'global',
        string $audienceSize = 'medium'
    ): ?self {
        return static::active()
            ->forIndustry($industry)
            ->forPlatform($platform)
            ->forObjective($objective)
            ->forRegion($region)
            ->forAudienceSize($audienceSize)
            ->current()
            ->first();
    }

    public static function findClosestBenchmark(
        string $industry,
        string $platform,
        string $objective,
        string $region = 'global',
        string $audienceSize = 'medium'
    ): ?self {
        // Try exact match first
        $benchmark = static::findBenchmark($industry, $platform, $objective, $region, $audienceSize);
        if ($benchmark) {
            return $benchmark;
        }

        // Try with global region
        if ($region !== 'global') {
            $benchmark = static::findBenchmark($industry, $platform, $objective, 'global', $audienceSize);
            if ($benchmark) {
                return $benchmark;
            }
        }

        // Try with medium audience size
        if ($audienceSize !== 'medium') {
            $benchmark = static::findBenchmark($industry, $platform, $objective, $region, 'medium');
            if ($benchmark) {
                return $benchmark;
            }
        }

        // Try with global region and medium audience
        if ($region !== 'global' || $audienceSize !== 'medium') {
            $benchmark = static::findBenchmark($industry, $platform, $objective, 'global', 'medium');
            if ($benchmark) {
                return $benchmark;
            }
        }

        // Try without audience size constraint
        return static::active()
            ->forIndustry($industry)
            ->forPlatform($platform)
            ->forObjective($objective)
            ->current()
            ->first();
    }

    public static function getIndustryOptions(): array
    {
        return [
            'ecommerce' => 'E-commerce',
            'saas' => 'SaaS',
            'healthcare' => 'Healthcare',
            'finance' => 'Finance',
            'education' => 'Education',
            'real_estate' => 'Real Estate',
            'automotive' => 'Automotive',
            'travel' => 'Travel & Tourism',
            'food_beverage' => 'Food & Beverage',
            'fashion' => 'Fashion & Apparel',
            'technology' => 'Technology',
            'fitness' => 'Fitness & Wellness',
            'entertainment' => 'Entertainment',
            'b2b_services' => 'B2B Services',
            'retail' => 'Retail',
        ];
    }

    public static function getPlatformOptions(): array
    {
        return [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'google' => 'Google Ads',
            'youtube' => 'YouTube',
            'linkedin' => 'LinkedIn',
            'twitter' => 'Twitter',
            'tiktok' => 'TikTok',
            'snapchat' => 'Snapchat',
            'pinterest' => 'Pinterest',
        ];
    }

    public static function getObjectiveOptions(): array
    {
        return [
            'awareness' => 'Brand Awareness',
            'leads' => 'Lead Generation',
            'sales' => 'Sales/Conversions',
            'calls' => 'Phone Calls',
            'traffic' => 'Website Traffic',
            'engagement' => 'Engagement',
            'app_installs' => 'App Installs',
            'video_views' => 'Video Views',
        ];
    }

    public static function getRegionOptions(): array
    {
        return [
            'global' => 'Global',
            'us' => 'United States',
            'eu' => 'Europe',
            'uk' => 'United Kingdom',
            'ca' => 'Canada',
            'au' => 'Australia',
            'mena' => 'Middle East & North Africa',
            'apac' => 'Asia Pacific',
            'latam' => 'Latin America',
        ];
    }

    public static function getAudienceSizeOptions(): array
    {
        return [
            'small' => 'Small (< 100K)',
            'medium' => 'Medium (100K - 1M)',
            'large' => 'Large (> 1M)',
        ];
    }
}
