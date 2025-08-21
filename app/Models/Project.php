<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'status',
        'type',
        'settings',
        'start_date',
        'end_date',
        'budget',
        'currency',
        'target_audience',
        'kpis',
        'platforms',
        'industry',
        'region',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'target_audience' => 'array',
        'kpis' => 'array',
        'platforms' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name) . '-' . Str::random(6);
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot('role', 'permissions', 'invited_at', 'joined_at', 'status', 'invited_by')
            ->withTimestamps();
    }

    public function contentPosts(): HasMany
    {
        return $this->hasMany(ContentPost::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(ScheduledPost::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function adCampaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOwner(User $user): bool
    {
        return $this->users()->wherePivot('user_id', $user->id)->wherePivot('role', 'owner')->exists();
    }

    public function hasUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    public function getUserRole(User $user): ?string
    {
        $projectUser = $this->users()->where('user_id', $user->id)->first();
        return $projectUser?->pivot->role;
    }

    public function canUserEdit(User $user): bool
    {
        $role = $this->getUserRole($user);
        return in_array($role, ['owner', 'admin', 'manager', 'editor']);
    }

    public function canUserView(User $user): bool
    {
        return $this->hasUser($user);
    }

    public function getProgressPercentage(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        $elapsedDays = $this->start_date->diffInDays(now());

        if ($totalDays <= 0) {
            return 100;
        }

        return min(100, max(0, ($elapsedDays / $totalDays) * 100));
    }

    public function getBudgetSpent(): float
    {
        // This would calculate actual spend from ad campaigns
        return $this->adCampaigns()->sum('spend') ?? 0;
    }

    public function getBudgetRemaining(): float
    {
        return max(0, $this->budget - $this->getBudgetSpent());
    }

    public function getKpiValue(string $kpi): mixed
    {
        // This would calculate actual KPI values from campaigns and metrics
        switch ($kpi) {
            case 'leads':
                return $this->leads()->count();
            case 'posts':
                return $this->contentPosts()->where('status', 'published')->count();
            case 'reach':
                return $this->adCampaigns()->sum('reach') ?? 0;
            case 'impressions':
                return $this->adCampaigns()->sum('impressions') ?? 0;
            default:
                return 0;
        }
    }
}
