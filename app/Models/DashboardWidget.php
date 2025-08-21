<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
    protected $fillable = [
        'dashboard_id',
        'type',
        'position',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'position' => 'integer',
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function getConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config ?? [];
        }

        return data_get($this->config, $key, $default);
    }

    public function updateConfig(array $config): void
    {
        $this->update(['config' => array_merge($this->config ?? [], $config)]);
    }
}
