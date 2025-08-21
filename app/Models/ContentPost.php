<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ContentPost extends Model
{
    protected $fillable = [
        'tenant_id',
        'created_by',
        'title',
        'content',
        'media_urls',
        'platforms',
        'post_type',
        'status',
        'hashtags',
        'mentions',
        'scheduled_at',
        'published_at',
        'template_id',
        'platform_specific_content',
        'notes',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'platforms' => 'array',
        'hashtags' => 'array',
        'mentions' => 'array',
        'platform_specific_content' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContentTemplate::class, 'template_id');
    }

    public function moderation(): HasOne
    {
        return $this->hasOne(ContentModeration::class);
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
                    ->whereNotNull('scheduled_at');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeByPlatform(Builder $query, string $platform): Builder
    {
        return $query->whereJsonContains('platforms', $platform);
    }

    public function scopeByPostType(Builder $query, string $postType): Builder
    {
        return $query->where('post_type', $postType);
    }

    // Helper methods
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at !== null;
    }

    public function isPendingReview(): bool
    {
        return $this->status === 'pending_review';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function getFormattedContentForPlatform(string $platform): string
    {
        $platformContent = $this->platform_specific_content[$platform] ?? null;
        return $platformContent ?: $this->content;
    }

    public function getHashtagsString(): string
    {
        return $this->hashtags ? implode(' ', array_map(fn($tag) => "#{$tag}", $this->hashtags)) : '';
    }

    public function getMentionsString(): string
    {
        return $this->mentions ? implode(' ', array_map(fn($mention) => "@{$mention}", $this->mentions)) : '';
    }
}
