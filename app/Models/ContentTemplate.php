<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ContentTemplate extends Model
{
    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'description',
        'content_template',
        'placeholders',
        'default_hashtags',
        'suggested_platforms',
        'category',
        'post_type',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'default_hashtags' => 'array',
        'suggested_platforms' => 'array',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
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

    public function contentPosts(): HasMany
    {
        return $this->hasMany(ContentPost::class, 'template_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByPostType(Builder $query, string $postType): Builder
    {
        return $query->where('post_type', $postType);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('usage_count', 'desc');
    }

    // Helper methods
    public function generateContent(array $data = []): string
    {
        $content = $this->content_template;
        
        foreach ($this->placeholders ?? [] as $placeholder) {
            $value = $data[$placeholder] ?? "{{$placeholder}}";
            $content = str_replace("{{$placeholder}}", $value, $content);
        }
        
        return $content;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getAvailablePlaceholders(): array
    {
        return $this->placeholders ?? [];
    }

    public function getDefaultHashtagsString(): string
    {
        return $this->default_hashtags ? implode(' ', array_map(fn($tag) => "#{$tag}", $this->default_hashtags)) : '';
    }

    public function isCompatibleWithPlatform(string $platform): bool
    {
        return in_array($platform, $this->suggested_platforms ?? []);
    }

    public function duplicate(array $overrides = []): self
    {
        $attributes = $this->toArray();
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);
        
        $attributes['name'] = $attributes['name'] . ' (Copy)';
        $attributes['usage_count'] = 0;
        
        return static::create(array_merge($attributes, $overrides));
    }
}
