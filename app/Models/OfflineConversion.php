<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineConversion extends Model
{
    protected $fillable = [
        'tenant_id',
        'lead_id',
        'campaign_id',
        'conversion_type',
        'conversion_value',
        'conversion_currency',
        'conversion_date',
        'source',
        'external_id',
        'metadata',
        'notes',
        'status',
    ];

    protected $casts = [
        'conversion_value' => 'decimal:2',
        'conversion_date' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the conversion
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the lead associated with the conversion
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the campaign associated with the conversion
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }

    /**
     * Scope to get conversions for a specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get conversions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('conversion_type', $type);
    }

    /**
     * Scope to get conversions by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get conversions within date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('conversion_date', [$from, $to]);
    }

    /**
     * Get formatted conversion value
     */
    public function getFormattedValueAttribute(): string
    {
        return number_format($this->conversion_value, 2) . ' ' . $this->conversion_currency;
    }

    /**
     * Check if conversion is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if conversion is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if conversion is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
