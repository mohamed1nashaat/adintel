<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ContentModeration extends Model
{
    protected $fillable = [
        'tenant_id',
        'content_post_id',
        'reviewer_id',
        'status',
        'feedback',
        'suggested_changes',
        'priority',
        'reviewed_at',
        'compliance_flags',
        'ai_confidence_score',
        'ai_suggestions',
        'requires_legal_review',
        'requires_brand_review',
    ];

    protected $casts = [
        'suggested_changes' => 'array',
        'compliance_flags' => 'array',
        'ai_suggestions' => 'array',
        'ai_confidence_score' => 'decimal:2',
        'requires_legal_review' => 'boolean',
        'requires_brand_review' => 'boolean',
        'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeRequiresLegalReview(Builder $query): Builder
    {
        return $query->where('requires_legal_review', true);
    }

    public function scopeRequiresBrandReview(Builder $query): Builder
    {
        return $query->where('requires_brand_review', true);
    }

    public function scopeByReviewer(Builder $query, int $reviewerId): Builder
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function needsRevision(): bool
    {
        return $this->status === 'needs_revision';
    }

    public function approve(User $reviewer, string $feedback = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewer_id' => $reviewer->id,
            'feedback' => $feedback,
            'reviewed_at' => now(),
        ]);

        // Update the content post status
        $this->contentPost->update(['status' => 'approved']);
    }

    public function reject(User $reviewer, string $feedback, array $suggestedChanges = []): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewer_id' => $reviewer->id,
            'feedback' => $feedback,
            'suggested_changes' => $suggestedChanges,
            'reviewed_at' => now(),
        ]);

        // Update the content post status
        $this->contentPost->update(['status' => 'rejected']);
    }

    public function requestRevision(User $reviewer, string $feedback, array $suggestedChanges = []): void
    {
        $this->update([
            'status' => 'needs_revision',
            'reviewer_id' => $reviewer->id,
            'feedback' => $feedback,
            'suggested_changes' => $suggestedChanges,
            'reviewed_at' => now(),
        ]);

        // Update the content post status
        $this->contentPost->update(['status' => 'needs_revision']);
    }

    public function hasComplianceIssues(): bool
    {
        return !empty($this->compliance_flags);
    }

    public function getComplianceIssuesCount(): int
    {
        return count($this->compliance_flags ?? []);
    }

    public function isHighConfidenceAI(): bool
    {
        return $this->ai_confidence_score && $this->ai_confidence_score >= 0.8;
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'approved' => 'green',
            'rejected' => 'red',
            'needs_revision' => 'yellow',
            'pending' => 'blue',
            default => 'gray',
        };
    }
}
