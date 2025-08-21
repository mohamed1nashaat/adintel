<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Message extends Model
{
    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'platform_message_id',
        'type',
        'direction',
        'content',
        'media_urls',
        'metadata',
        'sender_name',
        'sender_id',
        'sent_by',
        'sent_at',
        'delivered_at',
        'read_at',
        'is_automated',
        'language',
        'translated_content',
        'sentiment_score',
        'sentiment',
        'detected_intents',
        'requires_response',
        'response_due_at',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'metadata' => 'array',
        'detected_intents' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'response_due_at' => 'datetime',
        'is_automated' => 'boolean',
        'requires_response' => 'boolean',
        'sentiment_score' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (session('current_tenant_id')) {
                $builder->where('tenant_id', session('current_tenant_id'));
            }
        });

        static::created(function (Message $message) {
            // Update conversation's last message time and count
            $message->conversation->updateLastMessageTime();
            
            // Mark conversation as unread if it's an inbound message
            if ($message->direction === 'inbound') {
                $message->conversation->update(['is_read' => false]);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // Scopes
    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeRequiringResponse(Builder $query): Builder
    {
        return $query->where('requires_response', true);
    }

    public function scopeAutomated(Builder $query): Builder
    {
        return $query->where('is_automated', true);
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('is_automated', false);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }

    public function scopeBySentiment(Builder $query, string $sentiment): Builder
    {
        return $query->where('sentiment', $sentiment);
    }

    public function scopePositiveSentiment(Builder $query): Builder
    {
        return $query->where('sentiment', 'positive');
    }

    public function scopeNegativeSentiment(Builder $query): Builder
    {
        return $query->where('sentiment', 'negative');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('requires_response', true)
                    ->where('response_due_at', '<', now());
    }

    public function scopeRecentlySent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('sent_at', '>=', now()->subHours($hours));
    }

    public function scopeWithMedia(Builder $query): Builder
    {
        return $query->whereNotNull('media_urls')
                    ->whereJsonLength('media_urls', '>', 0);
    }

    // Helper methods
    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function isAutomated(): bool
    {
        return $this->is_automated;
    }

    public function requiresResponse(): bool
    {
        return $this->requires_response;
    }

    public function isOverdue(): bool
    {
        return $this->requires_response && 
               $this->response_due_at && 
               $this->response_due_at->isPast();
    }

    public function hasMedia(): bool
    {
        return !empty($this->media_urls);
    }

    public function isPositive(): bool
    {
        return $this->sentiment === 'positive';
    }

    public function isNegative(): bool
    {
        return $this->sentiment === 'negative';
    }

    public function isNeutral(): bool
    {
        return $this->sentiment === 'neutral';
    }

    public function isArabic(): bool
    {
        return $this->language === 'ar';
    }

    public function markAsRead(): void
    {
        if ($this->isInbound() && $this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function markAsRequiringResponse(int $minutesFromNow = 60): void
    {
        $this->update([
            'requires_response' => true,
            'response_due_at' => now()->addMinutes($minutesFromNow),
        ]);
    }

    public function markAsResponded(): void
    {
        $this->update([
            'requires_response' => false,
            'response_due_at' => null,
        ]);
    }

    public function getDisplayContent(): string
    {
        if ($this->type === 'text') {
            return $this->content ?? '';
        }

        return match($this->type) {
            'image' => '📷 Image',
            'video' => '🎥 Video',
            'audio' => '🎵 Audio',
            'document' => '📄 Document',
            'location' => '📍 Location',
            'contact' => '👤 Contact',
            'sticker' => '😊 Sticker',
            'emoji' => '😀 Emoji',
            default => ucfirst($this->type),
        };
    }

    public function getTranslatedContent(): ?string
    {
        if ($this->isArabic()) {
            return $this->content;
        }

        return $this->translated_content ?? $this->content;
    }

    public function getSentimentColor(): string
    {
        return match($this->sentiment) {
            'positive' => 'green',
            'negative' => 'red',
            'neutral' => 'gray',
            default => 'gray',
        };
    }

    public function getSentimentIcon(): string
    {
        return match($this->sentiment) {
            'positive' => '😊',
            'negative' => '😞',
            'neutral' => '😐',
            default => '❓',
        };
    }

    public function getTypeIcon(): string
    {
        return match($this->type) {
            'text' => '💬',
            'image' => '📷',
            'video' => '🎥',
            'audio' => '🎵',
            'document' => '📄',
            'location' => '📍',
            'contact' => '👤',
            'sticker' => '😊',
            'emoji' => '😀',
            default => '📝',
        };
    }

    public function getFormattedSentTime(): string
    {
        $now = now();
        $sentAt = $this->sent_at;

        if ($sentAt->isToday()) {
            return $sentAt->format('H:i');
        } elseif ($sentAt->isYesterday()) {
            return 'Yesterday ' . $sentAt->format('H:i');
        } elseif ($sentAt->diffInDays($now) < 7) {
            return $sentAt->format('D H:i');
        } else {
            return $sentAt->format('M j, H:i');
        }
    }

    public function getResponseTimeFromPrevious(): ?int
    {
        if ($this->isInbound()) {
            return null;
        }

        $previousInbound = Message::where('conversation_id', $this->conversation_id)
            ->where('direction', 'inbound')
            ->where('sent_at', '<', $this->sent_at)
            ->orderBy('sent_at', 'desc')
            ->first();

        if (!$previousInbound) {
            return null;
        }

        return $this->sent_at->diffInMinutes($previousInbound->sent_at);
    }

    public function getMediaCount(): int
    {
        return count($this->media_urls ?? []);
    }

    public function getFirstMediaUrl(): ?string
    {
        $urls = $this->media_urls ?? [];
        return $urls[0] ?? null;
    }

    public function hasIntent(string $intent): bool
    {
        return in_array($intent, $this->detected_intents ?? []);
    }

    public function getIntents(): array
    {
        return $this->detected_intents ?? [];
    }

    public function addIntent(string $intent): void
    {
        $intents = $this->detected_intents ?? [];
        if (!in_array($intent, $intents)) {
            $intents[] = $intent;
            $this->update(['detected_intents' => $intents]);
        }
    }

    public function removeIntent(string $intent): void
    {
        $intents = $this->detected_intents ?? [];
        $intents = array_filter($intents, fn($i) => $i !== $intent);
        $this->update(['detected_intents' => array_values($intents)]);
    }

    public function setSentiment(string $sentiment, float $score = null): void
    {
        $this->update([
            'sentiment' => $sentiment,
            'sentiment_score' => $score,
        ]);
    }

    public function setTranslation(string $translatedContent, string $language = 'ar'): void
    {
        $this->update([
            'translated_content' => $translatedContent,
            'language' => $language,
        ]);
    }

    public function getDeliveryStatus(): string
    {
        if ($this->isInbound()) {
            return 'received';
        }

        if ($this->read_at) {
            return 'read';
        } elseif ($this->delivered_at) {
            return 'delivered';
        } else {
            return 'sent';
        }
    }

    public function getDeliveryStatusIcon(): string
    {
        return match($this->getDeliveryStatus()) {
            'sent' => '✓',
            'delivered' => '✓✓',
            'read' => '✓✓',
            'received' => '📨',
            default => '⏳',
        };
    }

    public function getDeliveryStatusColor(): string
    {
        return match($this->getDeliveryStatus()) {
            'sent' => 'gray',
            'delivered' => 'blue',
            'read' => 'green',
            'received' => 'blue',
            default => 'gray',
        };
    }

    public function isFromGccNumber(): bool
    {
        if (!$this->sender_id) {
            return false;
        }

        // Check if sender ID looks like a GCC phone number
        $gccPrefixes = ['966', '971', '965', '974', '973', '968']; // SA, AE, KW, QA, BH, OM
        
        foreach ($gccPrefixes as $prefix) {
            if (str_starts_with($this->sender_id, $prefix) || str_starts_with($this->sender_id, '+' . $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function getEstimatedCountry(): ?string
    {
        if (!$this->sender_id) {
            return null;
        }

        $countryPrefixes = [
            '966' => 'SA', // Saudi Arabia
            '971' => 'AE', // UAE
            '965' => 'KW', // Kuwait
            '974' => 'QA', // Qatar
            '973' => 'BH', // Bahrain
            '968' => 'OM', // Oman
            '20' => 'EG',  // Egypt
            '962' => 'JO', // Jordan
            '961' => 'LB', // Lebanon
        ];

        foreach ($countryPrefixes as $prefix => $country) {
            if (str_starts_with($this->sender_id, $prefix) || str_starts_with($this->sender_id, '+' . $prefix)) {
                return $country;
            }
        }

        return null;
    }

    public function shouldAutoTranslate(): bool
    {
        return $this->isInbound() && 
               !$this->isArabic() && 
               empty($this->translated_content) &&
               $this->type === 'text' &&
               !empty($this->content);
    }

    public function getWordCount(): int
    {
        if ($this->type !== 'text' || empty($this->content)) {
            return 0;
        }

        return str_word_count($this->content);
    }

    public function isLongMessage(): bool
    {
        return $this->getWordCount() > 50;
    }

    public function containsKeywords(array $keywords): bool
    {
        if ($this->type !== 'text' || empty($this->content)) {
            return false;
        }

        $content = strtolower($this->content);
        
        foreach ($keywords as $keyword) {
            if (str_contains($content, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    public function getUrgencyScore(): int
    {
        $score = 0;

        // Negative sentiment increases urgency
        if ($this->sentiment === 'negative') {
            $score += 3;
        }

        // Certain intents increase urgency
        $urgentIntents = ['complaint', 'refund', 'cancel', 'urgent', 'problem', 'issue'];
        foreach ($urgentIntents as $intent) {
            if ($this->hasIntent($intent)) {
                $score += 2;
                break;
            }
        }

        // Long messages might be more urgent
        if ($this->isLongMessage()) {
            $score += 1;
        }

        // Multiple media attachments might indicate urgency
        if ($this->getMediaCount() > 1) {
            $score += 1;
        }

        return min($score, 10); // Cap at 10
    }

    public function isUrgent(): bool
    {
        return $this->getUrgencyScore() >= 5;
    }
}
