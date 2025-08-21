<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Lead extends Model
{
    protected $fillable = [
        'tenant_id',
        'lead_source_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'job_title',
        'message',
        'custom_fields',
        'status',
        'quality',
        'estimated_value',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'referrer_url',
        'landing_page',
        'ip_address',
        'user_agent',
        'form_data',
        'converted_at',
        'assigned_to',
        'notes',
        'synced_to_sheets',
        'sheets_synced_at',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'form_data' => 'array',
        'estimated_value' => 'decimal:2',
        'converted_at' => 'datetime',
        'synced_to_sheets' => 'boolean',
        'sheets_synced_at' => 'datetime',
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

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByQuality(Builder $query, string $quality): Builder
    {
        return $query->where('quality', $quality);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->where('status', 'converted');
    }

    public function scopeHot(Builder $query): Builder
    {
        return $query->where('quality', 'hot');
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeFromSource(Builder $query, int $sourceId): Builder
    {
        return $query->where('lead_source_id', $sourceId);
    }

    public function scopeNeedsSheetsSync(Builder $query): Builder
    {
        return $query->where('synced_to_sheets', false);
    }

    public function scopeByDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // Helper methods
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isHot(): bool
    {
        return $this->quality === 'hot';
    }

    public function isAssigned(): bool
    {
        return !is_null($this->assigned_to);
    }

    public function needsSheetsSync(): bool
    {
        return !$this->synced_to_sheets;
    }

    public function markAsConverted(): void
    {
        $this->update([
            'status' => 'converted',
            'converted_at' => now(),
        ]);
    }

    public function assignTo(User $user): void
    {
        $this->update(['assigned_to' => $user->id]);
    }

    public function unassign(): void
    {
        $this->update(['assigned_to' => null]);
    }

    public function updateQuality(string $quality): void
    {
        $this->update(['quality' => $quality]);
    }

    public function addNote(string $note): void
    {
        $currentNotes = $this->notes ? $this->notes . "\n\n" : '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $newNote = "[{$timestamp}] {$note}";
        
        $this->update(['notes' => $currentNotes . $newNote]);
    }

    public function getUtmParameters(): array
    {
        return [
            'source' => $this->utm_source,
            'medium' => $this->utm_medium,
            'campaign' => $this->utm_campaign,
            'term' => $this->utm_term,
            'content' => $this->utm_content,
        ];
    }

    public function getCustomField(string $key, $default = null)
    {
        return $this->custom_fields[$key] ?? $default;
    }

    public function setCustomField(string $key, $value): void
    {
        $customFields = $this->custom_fields ?? [];
        $customFields[$key] = $value;
        $this->update(['custom_fields' => $customFields]);
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'new' => 'blue',
            'contacted' => 'yellow',
            'qualified' => 'green',
            'converted' => 'purple',
            'lost' => 'red',
            default => 'gray',
        };
    }

    public function getQualityColor(): string
    {
        return match($this->quality) {
            'hot' => 'red',
            'warm' => 'orange',
            'cold' => 'blue',
            'unqualified' => 'gray',
            default => 'gray',
        };
    }

    public function toSheetsArray(): array
    {
        return [
            'ID' => $this->id,
            'Date' => $this->created_at->format('Y-m-d H:i:s'),
            'First Name' => $this->first_name,
            'Last Name' => $this->last_name,
            'Email' => $this->email,
            'Phone' => $this->phone,
            'Company' => $this->company,
            'Job Title' => $this->job_title,
            'Message' => $this->message,
            'Status' => $this->status,
            'Quality' => $this->quality,
            'Estimated Value' => $this->estimated_value,
            'Source' => $this->leadSource->name ?? '',
            'UTM Source' => $this->utm_source,
            'UTM Medium' => $this->utm_medium,
            'UTM Campaign' => $this->utm_campaign,
            'Landing Page' => $this->landing_page,
            'Assigned To' => $this->assignedUser->name ?? '',
        ];
    }
}
