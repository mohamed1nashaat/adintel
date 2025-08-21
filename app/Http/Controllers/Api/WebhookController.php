<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\WebhookLog;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    public function __construct(
        private GoogleSheetsService $googleSheetsService
    ) {}

    public function handleLeadWebhook(Request $request, string $slug): JsonResponse
    {
        $leadSource = LeadSource::where('slug', $slug)->first();

        if (!$leadSource) {
            return response()->json(['error' => 'Lead source not found'], 404);
        }

        if (!$leadSource->isActive()) {
            return response()->json(['error' => 'Lead source is inactive'], 403);
        }

        // Log the webhook request
        $webhookLog = WebhookLog::create([
            'tenant_id' => $leadSource->tenant_id,
            'lead_source_id' => $leadSource->id,
            'webhook_url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'payload' => $request->getContent(),
            'source_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'pending',
        ]);

        try {
            // Verify webhook signature if configured
            if ($leadSource->webhook_secret && !$this->verifySignature($request, $leadSource, $webhookLog)) {
                $webhookLog->markAsIgnored('Invalid webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Parse webhook data
            $data = $this->parseWebhookData($request, $leadSource);
            $webhookLog->update(['parsed_data' => $data]);

            // Validate required fields
            $validation = $this->validateLeadData($data, $leadSource);
            if ($validation['fails']) {
                $webhookLog->markAsFailed('Validation failed: ' . implode(', ', $validation['errors']));
                return response()->json(['error' => 'Validation failed', 'details' => $validation['errors']], 400);
            }

            // Check for duplicate leads
            if ($this->isDuplicateLead($data, $leadSource)) {
                $webhookLog->markAsIgnored('Duplicate lead detected');
                return response()->json(['message' => 'Duplicate lead ignored'], 200);
            }

            // Create the lead
            $lead = $this->createLeadFromWebhook($data, $leadSource, $request);
            
            // Update webhook log
            $webhookLog->markAsProcessed($lead);

            // Update lead source stats
            $leadSource->incrementLeadsCount();

            // Auto-sync to Google Sheets if enabled
            if ($leadSource->shouldAutoSync()) {
                try {
                    $this->googleSheetsService->syncLead($lead);
                } catch (\Exception $e) {
                    Log::warning('Failed to auto-sync lead to Google Sheets', [
                        'lead_id' => $lead->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Send notifications if configured
            $this->sendNotifications($lead, $leadSource);

            Log::info('Lead created from webhook', [
                'lead_id' => $lead->id,
                'lead_source_id' => $leadSource->id,
                'webhook_log_id' => $webhookLog->id,
            ]);

            return response()->json([
                'message' => 'Lead created successfully',
                'lead_id' => $lead->id,
            ], 201);

        } catch (\Exception $e) {
            $webhookLog->markAsFailed($e->getMessage());
            
            Log::error('Webhook processing failed', [
                'lead_source_id' => $leadSource->id,
                'webhook_log_id' => $webhookLog->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function retryFailedWebhooks(): JsonResponse
    {
        $failedLogs = WebhookLog::needsRetry()->limit(50)->get();
        $processed = 0;
        $failed = 0;

        foreach ($failedLogs as $log) {
            try {
                // Recreate the request from logged data
                $request = $this->recreateRequestFromLog($log);
                
                // Retry processing
                $this->handleLeadWebhook($request, $log->leadSource->slug);
                $processed++;
            } catch (\Exception $e) {
                $log->scheduleRetry();
                $failed++;
            }
        }

        return response()->json([
            'message' => "Retry completed: {$processed} processed, {$failed} failed",
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }

    private function verifySignature(Request $request, LeadSource $leadSource, WebhookLog $log): bool
    {
        $signature = $request->header('X-Signature') ?? $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }

        return $leadSource->verifyWebhookSignature($request->getContent(), $signature);
    }

    private function parseWebhookData(Request $request, LeadSource $leadSource): array
    {
        $contentType = $request->header('Content-Type', '');
        
        if (str_contains($contentType, 'application/json')) {
            $data = $request->json()->all();
        } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $data = $request->all();
        } else {
            // Try to parse as JSON first, then fall back to form data
            $jsonData = json_decode($request->getContent(), true);
            $data = $jsonData ?: $request->all();
        }

        // Apply field mapping if configured
        return $leadSource->mapWebhookData($data);
    }

    private function validateLeadData(array $data, LeadSource $leadSource): array
    {
        $formFields = $leadSource->getFormFieldsConfig();
        $rules = [];
        
        foreach ($formFields as $field => $config) {
            if ($config['required'] ?? false) {
                $rules[$field] = 'required';
            }
            
            if (($config['type'] ?? '') === 'email') {
                $rules[$field] = ($rules[$field] ?? '') . '|email';
            }
        }

        $validator = Validator::make($data, $rules);

        return [
            'fails' => $validator->fails(),
            'errors' => $validator->errors()->all(),
        ];
    }

    private function isDuplicateLead(array $data, LeadSource $leadSource): bool
    {
        if (empty($data['email'])) {
            return false;
        }

        // Check for duplicate email in the same tenant within the last 24 hours
        return Lead::where('tenant_id', $leadSource->tenant_id)
                   ->where('email', $data['email'])
                   ->where('created_at', '>=', now()->subDay())
                   ->exists();
    }

    private function createLeadFromWebhook(array $data, LeadSource $leadSource, Request $request): Lead
    {
        $leadData = [
            'tenant_id' => $leadSource->tenant_id,
            'lead_source_id' => $leadSource->id,
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => 'new',
            'quality' => $this->determineLeadQuality($data),
            'utm_source' => $data['utm_source'] ?? $request->get('utm_source'),
            'utm_medium' => $data['utm_medium'] ?? $request->get('utm_medium'),
            'utm_campaign' => $data['utm_campaign'] ?? $request->get('utm_campaign'),
            'utm_term' => $data['utm_term'] ?? $request->get('utm_term'),
            'utm_content' => $data['utm_content'] ?? $request->get('utm_content'),
            'referrer_url' => $request->header('Referer'),
            'landing_page' => $data['landing_page'] ?? $request->get('landing_page'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'form_data' => $data,
        ];

        // Add custom fields
        $customFields = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, ['first_name', 'last_name', 'email', 'phone', 'company', 'job_title', 'message'])) {
                $customFields[$key] = $value;
            }
        }
        
        if (!empty($customFields)) {
            $leadData['custom_fields'] = $customFields;
        }

        return Lead::create($leadData);
    }

    private function determineLeadQuality(array $data): ?string
    {
        // Simple lead scoring logic - can be enhanced
        $score = 0;

        // Company provided
        if (!empty($data['company'])) {
            $score += 2;
        }

        // Phone provided
        if (!empty($data['phone'])) {
            $score += 1;
        }

        // Job title provided
        if (!empty($data['job_title'])) {
            $score += 1;
        }

        // Message length
        if (!empty($data['message']) && strlen($data['message']) > 50) {
            $score += 1;
        }

        return match(true) {
            $score >= 4 => 'hot',
            $score >= 2 => 'warm',
            $score >= 1 => 'cold',
            default => null,
        };
    }

    private function sendNotifications(Lead $lead, LeadSource $leadSource): void
    {
        // Email notifications
        if ($leadSource->shouldNotifyEmail()) {
            // Queue email notification job
            // NotifyNewLeadJob::dispatch($lead, $leadSource->getEmailRecipients());
        }

        // Slack notifications
        if ($leadSource->shouldNotifySlack()) {
            // Queue slack notification job
            // SlackNotificationJob::dispatch($lead, $leadSource->getSlackWebhookUrl());
        }
    }

    private function recreateRequestFromLog(WebhookLog $log): Request
    {
        // This is a simplified recreation - in a real implementation,
        // you'd want to more accurately recreate the original request
        $request = new Request();
        $request->merge(json_decode($log->payload, true) ?: []);
        
        return $request;
    }

    public function webhookLogs(Request $request): JsonResponse
    {
        $query = WebhookLog::with(['leadSource', 'lead']);

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('source_id')) {
            $query->bySource($request->source_id);
        }

        if ($request->filled('hours')) {
            $query->recent($request->hours);
        }

        $logs = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function webhookStats(): JsonResponse
    {
        $stats = [
            'total' => WebhookLog::count(),
            'pending' => WebhookLog::pending()->count(),
            'processed' => WebhookLog::processed()->count(),
            'failed' => WebhookLog::failed()->count(),
            'ignored' => WebhookLog::ignored()->count(),
            'needs_retry' => WebhookLog::needsRetry()->count(),
            'today' => WebhookLog::whereDate('created_at', now())->count(),
            'this_week' => WebhookLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return response()->json(['data' => $stats]);
    }
}
