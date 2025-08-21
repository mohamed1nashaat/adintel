<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureFlag extends Model
{
    protected $fillable = [
        'tenant_id',
        'feature_key',
        'is_active',
        'conditions',
        'rollout_percentage',
        'enabled_at',
        'disabled_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
        'rollout_percentage' => 'integer',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the feature flag
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to get flags for a specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get global flags
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Scope to get active flags
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get flags by feature key
     */
    public function scopeByFeature($query, string $featureKey)
    {
        return $query->where('feature_key', $featureKey);
    }

    /**
     * Check if flag is global
     */
    public function isGlobal(): bool
    {
        return is_null($this->tenant_id);
    }

    /**
     * Check if flag is tenant-specific
     */
    public function isTenantSpecific(): bool
    {
        return !is_null($this->tenant_id);
    }

    /**
     * Check if flag has conditions
     */
    public function hasConditions(): bool
    {
        return !empty($this->conditions);
    }

    /**
     * Check if flag has partial rollout
     */
    public function hasPartialRollout(): bool
    {
        return $this->rollout_percentage < 100;
    }

    /**
     * Get rollout status
     */
    public function getRolloutStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'disabled';
        }

        if ($this->rollout_percentage == 0) {
            return 'not_started';
        } elseif ($this->rollout_percentage < 25) {
            return 'limited';
        } elseif ($this->rollout_percentage < 75) {
            return 'partial';
        } elseif ($this->rollout_percentage < 100) {
            return 'wide';
        } else {
            return 'complete';
        }
    }
}
