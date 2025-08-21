<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $accessToken;
    private string $phoneNumberId;
    private string $webhookVerifyToken;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->webhookVerifyToken = config('services.whatsapp.webhook_verify_token');
    }

    public function sendMessage(string $to, string $message, array $options = []): array
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            // Add Arabic language support
            if ($this->isArabicText($message)) {
                $payload['text']['preview_url'] = false;
            }

            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Unknown error',
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp send message error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function sendTemplate(string $to, string $templateName, array $parameters = [], string $language = 'ar'): array
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $language
                    ]
                ]
            ];

            if (!empty($parameters)) {
                $payload['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => array_map(function ($param) {
                            return ['type' => 'text', 'text' => $param];
                        }, $parameters)
                    ]
                ];
            }

            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Unknown error',
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp send template error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function sendMedia(string $to, string $mediaType, string $mediaUrl, string $caption = null): array
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => $mediaType,
                $mediaType => [
                    'link' => $mediaUrl
                ]
            ];

            if ($caption && in_array($mediaType, ['image', 'video', 'document'])) {
                $payload[$mediaType]['caption'] = $caption;
            }

            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Unknown error',
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp send media error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function handleWebhook(array $payload): array
    {
        try {
            $results = [];

            if (isset($payload['entry'])) {
                foreach ($payload['entry'] as $entry) {
                    if (isset($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            if ($change['field'] === 'messages') {
                                $result = $this->processMessageChange($change['value']);
                                $results[] = $result;
                            }
                        }
                    }
                }
            }

            return [
                'success' => true,
                'processed' => count($results),
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function processMessageChange(array $value): array
    {
        $results = [];

        // Process incoming messages
        if (isset($value['messages'])) {
            foreach ($value['messages'] as $messageData) {
                $result = $this->processIncomingMessage($messageData, $value['metadata']);
                $results[] = $result;
            }
        }

        // Process message statuses (delivered, read, etc.)
        if (isset($value['statuses'])) {
            foreach ($value['statuses'] as $statusData) {
                $result = $this->processMessageStatus($statusData);
                $results[] = $result;
            }
        }

        return $results;
    }

    private function processIncomingMessage(array $messageData, array $metadata): array
    {
        try {
            $phoneNumber = $messageData['from'];
            $messageId = $messageData['id'];
            $timestamp = $messageData['timestamp'];

            // Find or create conversation
            $conversation = $this->findOrCreateConversation($phoneNumber, $metadata);

            // Determine message type and content
            $messageType = $this->getMessageType($messageData);
            $content = $this->extractMessageContent($messageData, $messageType);

            // Detect language (prioritize Arabic for GCC)
            $language = $this->detectLanguage($content['text'] ?? '');

            // Create message record
            $message = Message::create([
                'tenant_id' => $conversation->tenant_id,
                'conversation_id' => $conversation->id,
                'platform_message_id' => $messageId,
                'type' => $messageType,
                'direction' => 'inbound',
                'content' => $content['text'] ?? null,
                'media_urls' => $content['media_urls'] ?? null,
                'metadata' => $messageData,
                'sender_name' => $messageData['profile']['name'] ?? null,
                'sender_id' => $phoneNumber,
                'sent_at' => now()->createFromTimestamp($timestamp),
                'language' => $language,
                'requires_response' => $this->shouldRequireResponse($content['text'] ?? '', $messageType),
                'response_due_at' => $this->calculateResponseDueTime($conversation->priority),
            ]);

            // Analyze sentiment and intents for text messages
            if ($messageType === 'text' && !empty($content['text'])) {
                $this->analyzeSentiment($message);
                $this->detectIntents($message);
            }

            // Auto-translate if needed
            if ($language !== 'ar' && $messageType === 'text') {
                $this->translateMessage($message);
            }

            // Update conversation
            $conversation->updateLastMessageTime();

            return [
                'success' => true,
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'type' => 'message_received'
            ];

        } catch (\Exception $e) {
            Log::error('Process incoming message error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'type' => 'message_error'
            ];
        }
    }

    private function processMessageStatus(array $statusData): array
    {
        try {
            $messageId = $statusData['id'];
            $status = $statusData['status'];
            $timestamp = $statusData['timestamp'];

            // Find the message
            $message = Message::where('platform_message_id', $messageId)->first();

            if (!$message) {
                return [
                    'success' => false,
                    'error' => 'Message not found',
                    'message_id' => $messageId
                ];
            }

            // Update message status
            switch ($status) {
                case 'delivered':
                    $message->update(['delivered_at' => now()->createFromTimestamp($timestamp)]);
                    break;
                case 'read':
                    $message->update(['read_at' => now()->createFromTimestamp($timestamp)]);
                    break;
            }

            return [
                'success' => true,
                'message_id' => $message->id,
                'status' => $status,
                'type' => 'status_update'
            ];

        } catch (\Exception $e) {
            Log::error('Process message status error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'type' => 'status_error'
            ];
        }
    }

    private function findOrCreateConversation(string $phoneNumber, array $metadata): Conversation
    {
        $platformConversationId = "whatsapp_{$phoneNumber}";

        $conversation = Conversation::where('platform_conversation_id', $platformConversationId)->first();

        if (!$conversation) {
            // Detect country from phone number
            $countryCode = $this->detectCountryFromPhone($phoneNumber);
            
            $conversation = Conversation::create([
                'tenant_id' => session('current_tenant_id'),
                'platform' => 'whatsapp',
                'platform_conversation_id' => $platformConversationId,
                'customer_phone' => $phoneNumber,
                'customer_metadata' => $metadata,
                'status' => 'open',
                'priority' => $this->determinePriority($phoneNumber, $countryCode),
                'language' => $countryCode === 'SA' ? 'ar' : 'en', // Default to Arabic for Saudi
                'country_code' => $countryCode,
                'last_message_at' => now(),
            ]);
        }

        return $conversation;
    }

    private function getMessageType(array $messageData): string
    {
        if (isset($messageData['text'])) return 'text';
        if (isset($messageData['image'])) return 'image';
        if (isset($messageData['video'])) return 'video';
        if (isset($messageData['audio'])) return 'audio';
        if (isset($messageData['voice'])) return 'audio';
        if (isset($messageData['document'])) return 'document';
        if (isset($messageData['location'])) return 'location';
        if (isset($messageData['contacts'])) return 'contact';
        if (isset($messageData['sticker'])) return 'sticker';

        return 'text';
    }

    private function extractMessageContent(array $messageData, string $type): array
    {
        $content = [];

        switch ($type) {
            case 'text':
                $content['text'] = $messageData['text']['body'] ?? '';
                break;
            
            case 'image':
                $content['media_urls'] = [$messageData['image']['id']];
                $content['text'] = $messageData['image']['caption'] ?? null;
                break;
            
            case 'video':
                $content['media_urls'] = [$messageData['video']['id']];
                $content['text'] = $messageData['video']['caption'] ?? null;
                break;
            
            case 'audio':
                $content['media_urls'] = [$messageData['audio']['id'] ?? $messageData['voice']['id']];
                break;
            
            case 'document':
                $content['media_urls'] = [$messageData['document']['id']];
                $content['text'] = $messageData['document']['caption'] ?? $messageData['document']['filename'] ?? null;
                break;
            
            case 'location':
                $location = $messageData['location'];
                $content['text'] = "Location: {$location['latitude']}, {$location['longitude']}";
                if (isset($location['name'])) {
                    $content['text'] .= " ({$location['name']})";
                }
                break;
            
            case 'contact':
                $contact = $messageData['contacts'][0] ?? [];
                $content['text'] = "Contact: " . ($contact['name']['formatted_name'] ?? 'Unknown');
                break;
        }

        return $content;
    }

    private function detectLanguage(string $text): string
    {
        if (empty($text)) {
            return 'ar'; // Default to Arabic for GCC
        }

        // Simple Arabic detection
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ar';
        }

        // Check for common Arabic words in Latin script
        $arabicWords = ['salam', 'ahlan', 'marhaba', 'shukran', 'maasalama'];
        $lowerText = strtolower($text);
        
        foreach ($arabicWords as $word) {
            if (str_contains($lowerText, $word)) {
                return 'ar';
            }
        }

        return 'en';
    }

    private function detectCountryFromPhone(string $phoneNumber): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // GCC country codes
        $countryCodes = [
            '966' => 'SA', // Saudi Arabia
            '971' => 'AE', // UAE
            '965' => 'KW', // Kuwait
            '974' => 'QA', // Qatar
            '973' => 'BH', // Bahrain
            '968' => 'OM', // Oman
        ];

        foreach ($countryCodes as $code => $country) {
            if (str_starts_with($phone, $code)) {
                return $country;
            }
        }

        return 'SA'; // Default to Saudi Arabia
    }

    private function determinePriority(string $phoneNumber, string $countryCode): string
    {
        // VIP numbers or premium customers could have higher priority
        // For now, default priority based on country
        return match($countryCode) {
            'SA' => 'high',    // Saudi Arabia - high priority
            'AE' => 'high',    // UAE - high priority
            'QA' => 'high',    // Qatar - high priority
            'KW' => 'medium',  // Kuwait - medium priority
            'BH' => 'medium',  // Bahrain - medium priority
            'OM' => 'medium',  // Oman - medium priority
            default => 'medium'
        };
    }

    private function shouldRequireResponse(string $text, string $type): bool
    {
        if ($type !== 'text' || empty($text)) {
            return $type !== 'sticker'; // Most non-text messages require response except stickers
        }

        // Keywords that typically require response
        $responseKeywords = [
            // Arabic
            'مساعدة', 'سؤال', 'مشكلة', 'شكوى', 'استفسار', 'طلب', 'عاجل',
            // English
            'help', 'question', 'problem', 'issue', 'complaint', 'urgent', 'request',
            // Common phrases
            '?', 'how', 'what', 'when', 'where', 'why', 'can you', 'please'
        ];

        $lowerText = strtolower($text);
        
        foreach ($responseKeywords as $keyword) {
            if (str_contains($lowerText, strtolower($keyword))) {
                return true;
            }
        }

        // Questions typically end with question marks
        if (str_contains($text, '?') || str_contains($text, '؟')) {
            return true;
        }

        return false;
    }

    private function calculateResponseDueTime(string $priority): ?\Carbon\Carbon
    {
        $minutes = match($priority) {
            'urgent' => 15,
            'high' => 60,
            'medium' => 240,
            'low' => 480,
            default => 240
        };

        return now()->addMinutes($minutes);
    }

    private function analyzeSentiment(Message $message): void
    {
        // Simple sentiment analysis for Arabic and English
        $text = strtolower($message->content);
        
        $positiveWords = [
            // Arabic
            'شكرا', 'ممتاز', 'رائع', 'جيد', 'حب', 'سعيد', 'راضي',
            // English
            'thank', 'great', 'good', 'excellent', 'love', 'happy', 'satisfied'
        ];
        
        $negativeWords = [
            // Arabic
            'سيء', 'مشكلة', 'شكوى', 'غاضب', 'زعلان', 'مستاء', 'رديء',
            // English
            'bad', 'problem', 'issue', 'angry', 'upset', 'terrible', 'awful'
        ];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($text, $word)) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($text, $word)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            $sentiment = 'positive';
            $score = min(0.8, 0.5 + ($positiveCount * 0.1));
        } elseif ($negativeCount > $positiveCount) {
            $sentiment = 'negative';
            $score = max(-0.8, -0.5 - ($negativeCount * 0.1));
        } else {
            $sentiment = 'neutral';
            $score = 0.0;
        }

        $message->setSentiment($sentiment, $score);
    }

    private function detectIntents(Message $message): void
    {
        $text = strtolower($message->content);
        $intents = [];

        $intentKeywords = [
            'support' => ['مساعدة', 'help', 'support', 'assist'],
            'complaint' => ['شكوى', 'مشكلة', 'complaint', 'problem', 'issue'],
            'inquiry' => ['استفسار', 'سؤال', 'question', 'inquiry', 'ask'],
            'order' => ['طلب', 'order', 'buy', 'purchase', 'شراء'],
            'cancel' => ['إلغاء', 'cancel', 'stop', 'quit'],
            'refund' => ['استرداد', 'refund', 'return', 'money back'],
            'praise' => ['شكر', 'praise', 'compliment', 'thank'],
        ];

        foreach ($intentKeywords as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $intents[] = $intent;
                    break;
                }
            }
        }

        if (!empty($intents)) {
            $message->update(['detected_intents' => array_unique($intents)]);
        }
    }

    private function translateMessage(Message $message): void
    {
        // This would integrate with Google Translate API or similar
        // For now, we'll just mark it as needing translation
        if ($message->shouldAutoTranslate()) {
            // Placeholder for translation service
            $translatedText = $this->translateText($message->content, 'ar');
            if ($translatedText) {
                $message->setTranslation($translatedText, 'ar');
            }
        }
    }

    private function translateText(string $text, string $targetLanguage): ?string
    {
        // Placeholder for actual translation service integration
        // This would call Google Translate API or similar service
        return null;
    }

    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add country code if missing
        if (!str_starts_with($phone, '966') && !str_starts_with($phone, '971') && 
            !str_starts_with($phone, '965') && !str_starts_with($phone, '974') && 
            !str_starts_with($phone, '973') && !str_starts_with($phone, '968')) {
            
            // Default to Saudi Arabia if no country code
            if (strlen($phone) === 9 && str_starts_with($phone, '5')) {
                $phone = '966' . $phone;
            }
        }

        return $phone;
    }

    private function isArabicText(string $text): bool
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) > 0;
    }

    public function verifyWebhook(string $mode, string $token, string $challenge): ?string
    {
        if ($mode === 'subscribe' && $token === $this->webhookVerifyToken) {
            return $challenge;
        }
        
        return null;
    }

    public function getMediaUrl(string $mediaId): ?string
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get("{$this->apiUrl}/{$mediaId}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['url'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Get media URL error: ' . $e->getMessage());
            return null;
        }
    }

    public function downloadMedia(string $mediaUrl): ?string
    {
        try {
            $response = Http::withToken($this->accessToken)->get($mediaUrl);
            
            if ($response->successful()) {
                // Save to storage and return local path
                $filename = 'whatsapp_media_' . time() . '_' . uniqid();
                $path = storage_path('app/public/whatsapp/' . $filename);
                
                if (!file_exists(dirname($path))) {
                    mkdir(dirname($path), 0755, true);
                }
                
                file_put_contents($path, $response->body());
                return 'whatsapp/' . $filename;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Download media error: ' . $e->getMessage());
            return null;
        }
    }
}
