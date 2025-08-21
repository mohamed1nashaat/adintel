<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class WebhookLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'lead_source_id',
        'lead_id',
        'webhook_url',
        'method',
        'headers',
        'payload',
        'parsed_data',
        'status',
        'error_message',
        'source_ip',
        'user_agent',
        'processed_at',
        'retry_count',
        'next_retry_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'parsed_data' => 'array',
        'processed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'retry_count' => 'integer',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed(Builder $query): Builder
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeIgnored(Builder $query): Builder
    {
        return $query->where('status', 'ignored');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeNeedsRetry(Builder $query): Builder
    {
        return $query->where('status', 'failed')
                    ->where('retry_count', '<', 3)
                    ->where(function ($q) {
                        $q->whereNull('next_retry_at')
                          ->orWhere('next_retry_at', '<=', now());
                    });
    }

    public function scopeBySource(Builder $query, int $sourceId): Builder
    {
        return $query->where('lead_source_id', $sourceId);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isIgnored(): bool
    {
        return $this->status === 'ignored';
    }

    public function canRetry(): bool
    {
        return $this->isFailed() && $this->retry_count < 3;
    }

    public function needsRetry(): bool
    {
        return $this->canRetry() && 
               (is_null($this->next_retry_at) || $this->next_retry_at <= now());
    }

    public function markAsProcessed(Lead $lead = null): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
            'lead_id' => $lead?->id,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    public function markAsIgnored(string $reason): void
    {
        $this->update([
            'status' => 'ignored',
            'error_message' => $reason,
            'processed_at' => now(),
        ]);
    }

    public function scheduleRetry(): void
    {
        if (!$this->canRetry()) {
            return;
        }

        $retryDelays = [5, 15, 60]; // minutes
        $delay = $retryDelays[$this->retry_count] ?? 60;

        $this->update([
            'retry_count' => $this->retry_count + 1,
            'next_retry_at' => now()->addMinutes($delay),
        ]);
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'blue',
            'processed' => 'green',
            'failed' => 'red',
            'ignored' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processed' => 'Processed',
            'failed' => 'Failed',
            'ignored' => 'Ignored',
            default => ucfirst($this->status),
        };
    }

    public function getPayloadSize(): string
    {
        $bytes = strlen($this->payload);
        
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' B';
    }

    public function getProcessingTime(): ?string
    {
        if (!$this->processed_at) {
            return null;
        }

        $seconds = $this->processed_at->diffInSeconds($this->created_at);
        
        if ($seconds < 1) {
            return '< 1s';
        } elseif ($seconds < 60) {
            return $seconds . 's';
        } else {
            return $this->processed_at->diffForHumans($this->created_at, true);
        }
    }

    public function getRetryStatus(): string
    {
        if (!$this->isFailed()) {
            return 'N/A';
        }

        if ($this->retry_count >= 3) {
            return 'Max retries reached';
        }

        if ($this->next_retry_at && $this->next_retry_at > now()) {
            return 'Retry scheduled for ' . $this->next_retry_at->format('Y-m-d H:i:s');
        }

        return 'Ready for retry';
    }

    public function getParsedField(string $key, $default = null)
    {
        return $this->parsed_data[$key] ?? $default;
    }

    public function getHeader(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    public function hasValidSignature(): bool
    {
        if (!$this->leadSource) {
            return false;
        }

        $signature = $this->getHeader('X-Signature') ?? $this->getHeader('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }

        return $this->leadSource->verifyWebhookSignature($this->payload, $signature);
    }
}
