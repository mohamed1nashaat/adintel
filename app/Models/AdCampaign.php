<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class AdCampaign extends Model
{
    protected $fillable = [
        'tenant_id',
        'ad_account_id',
        'external_campaign_id',
        'name',
        'objective',
        'status',
    ];

    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && session('current_tenant_id')) {
                $builder->where('tenant_id', session('current_tenant_id'));
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function adMetrics(): HasMany
    {
        return $this->hasMany(AdMetric::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForObjective(Builder $query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }

    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('ad_account_id', $accountId);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getPlatform(): string
    {
        return $this->adAccount->integration->platform;
    }

    public function getMetricsForDateRange(string $startDate, string $endDate)
    {
        return $this->adMetrics()
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
    }

    public function getAggregatedMetrics(string $startDate, string $endDate): array
    {
        $metrics = $this->adMetrics()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                SUM(spend) as total_spend,
                SUM(impressions) as total_impressions,
                SUM(reach) as total_reach,
                SUM(clicks) as total_clicks,
                SUM(video_views) as total_video_views,
                SUM(conversions) as total_conversions,
                SUM(revenue) as total_revenue,
                SUM(purchases) as total_purchases,
                SUM(leads) as total_leads,
                SUM(calls) as total_calls,
                SUM(sessions) as total_sessions,
                SUM(atc) as total_atc
            ')
            ->first();

        return $metrics ? $metrics->toArray() : [];
    }
}
