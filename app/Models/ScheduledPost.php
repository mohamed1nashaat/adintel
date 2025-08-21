<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ScheduledPost extends Model
{
    protected $fillable = [
        'tenant_id',
        'content_post_id',
        'created_by',
        'platforms',
        'platform_configs',
        'scheduled_at',
        'status',
        'publish_results',
        'error_message',
        'published_at',
        'retry_count',
        'next_retry_at',
        'preview_data',
        'preview_approved',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'platforms' => 'array',
        'platform_configs' => 'array',
        'publish_results' => 'array',
        'preview_data' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'approved_at' => 'datetime',
        'preview_approved' => 'boolean',
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

    public function contentPost(): BelongsTo
    {
        return $this->belongsTo(ContentPost::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePublishing(Builder $query): Builder
    {
        return $query->where('status', 'publishing');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('scheduled_at', '<=', now())
                    ->where('status', 'scheduled');
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

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('preview_approved', false)
                    ->where('status', 'scheduled');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('preview_approved', true);
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->whereJsonContains('platforms', $platform);
    }

    public function scopeByCreator(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    public function scopeByDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('scheduled_at', [$from, $to]);
    }

    // Helper methods
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isPublishing(): bool
    {
        return $this->status === 'publishing';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isDue(): bool
    {
        return $this->scheduled_at <= now() && $this->isScheduled();
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

    public function needsApproval(): bool
    {
        return !$this->preview_approved && $this->isScheduled();
    }

    public function isApproved(): bool
    {
        return $this->preview_approved;
    }

    public function hasPlatform(string $platform): bool
    {
        return in_array($platform, $this->platforms ?? []);
    }

    public function markAsPublishing(): void
    {
        $this->update([
            'status' => 'publishing',
        ]);
    }

    public function markAsPublished(array $results = []): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'publish_results' => $results,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    public function approve(User $user, string $notes = null): void
    {
        $this->update([
            'preview_approved' => true,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
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
            'status' => 'scheduled',
        ]);
    }

    public function reschedule(\DateTime $newDateTime): void
    {
        $this->update([
            'scheduled_at' => $newDateTime,
            'status' => 'scheduled',
        ]);
    }

    public function getPlatformConfig(string $platform): array
    {
        return $this->platform_configs[$platform] ?? [];
    }

    public function setPlatformConfig(string $platform, array $config): void
    {
        $configs = $this->platform_configs ?? [];
        $configs[$platform] = $config;
        $this->update(['platform_configs' => $configs]);
    }

    public function getPublishResult(string $platform): array
    {
        return $this->publish_results[$platform] ?? [];
    }

    public function setPublishResult(string $platform, array $result): void
    {
        $results = $this->publish_results ?? [];
        $results[$platform] = $result;
        $this->update(['publish_results' => $results]);
    }

    public function getSuccessfulPlatforms(): array
    {
        if (!$this->publish_results) {
            return [];
        }

        return collect($this->publish_results)
            ->filter(fn($result) => ($result['success'] ?? false))
            ->keys()
            ->toArray();
    }

    public function getFailedPlatforms(): array
    {
        if (!$this->publish_results) {
            return [];
        }

        return collect($this->publish_results)
            ->filter(fn($result) => !($result['success'] ?? true))
            ->keys()
            ->toArray();
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'scheduled' => 'blue',
            'publishing' => 'yellow',
            'published' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'scheduled' => 'Scheduled',
            'publishing' => 'Publishing',
            'published' => 'Published',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function generatePreview(): array
    {
        $preview = [
            'content' => $this->contentPost->content,
            'media' => $this->contentPost->media_urls ?? [],
            'platforms' => [],
        ];

        foreach ($this->platforms as $platform) {
            $config = $this->getPlatformConfig($platform);
            $platformContent = $this->contentPost->platform_specific_content[$platform] ?? $this->contentPost->content;
            
            $preview['platforms'][$platform] = [
                'content' => $platformContent,
                'hashtags' => $this->contentPost->hashtags ?? [],
                'mentions' => $this->contentPost->mentions ?? [],
                'config' => $config,
                'character_count' => strlen($platformContent),
                'estimated_reach' => $this->estimateReach($platform),
            ];
        }

        $this->update(['preview_data' => $preview]);
        
        return $preview;
    }

    private function estimateReach(string $platform): int
    {
        // Simple reach estimation based on platform
        $baseReach = match($platform) {
            'facebook' => rand(100, 1000),
            'instagram' => rand(200, 1500),
            'twitter' => rand(50, 500),
            'linkedin' => rand(25, 250),
            'tiktok' => rand(500, 5000),
            default => rand(100, 500),
        };

        return $baseReach;
    }

    public function duplicate(array $overrides = []): self
    {
        $attributes = $this->toArray();
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);
        
        $attributes['status'] = 'scheduled';
        $attributes['published_at'] = null;
        $attributes['publish_results'] = null;
        $attributes['error_message'] = null;
        $attributes['retry_count'] = 0;
        $attributes['next_retry_at'] = null;
        $attributes['preview_approved'] = false;
        $attributes['approved_by'] = null;
        $attributes['approved_at'] = null;
        $attributes['approval_notes'] = null;
        
        return static::create(array_merge($attributes, $overrides));
    }
}
