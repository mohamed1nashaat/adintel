<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomAudience extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'criteria',
        'date_range',
        'lead_count',
        'status',
        'platform_sync_status',
        'metadata',
        'created_by',
        'last_synced_at',
    ];

    protected $casts = [
        'criteria' => 'array',
        'date_range' => 'array',
        'platform_sync_status' => 'array',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Global scope to filter by tenant
     */
    protected static function booted()
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (session('current_tenant_id')) {
                $builder->where('tenant_id', session('current_tenant_id'));
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'custom_audience_leads');
    }

    /**
     * Get leads that match the audience criteria
     */
    public function getMatchingLeads()
    {
        $query = Lead::where('tenant_id', $this->tenant_id);

        // Apply date range filter
        if (!empty($this->date_range['start'])) {
            $query->where('created_at', '>=', $this->date_range['start']);
        }
        if (!empty($this->date_range['end'])) {
            $query->where('created_at', '<=', $this->date_range['end']);
        }

        // Apply criteria filters
        foreach ($this->criteria as $criterion) {
            switch ($criterion['field']) {
                case 'source':
                    $query->where('source', $criterion['operator'], $criterion['value']);
                    break;
                case 'status':
                    $query->where('status', $criterion['operator'], $criterion['value']);
                    break;
                case 'email':
                    $query->where('email', $criterion['operator'], $criterion['value']);
                    break;
                case 'phone':
                    $query->where('phone', $criterion['operator'], $criterion['value']);
                    break;
                case 'location':
                    if (isset($criterion['value']['country'])) {
                        $query->whereJsonContains('metadata->location->country', $criterion['value']['country']);
                    }
                    if (isset($criterion['value']['city'])) {
                        $query->whereJsonContains('metadata->location->city', $criterion['value']['city']);
                    }
                    break;
                case 'custom_field':
                    $query->whereJsonContains("metadata->{$criterion['custom_field']}", $criterion['value']);
                    break;
            }
        }

        return $query;
    }

    /**
     * Update lead count
     */
    public function updateLeadCount()
    {
        $this->lead_count = $this->getMatchingLeads()->count();
        $this->save();
    }

    /**
     * Check if audience is ready for sync
     */
    public function isReadyForSync(): bool
    {
        return $this->status === 'ready' && $this->lead_count > 0;
    }

    /**
     * Get sync status for a specific platform
     */
    public function getSyncStatus(string $platform): string
    {
        return $this->platform_sync_status[$platform] ?? 'not_synced';
    }

    /**
     * Update sync status for a platform
     */
    public function updateSyncStatus(string $platform, string $status, array $metadata = [])
    {
        $syncStatus = $this->platform_sync_status ?? [];
        $syncStatus[$platform] = [
            'status' => $status,
            'updated_at' => now()->toISOString(),
            'metadata' => $metadata,
        ];
        
        $this->platform_sync_status = $syncStatus;
        
        if ($status === 'synced') {
            $this->last_synced_at = now();
        }
        
        $this->save();
    }
}
