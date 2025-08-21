<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Integration extends Model
{
    protected $fillable = [
        'tenant_id',
        'platform',
        'app_config',
        'created_by',
        'status',
    ];

    protected $casts = [
        'app_config' => 'array',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getCredentials(): array
    {
        return $this->app_config ?? [];
    }

    public function updateCredentials(array $credentials): void
    {
        $this->update(['app_config' => $credentials]);
    }
}
