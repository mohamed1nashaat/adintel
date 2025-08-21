<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Dashboard extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'objective',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class)->orderBy('position');
    }

    public function scopeForObjective(Builder $query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function setAsDefault(): void
    {
        // Remove default from other dashboards for this user and objective
        static::where('user_id', $this->user_id)
            ->where('objective', $this->objective)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function addWidget(string $type, array $config = [], int $position = null): DashboardWidget
    {
        if ($position === null) {
            $position = $this->widgets()->max('position') + 1;
        }

        return $this->widgets()->create([
            'type' => $type,
            'position' => $position,
            'config' => $config,
        ]);
    }

    public function getObjectiveKpis(): array
    {
        return match ($this->objective) {
            'awareness' => ['cpm', 'reach', 'vtr', 'ctr'],
            'leads' => ['cpl', 'cvr', 'ctr', 'cpc'],
            'sales' => ['roas', 'cpa', 'aov', 'revenue'],
            'calls' => ['cost_per_call', 'calls', 'ctr'],
            default => [],
        };
    }
}
