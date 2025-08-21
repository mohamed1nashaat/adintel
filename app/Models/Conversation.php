<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Conversation extends Model
{
    protected $fillable = [
        'tenant_id',
        'platform',
        'platform_conversation_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_metadata',
        'status',
        'priority',
        'assigned_to',
        'tags',
        'language',
        'country_code',
        'last_message_at',
        'first_response_at',
        'resolved_at',
        'message_count',
        'is_read',
        'notes',
    ];

    protected $casts = [
        'customer_metadata' => 'array',
        'tags' => 'array',
        'last_message_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'is_read' => 'boolean',
        'message_count' => 'integer',
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

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // Scopes
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeByLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }

    public function scopeByCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeGccCountries(Builder $query): Builder
    {
        return $query->whereIn('country_code', ['SA', 'AE', 'KW', 'QA', 'BH', 'OM']);
    }

    public function scopeRequiringResponse(Builder $query): Builder
    {
        return $query->whereHas('messages', function ($q) {
            $q->where('requires_response', true)
              ->where('direction', 'inbound');
        });
    }

    public function scopeRecentActivity(Builder $query, int $hours = 24): Builder
    {
        return $query->where('last_message_at', '>=', now()->subHours($hours));
    }

    public function scopeWithTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('tags', $tag);
    }

    // Helper methods
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    public function isAssigned(): bool
    {
        return !is_null($this->assigned_to);
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['high', 'urgent']);
    }

    public function isGccCustomer(): bool
    {
        return in_array($this->country_code, ['SA', 'AE', 'KW', 'QA', 'BH', 'OM']);
    }

    public function isArabicLanguage(): bool
    {
        return $this->language === 'ar';
    }

    public function hasWhatsApp(): bool
    {
        return $this->platform === 'whatsapp';
    }

    public function getLastMessage(): ?Message
    {
        return $this->messages()->latest('sent_at')->first();
    }

    public function getFirstMessage(): ?Message
    {
        return $this->messages()->oldest('sent_at')->first();
    }

    public function getUnreadMessagesCount(): int
    {
        return $this->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->count();
    }

    public function getResponseTime(): ?int
    {
        if (!$this->first_response_at) {
            return null;
        }

        $firstMessage = $this->getFirstMessage();
        if (!$firstMessage) {
            return null;
        }

        return $this->first_response_at->diffInMinutes($firstMessage->sent_at);
    }

    public function getAverageResponseTime(): ?float
    {
        $responses = $this->messages()
            ->where('direction', 'outbound')
            ->whereNotNull('sent_at')
            ->get();

        if ($responses->isEmpty()) {
            return null;
        }

        $totalTime = 0;
        $responseCount = 0;

        foreach ($responses as $response) {
            $previousInbound = $this->messages()
                ->where('direction', 'inbound')
                ->where('sent_at', '<', $response->sent_at)
                ->latest('sent_at')
                ->first();

            if ($previousInbound) {
                $totalTime += $response->sent_at->diffInMinutes($previousInbound->sent_at);
                $responseCount++;
            }
        }

        return $responseCount > 0 ? $totalTime / $responseCount : null;
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
        
        // Mark all inbound messages as read
        $this->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function assignTo(User $user): void
    {
        $this->update(['assigned_to' => $user->id]);
    }

    public function unassign(): void
    {
        $this->update(['assigned_to' => null]);
    }

    public function resolve(string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'notes' => $notes ? ($this->notes ? $this->notes . "\n\n" . $notes : $notes) : $this->notes,
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
        ]);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_filter($tags, fn($t) => $t !== $tag);
        $this->update(['tags' => array_values($tags)]);
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    public function updateLastMessageTime(): void
    {
        $lastMessage = $this->getLastMessage();
        if ($lastMessage) {
            $this->update([
                'last_message_at' => $lastMessage->sent_at,
                'message_count' => $this->messages()->count(),
            ]);
        }
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'open' => 'green',
            'pending' => 'yellow',
            'resolved' => 'blue',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'open' => 'Open',
            'pending' => 'Pending',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            default => ucfirst($this->status),
        };
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

    public function getPriorityLabel(): string
    {
        return match($this->priority) {
            'urgent' => 'Urgent',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            default => ucfirst($this->priority),
        };
    }

    public function getPlatformLabel(): string
    {
        return match($this->platform) {
            'whatsapp' => 'WhatsApp',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'twitter' => 'Twitter',
            'telegram' => 'Telegram',
            'linkedin' => 'LinkedIn',
            default => ucfirst($this->platform),
        };
    }

    public function getCountryLabel(): string
    {
        $countries = [
            'SA' => 'Saudi Arabia',
            'AE' => 'UAE',
            'KW' => 'Kuwait',
            'QA' => 'Qatar',
            'BH' => 'Bahrain',
            'OM' => 'Oman',
            'EG' => 'Egypt',
            'JO' => 'Jordan',
            'LB' => 'Lebanon',
            'MA' => 'Morocco',
            'TN' => 'Tunisia',
            'DZ' => 'Algeria',
        ];

        return $countries[$this->country_code] ?? $this->country_code;
    }

    public function getLanguageLabel(): string
    {
        $languages = [
            'ar' => 'العربية',
            'en' => 'English',
            'fr' => 'Français',
            'ur' => 'اردو',
            'hi' => 'हिन्दी',
            'bn' => 'বাংলা',
            'ta' => 'தமிழ்',
            'ml' => 'മലയാളം',
        ];

        return $languages[$this->language] ?? $this->language;
    }

    public function getCustomerDisplayName(): string
    {
        if ($this->customer_name) {
            return $this->customer_name;
        }

        if ($this->customer_phone) {
            return $this->customer_phone;
        }

        if ($this->customer_email) {
            return $this->customer_email;
        }

        return 'Unknown Customer';
    }

    public function getFormattedPhone(): ?string
    {
        if (!$this->customer_phone) {
            return null;
        }

        // Format phone numbers for GCC countries
        $phone = preg_replace('/[^0-9+]/', '', $this->customer_phone);
        
        if (str_starts_with($phone, '966')) { // Saudi Arabia
            return '+966 ' . substr($phone, 3, 2) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8);
        } elseif (str_starts_with($phone, '971')) { // UAE
            return '+971 ' . substr($phone, 3, 2) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8);
        } elseif (str_starts_with($phone, '965')) { // Kuwait
            return '+965 ' . substr($phone, 3, 4) . ' ' . substr($phone, 7);
        }

        return $phone;
    }

    public function requiresUrgentResponse(): bool
    {
        if ($this->priority === 'urgent') {
            return true;
        }

        // Check if last message was more than 2 hours ago for high priority
        if ($this->priority === 'high' && $this->last_message_at) {
            return $this->last_message_at->diffInHours(now()) > 2;
        }

        // Check if there are unread messages requiring response
        return $this->messages()
            ->where('direction', 'inbound')
            ->where('requires_response', true)
            ->whereNull('read_at')
            ->exists();
    }

    public function getBusinessHoursStatus(): string
    {
        $now = now();
        
        // GCC business hours (Sunday to Thursday, 9 AM to 6 PM)
        $dayOfWeek = $now->dayOfWeek; // 0 = Sunday, 6 = Saturday
        $hour = $now->hour;
        
        if ($dayOfWeek >= 0 && $dayOfWeek <= 4 && $hour >= 9 && $hour < 18) {
            return 'business_hours';
        } elseif ($dayOfWeek == 5) { // Friday
            return 'weekend';
        } elseif ($dayOfWeek == 6) { // Saturday
            return 'weekend';
        } else {
            return 'after_hours';
        }
    }

    public function getSlaStatus(): array
    {
        $responseTime = $this->getResponseTime();
        $businessHours = $this->getBusinessHoursStatus();
        
        // SLA targets for GCC market
        $slaTargets = [
            'urgent' => ['business_hours' => 15, 'after_hours' => 60], // minutes
            'high' => ['business_hours' => 60, 'after_hours' => 240],
            'medium' => ['business_hours' => 240, 'after_hours' => 480],
            'low' => ['business_hours' => 480, 'after_hours' => 1440],
        ];
        
        $target = $slaTargets[$this->priority][$businessHours] ?? $slaTargets['medium'][$businessHours];
        
        if ($responseTime === null) {
            $firstMessage = $this->getFirstMessage();
            if ($firstMessage && $firstMessage->direction === 'inbound') {
                $elapsed = $firstMessage->sent_at->diffInMinutes(now());
                return [
                    'status' => $elapsed > $target ? 'breached' : 'within_sla',
                    'target_minutes' => $target,
                    'elapsed_minutes' => $elapsed,
                    'remaining_minutes' => max(0, $target - $elapsed),
                ];
            }
        }
        
        return [
            'status' => $responseTime && $responseTime > $target ? 'breached' : 'met',
            'target_minutes' => $target,
            'actual_minutes' => $responseTime,
            'remaining_minutes' => 0,
        ];
    }
}
