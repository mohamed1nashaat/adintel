<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AdMetric extends Model
{
    protected $fillable = [
        'tenant_id',
        'date',
        'platform',
        'ad_account_id',
        'ad_campaign_id',
        'objective',
        'spend',
        'impressions',
        'reach',
        'clicks',
        'video_views',
        'conversions',
        'revenue',
        'purchases',
        'leads',
        'calls',
        'sessions',
        'atc',
        'checksum',
    ];

    protected $casts = [
        'date' => 'date',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'impressions' => 'integer',
        'reach' => 'integer',
        'clicks' => 'integer',
        'video_views' => 'integer',
        'conversions' => 'integer',
        'purchases' => 'integer',
        'leads' => 'integer',
        'calls' => 'integer',
        'sessions' => 'integer',
        'atc' => 'integer',
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

    public function adCampaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class);
    }

    // Objective-specific KPI calculations
    public function getCpmAttribute(): ?float
    {
        if ($this->impressions == 0) return null;
        return ($this->spend / ($this->impressions / 1000));
    }

    public function getCpcAttribute(): ?float
    {
        if ($this->clicks == 0) return null;
        return ($this->spend / $this->clicks);
    }

    public function getCtrAttribute(): ?float
    {
        if ($this->impressions == 0) return null;
        return (($this->clicks / $this->impressions) * 100);
    }

    public function getCplAttribute(): ?float
    {
        if ($this->leads == 0) return null;
        return ($this->spend / $this->leads);
    }

    public function getCvr(): ?float
    {
        if ($this->clicks == 0) return null;
        return (($this->conversions / $this->clicks) * 100);
    }

    public function getRoasAttribute(): ?float
    {
        if ($this->spend == 0) return null;
        return ($this->revenue / $this->spend);
    }

    public function getCpaAttribute(): ?float
    {
        if ($this->purchases == 0) return null;
        return ($this->spend / $this->purchases);
    }

    public function getAovAttribute(): ?float
    {
        if ($this->purchases == 0) return null;
        return ($this->revenue / $this->purchases);
    }

    public function getCostPerCallAttribute(): ?float
    {
        if ($this->calls == 0) return null;
        return ($this->spend / $this->calls);
    }

    public function getVtrAttribute(): ?float
    {
        if ($this->impressions == 0) return null;
        return (($this->video_views / $this->impressions) * 100);
    }

    public function getFrequencyAttribute(): ?float
    {
        if ($this->reach == 0) return null;
        return ($this->impressions / $this->reach);
    }

    // Scopes for filtering
    public function scopeForObjective(Builder $query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function scopeForDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('ad_account_id', $accountId);
    }

    public function scopeForCampaign(Builder $query, int $campaignId): Builder
    {
        return $query->where('ad_campaign_id', $campaignId);
    }
}
