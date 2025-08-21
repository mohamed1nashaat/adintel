<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CommunicationController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsAppService
    ) {}

    public function getConversations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:open,pending,resolved,closed',
            'platform' => 'nullable|in:whatsapp,facebook,instagram,twitter,telegram',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'unread_only' => 'nullable|boolean',
            'country' => 'nullable|string|size:2',
            'language' => 'nullable|string|size:2',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|in:last_message_at,created_at,priority,status',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        $query = Conversation::with(['assignedAgent', 'messages' => function ($q) {
            $q->latest('sent_at')->limit(1);
        }]);

        // Apply filters
        if ($validated['status'] ?? false) {
            $query->where('status', $validated['status']);
        }

        if ($validated['platform'] ?? false) {
            $query->forPlatform($validated['platform']);
        }

        if ($validated['priority'] ?? false) {
            $query->byPriority($validated['priority']);
        }

        if ($validated['assigned_to'] ?? false) {
            $query->assignedTo($validated['assigned_to']);
        }

        if ($validated['unread_only'] ?? false) {
            $query->unread();
        }

        if ($validated['country'] ?? false) {
            $query->byCountry($validated['country']);
        }

        if ($validated['language'] ?? false) {
            $query->byLanguage($validated['language']);
        }

        if ($validated['search'] ?? false) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhereHas('messages', function ($mq) use ($search) {
                      $mq->where('content', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortBy = $validated['sort_by'] ?? 'last_message_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $conversations = $query->paginate($validated['per_page'] ?? 20);

        // Add computed fields
        $conversations->getCollection()->transform(function ($conversation) {
            return [
                'id' => $conversation->id,
                'platform' => $conversation->platform,
                'platform_label' => $conversation->getPlatformLabel(),
                'customer_name' => $conversation->getCustomerDisplayName(),
                'customer_phone' => $conversation->getFormattedPhone(),
                'customer_email' => $conversation->customer_email,
                'status' => $conversation->status,
                'status_label' => $conversation->getStatusLabel(),
                'status_color' => $conversation->getStatusColor(),
                'priority' => $conversation->priority,
                'priority_label' => $conversation->getPriorityLabel(),
                'priority_color' => $conversation->getPriorityColor(),
                'assigned_agent' => $conversation->assignedAgent ? [
                    'id' => $conversation->assignedAgent->id,
                    'name' => $conversation->assignedAgent->name,
                ] : null,
                'language' => $conversation->language,
                'language_label' => $conversation->getLanguageLabel(),
                'country_code' => $conversation->country_code,
                'country_label' => $conversation->getCountryLabel(),
                'tags' => $conversation->tags,
                'is_read' => $conversation->is_read,
                'message_count' => $conversation->message_count,
                'unread_count' => $conversation->getUnreadMessagesCount(),
                'last_message_at' => $conversation->last_message_at?->toISOString(),
                'last_message' => $conversation->messages->first() ? [
                    'content' => $conversation->messages->first()->getDisplayContent(),
                    'type' => $conversation->messages->first()->type,
                    'direction' => $conversation->messages->first()->direction,
                    'sent_at' => $conversation->messages->first()->sent_at->toISOString(),
                ] : null,
                'response_time' => $conversation->getResponseTime(),
                'sla_status' => $conversation->getSlaStatus(),
                'requires_urgent_response' => $conversation->requiresUrgentResponse(),
                'business_hours_status' => $conversation->getBusinessHoursStatus(),
                'created_at' => $conversation->created_at->toISOString(),
                'updated_at' => $conversation->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'data' => $conversations->items(),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    public function getConversation(Conversation $conversation): JsonResponse
    {
        $conversation->load(['assignedAgent', 'messages.sentByUser']);

        $messages = $conversation->messages->map(function ($message) {
            return [
                'id' => $message->id,
                'type' => $message->type,
                'type_icon' => $message->getTypeIcon(),
                'direction' => $message->direction,
                'content' => $message->getDisplayContent(),
                'translated_content' => $message->getTranslatedContent(),
                'media_urls' => $message->media_urls,
                'sender_name' => $message->sender_name,
                'sent_by' => $message->sentByUser ? [
                    'id' => $message->sentByUser->id,
                    'name' => $message->sentByUser->name,
                ] : null,
                'sent_at' => $message->sent_at->toISOString(),
                'formatted_time' => $message->getFormattedSentTime(),
                'delivered_at' => $message->delivered_at?->toISOString(),
                'read_at' => $message->read_at?->toISOString(),
                'delivery_status' => $message->getDeliveryStatus(),
                'delivery_icon' => $message->getDeliveryStatusIcon(),
                'is_automated' => $message->is_automated,
                'language' => $message->language,
                'sentiment' => $message->sentiment,
                'sentiment_color' => $message->getSentimentColor(),
                'sentiment_icon' => $message->getSentimentIcon(),
                'detected_intents' => $message->detected_intents,
                'requires_response' => $message->requires_response,
                'response_due_at' => $message->response_due_at?->toISOString(),
                'is_overdue' => $message->isOverdue(),
                'urgency_score' => $message->getUrgencyScore(),
                'is_urgent' => $message->isUrgent(),
            ];
        });

        return response()->json([
            'data' => [
                'id' => $conversation->id,
                'platform' => $conversation->platform,
                'platform_label' => $conversation->getPlatformLabel(),
                'customer_name' => $conversation->getCustomerDisplayName(),
                'customer_phone' => $conversation->getFormattedPhone(),
                'customer_email' => $conversation->customer_email,
                'customer_metadata' => $conversation->customer_metadata,
                'status' => $conversation->status,
                'status_label' => $conversation->getStatusLabel(),
                'status_color' => $conversation->getStatusColor(),
                'priority' => $conversation->priority,
                'priority_label' => $conversation->getPriorityLabel(),
                'priority_color' => $conversation->getPriorityColor(),
                'assigned_agent' => $conversation->assignedAgent ? [
                    'id' => $conversation->assignedAgent->id,
                    'name' => $conversation->assignedAgent->name,
                ] : null,
                'language' => $conversation->language,
                'language_label' => $conversation->getLanguageLabel(),
                'country_code' => $conversation->country_code,
                'country_label' => $conversation->getCountryLabel(),
                'tags' => $conversation->tags,
                'notes' => $conversation->notes,
                'is_read' => $conversation->is_read,
                'message_count' => $conversation->message_count,
                'unread_count' => $conversation->getUnreadMessagesCount(),
                'last_message_at' => $conversation->last_message_at?->toISOString(),
                'first_response_at' => $conversation->first_response_at?->toISOString(),
                'resolved_at' => $conversation->resolved_at?->toISOString(),
                'response_time' => $conversation->getResponseTime(),
                'average_response_time' => $conversation->getAverageResponseTime(),
                'sla_status' => $conversation->getSlaStatus(),
                'requires_urgent_response' => $conversation->requiresUrgentResponse(),
                'business_hours_status' => $conversation->getBusinessHoursStatus(),
                'messages' => $messages,
                'created_at' => $conversation->created_at->toISOString(),
                'updated_at' => $conversation->updated_at->toISOString(),
            ],
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:4096',
            'type' => 'nullable|in:text,template',
            'template_name' => 'nullable|string|required_if:type,template',
            'template_params' => 'nullable|array|required_if:type,template',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $messageType = $validated['type'] ?? 'text';

            // Send via platform
            if ($conversation->platform === 'whatsapp') {
                if ($messageType === 'template') {
                    $result = $this->whatsAppService->sendTemplate(
                        $conversation->customer_phone,
                        $validated['template_name'],
                        $validated['template_params'] ?? [],
                        $conversation->language
                    );
                } else {
                    $result = $this->whatsAppService->sendMessage(
                        $conversation->customer_phone,
                        $validated['content']
                    );
                }

                if (!$result['success']) {
                    return response()->json([
                        'error' => 'Failed to send message',
                        'message' => $result['error'],
                    ], 400);
                }

                $platformMessageId = $result['message_id'];
            } else {
                // Handle other platforms
                $platformMessageId = 'manual_' . time() . '_' . uniqid();
            }

            // Create message record
            $message = Message::create([
                'tenant_id' => $conversation->tenant_id,
                'conversation_id' => $conversation->id,
                'platform_message_id' => $platformMessageId,
                'type' => 'text',
                'direction' => 'outbound',
                'content' => $validated['content'],
                'sent_by' => $user->id,
                'sent_at' => now(),
                'language' => $conversation->language,
            ]);

            // Mark previous inbound messages as responded
            $conversation->messages()
                ->where('direction', 'inbound')
                ->where('requires_response', true)
                ->update([
                    'requires_response' => false,
                    'response_due_at' => null,
                ]);

            // Update conversation
            $conversation->update([
                'is_read' => true,
                'first_response_at' => $conversation->first_response_at ?? now(),
            ]);

            $conversation->updateLastMessageTime();

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sent_at' => $message->sent_at->toISOString(),
                    'delivery_status' => $message->getDeliveryStatus(),
                ],
                'message' => 'Message sent successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to send message',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:open,pending,resolved,closed',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        $conversation->update($validated);

        return response()->json([
            'data' => $conversation->fresh(),
            'message' => 'Conversation updated successfully',
        ]);
    }

    public function markAsRead(Conversation $conversation): JsonResponse
    {
        $conversation->markAsRead();

        return response()->json([
            'message' => 'Conversation marked as read',
        ]);
    }

    public function assignConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = \App\Models\User::find($validated['user_id']);
        $conversation->assignTo($user);

        return response()->json([
            'message' => 'Conversation assigned successfully',
            'assigned_to' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    public function unassignConversation(Conversation $conversation): JsonResponse
    {
        $conversation->unassign();

        return response()->json([
            'message' => 'Conversation unassigned successfully',
        ]);
    }

    public function resolveConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $conversation->resolve($validated['notes'] ?? null);

        return response()->json([
            'message' => 'Conversation resolved successfully',
        ]);
    }

    public function reopenConversation(Conversation $conversation): JsonResponse
    {
        $conversation->reopen();

        return response()->json([
            'message' => 'Conversation reopened successfully',
        ]);
    }

    public function closeConversation(Conversation $conversation): JsonResponse
    {
        $conversation->close();

        return response()->json([
            'message' => 'Conversation closed successfully',
        ]);
    }

    public function addTag(Request $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'tag' => 'required|string|max:50',
        ]);

        $conversation->addTag($validated['tag']);

        return response()->json([
            'message' => 'Tag added successfully',
            'tags' => $conversation->fresh()->tags,
        ]);
    }

    public function removeTag(Request $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'tag' => 'required|string|max:50',
        ]);

        $conversation->removeTag($validated['tag']);

        return response()->json([
            'message' => 'Tag removed successfully',
            'tags' => $conversation->fresh()->tags,
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|in:today,week,month,quarter,year',
            'platform' => 'nullable|in:whatsapp,facebook,instagram,twitter,telegram',
        ]);

        $period = $validated['period'] ?? 'today';
        $platform = $validated['platform'] ?? null;

        $dateRange = match($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
        };

        $query = Conversation::whereBetween('created_at', $dateRange);
        
        if ($platform) {
            $query->forPlatform($platform);
        }

        $stats = [
            'total_conversations' => $query->count(),
            'open_conversations' => (clone $query)->open()->count(),
            'pending_conversations' => (clone $query)->pending()->count(),
            'resolved_conversations' => (clone $query)->resolved()->count(),
            'closed_conversations' => (clone $query)->closed()->count(),
            'unread_conversations' => (clone $query)->unread()->count(),
            'urgent_conversations' => (clone $query)->byPriority('urgent')->count(),
            'gcc_conversations' => (clone $query)->gccCountries()->count(),
            'arabic_conversations' => (clone $query)->byLanguage('ar')->count(),
        ];

        // Platform breakdown
        $platformStats = Conversation::whereBetween('created_at', $dateRange)
            ->select('platform', DB::raw('count(*) as count'))
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        // Country breakdown
        $countryStats = Conversation::whereBetween('created_at', $dateRange)
            ->select('country_code', DB::raw('count(*) as count'))
            ->groupBy('country_code')
            ->pluck('count', 'country_code')
            ->toArray();

        // Response time stats
        $responseTimeQuery = Conversation::whereBetween('created_at', $dateRange)
            ->whereNotNull('first_response_at');

        if ($platform) {
            $responseTimeQuery->forPlatform($platform);
        }

        $avgResponseTime = $responseTimeQuery->avg(
            DB::raw('TIMESTAMPDIFF(MINUTE, created_at, first_response_at)')
        );

        // SLA compliance
        $slaBreached = Conversation::whereBetween('created_at', $dateRange)
            ->requireingResponse()
            ->count();

        $stats['platform_breakdown'] = $platformStats;
        $stats['country_breakdown'] = $countryStats;
        $stats['average_response_time_minutes'] = round($avgResponseTime ?? 0, 2);
        $stats['sla_breached'] = $slaBreached;
        $stats['sla_compliance_rate'] = $stats['total_conversations'] > 0 
            ? round((($stats['total_conversations'] - $slaBreached) / $stats['total_conversations']) * 100, 2)
            : 100;

        return response()->json([
            'data' => $stats,
            'period' => $period,
            'platform' => $platform,
            'date_range' => [
                'start' => $dateRange[0]->toISOString(),
                'end' => $dateRange[1]->toISOString(),
            ],
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:mark_read,assign,unassign,resolve,close,add_tag,remove_tag,change_priority',
            'conversation_ids' => 'required|array|min:1|max:100',
            'conversation_ids.*' => 'exists:conversations,id',
            'user_id' => 'nullable|exists:users,id|required_if:action,assign',
            'tag' => 'nullable|string|max:50|required_if:action,add_tag,remove_tag',
            'priority' => 'nullable|in:low,medium,high,urgent|required_if:action,change_priority',
        ]);

        $conversations = Conversation::whereIn('id', $validated['conversation_ids'])->get();
        $results = [];

        foreach ($conversations as $conversation) {
            try {
                switch ($validated['action']) {
                    case 'mark_read':
                        $conversation->markAsRead();
                        break;
                    
                    case 'assign':
                        $user = \App\Models\User::find($validated['user_id']);
                        $conversation->assignTo($user);
                        break;
                    
                    case 'unassign':
                        $conversation->unassign();
                        break;
                    
                    case 'resolve':
                        $conversation->resolve();
                        break;
                    
                    case 'close':
                        $conversation->close();
                        break;
                    
                    case 'add_tag':
                        $conversation->addTag($validated['tag']);
                        break;
                    
                    case 'remove_tag':
                        $conversation->removeTag($validated['tag']);
                        break;
                    
                    case 'change_priority':
                        $conversation->update(['priority' => $validated['priority']]);
                        break;
                }

                $results[] = [
                    'conversation_id' => $conversation->id,
                    'success' => true,
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'conversation_id' => $conversation->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $successCount = collect($results)->where('success', true)->count();

        return response()->json([
            'message' => "Bulk action completed. {$successCount} of " . count($results) . " conversations updated.",
            'results' => $results,
        ]);
    }

    public function getTemplates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => 'nullable|in:whatsapp,facebook,instagram',
            'language' => 'nullable|string|size:2',
        ]);

        // This would typically come from a database or external API
        $templates = [
            [
                'name' => 'welcome_message',
                'display_name' => 'Welcome Message',
                'language' => 'ar',
                'platform' => 'whatsapp',
                'content' => 'مرحباً {{1}}، نشكرك لتواصلك معنا. كيف يمكننا مساعدتك اليوم؟',
                'parameters' => ['customer_name'],
            ],
            [
                'name' => 'order_confirmation',
                'display_name' => 'Order Confirmation',
                'language' => 'ar',
                'platform' => 'whatsapp',
                'content' => 'تم تأكيد طلبك رقم {{1}} بقيمة {{2}} ريال. سيتم التوصيل خلال {{3}} أيام عمل.',
                'parameters' => ['order_number', 'amount', 'delivery_days'],
            ],
            [
                'name' => 'appointment_reminder',
                'display_name' => 'Appointment Reminder',
                'language' => 'ar',
                'platform' => 'whatsapp',
                'content' => 'تذكير: لديك موعد يوم {{1}} في تمام الساعة {{2}}. للإلغاء أو التأجيل، يرجى الرد على هذه الرسالة.',
                'parameters' => ['date', 'time'],
            ],
        ];

        // Filter by platform and language if specified
        if ($validated['platform'] ?? false) {
            $templates = array_filter($templates, fn($t) => $t['platform'] === $validated['platform']);
        }

        if ($validated['language'] ?? false) {
            $templates = array_filter($templates, fn($t) => $t['language'] === $validated['language']);
        }

        return response()->json([
            'data' => array_values($templates),
        ]);
    }
}
