<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class LeadSource extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'type',
        'webhook_url',
        'webhook_secret',
        'form_fields',
        'mapping_config',
        'google_sheet_id',
        'google_sheet_name',
        'auto_sync_sheets',
        'notification_settings',
        'is_active',
        'leads_count',
        'last_lead_at',
    ];

    protected $casts = [
        'form_fields' => 'array',
        'mapping_config' => 'array',
        'notification_settings' => 'array',
        'auto_sync_sheets' => 'boolean',
        'is_active' => 'boolean',
        'leads_count' => 'integer',
        'last_lead_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (session('current_tenant_id')) {
                $builder->where('tenant_id', session('current_tenant_id'));
            }
        });

        static::creating(function ($leadSource) {
            if (empty($leadSource->slug)) {
                $leadSource->slug = Str::slug($leadSource->name) . '-' . Str::random(6);
            }
            
            if (empty($leadSource->webhook_secret)) {
                $leadSource->webhook_secret = Str::random(32);
            }
            
            if (empty($leadSource->webhook_url)) {
                $leadSource->webhook_url = route('webhooks.leads', ['slug' => $leadSource->slug]);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeWithGoogleSheets(Builder $query): Builder
    {
        return $query->whereNotNull('google_sheet_id');
    }

    public function scopeAutoSync(Builder $query): Builder
    {
        return $query->where('auto_sync_sheets', true);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function hasGoogleSheets(): bool
    {
        return !empty($this->google_sheet_id);
    }

    public function shouldAutoSync(): bool
    {
        return $this->auto_sync_sheets && $this->hasGoogleSheets();
    }

    public function incrementLeadsCount(): void
    {
        $this->increment('leads_count');
        $this->update(['last_lead_at' => now()]);
    }

    public function getWebhookUrl(): string
    {
        return $this->webhook_url ?: route('webhooks.leads', ['slug' => $this->slug]);
    }

    public function generateNewWebhookSecret(): string
    {
        $secret = Str::random(32);
        $this->update(['webhook_secret' => $secret]);
        return $secret;
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($expectedSignature, $signature);
    }

    public function getFormFieldsConfig(): array
    {
        return $this->form_fields ?? [
            'first_name' => ['required' => true, 'type' => 'text'],
            'last_name' => ['required' => true, 'type' => 'text'],
            'email' => ['required' => true, 'type' => 'email'],
            'phone' => ['required' => false, 'type' => 'tel'],
            'company' => ['required' => false, 'type' => 'text'],
            'message' => ['required' => false, 'type' => 'textarea'],
        ];
    }

    public function getMappingConfig(): array
    {
        return $this->mapping_config ?? [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email' => 'email',
            'phone' => 'phone',
            'company' => 'company',
            'message' => 'message',
        ];
    }

    public function mapWebhookData(array $data): array
    {
        $mapping = $this->getMappingConfig();
        $mapped = [];

        foreach ($mapping as $leadField => $webhookField) {
            if (isset($data[$webhookField])) {
                $mapped[$leadField] = $data[$webhookField];
            }
        }

        return $mapped;
    }

    public function getNotificationSettings(): array
    {
        return $this->notification_settings ?? [
            'email' => ['enabled' => false, 'recipients' => []],
            'slack' => ['enabled' => false, 'webhook_url' => ''],
        ];
    }

    public function shouldNotifyEmail(): bool
    {
        $settings = $this->getNotificationSettings();
        return $settings['email']['enabled'] ?? false;
    }

    public function shouldNotifySlack(): bool
    {
        $settings = $this->getNotificationSettings();
        return $settings['slack']['enabled'] ?? false;
    }

    public function getEmailRecipients(): array
    {
        $settings = $this->getNotificationSettings();
        return $settings['email']['recipients'] ?? [];
    }

    public function getSlackWebhookUrl(): ?string
    {
        $settings = $this->getNotificationSettings();
        return $settings['slack']['webhook_url'] ?? null;
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'form' => 'Contact Form',
            'webhook' => 'Webhook',
            'api' => 'API Integration',
            'manual' => 'Manual Entry',
            'import' => 'Data Import',
            default => ucfirst($this->type),
        };
    }

    public function getStatusColor(): string
    {
        return $this->is_active ? 'green' : 'red';
    }

    public function getRecentLeads(int $limit = 10)
    {
        return $this->leads()
            ->with('assignedUser')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getLeadsStats(): array
    {
        return [
            'total' => $this->leads_count,
            'new' => $this->leads()->new()->count(),
            'converted' => $this->leads()->converted()->count(),
            'hot' => $this->leads()->hot()->count(),
            'this_month' => $this->leads()->whereMonth('created_at', now()->month)->count(),
            'this_week' => $this->leads()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'today' => $this->leads()->whereDate('created_at', now())->count(),
        ];
    }

    public function duplicate(array $overrides = []): self
    {
        $attributes = $this->toArray();
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);
        
        $attributes['name'] = $attributes['name'] . ' (Copy)';
        $attributes['slug'] = Str::slug($attributes['name']) . '-' . Str::random(6);
        $attributes['webhook_secret'] = Str::random(32);
        $attributes['leads_count'] = 0;
        $attributes['last_lead_at'] = null;
        
        return static::create(array_merge($attributes, $overrides));
    }
}
