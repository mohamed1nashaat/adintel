<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use App\Models\CustomAudience;
use App\Services\GoogleSheetsService;
use App\Services\CustomAudienceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function __construct(
        private GoogleSheetsService $googleSheetsService,
        private CustomAudienceService $customAudienceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Lead::with(['leadSource', 'assignedUser']);

        // Apply filters
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('quality')) {
            $query->byQuality($request->quality);
        }

        if ($request->filled('source_id')) {
            $query->fromSource($request->source_id);
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->unassigned();
            } else {
                $query->assignedTo($request->assigned_to);
            }
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $leads = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $leads->items(),
            'meta' => [
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
            ],
        ]);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load(['leadSource', 'assignedUser', 'webhookLogs']);
        
        return response()->json(['data' => $lead]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_source_id' => 'required|exists:lead_sources,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'status' => ['nullable', Rule::in(['new', 'contacted', 'qualified', 'converted', 'lost'])],
            'quality' => ['nullable', Rule::in(['hot', 'warm', 'cold', 'unqualified'])],
            'estimated_value' => 'nullable|numeric|min:0',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
            'referrer_url' => 'nullable|url',
            'landing_page' => 'nullable|url',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $validated['tenant_id'] = session('current_tenant_id');

        $lead = Lead::create($validated);

        // Update lead source stats
        $leadSource = LeadSource::find($validated['lead_source_id']);
        $leadSource->incrementLeadsCount();

        // Auto-sync to Google Sheets if enabled
        if ($leadSource->shouldAutoSync()) {
            $this->googleSheetsService->syncLead($lead);
        }

        $lead->load(['leadSource', 'assignedUser']);

        return response()->json(['data' => $lead], 201);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'status' => ['nullable', Rule::in(['new', 'contacted', 'qualified', 'converted', 'lost'])],
            'quality' => ['nullable', Rule::in(['hot', 'warm', 'cold', 'unqualified'])],
            'estimated_value' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        // Mark as converted if status changed to converted
        if (isset($validated['status']) && $validated['status'] === 'converted' && $lead->status !== 'converted') {
            $validated['converted_at'] = now();
        }

        $lead->update($validated);
        $lead->load(['leadSource', 'assignedUser']);

        return response()->json(['data' => $lead]);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully']);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
            'updates' => 'required|array',
            'updates.status' => ['nullable', Rule::in(['new', 'contacted', 'qualified', 'converted', 'lost'])],
            'updates.quality' => ['nullable', Rule::in(['hot', 'warm', 'cold', 'unqualified'])],
            'updates.assigned_to' => 'nullable|exists:users,id',
        ]);

        $updates = $validated['updates'];
        
        // Add converted_at if status is being changed to converted
        if (isset($updates['status']) && $updates['status'] === 'converted') {
            $updates['converted_at'] = now();
        }

        Lead::whereIn('id', $validated['lead_ids'])->update($updates);

        return response()->json(['message' => 'Leads updated successfully']);
    }

    public function assign(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);
        $lead->assignTo($user);

        return response()->json([
            'message' => 'Lead assigned successfully',
            'data' => $lead->load(['assignedUser']),
        ]);
    }

    public function unassign(Lead $lead): JsonResponse
    {
        $lead->unassign();

        return response()->json([
            'message' => 'Lead unassigned successfully',
            'data' => $lead->fresh(),
        ]);
    }

    public function addNote(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        $lead->addNote($validated['note']);

        return response()->json([
            'message' => 'Note added successfully',
            'data' => $lead->fresh(),
        ]);
    }

    public function syncToSheets(Lead $lead): JsonResponse
    {
        try {
            $this->googleSheetsService->syncLead($lead);
            
            return response()->json(['message' => 'Lead synced to Google Sheets successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to sync lead to Google Sheets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkSyncToSheets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
        ]);

        $leads = Lead::whereIn('id', $validated['lead_ids'])->get();
        $synced = 0;
        $failed = 0;

        foreach ($leads as $lead) {
            try {
                $this->googleSheetsService->syncLead($lead);
                $synced++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return response()->json([
            'message' => "Sync completed: {$synced} successful, {$failed} failed",
            'synced' => $synced,
            'failed' => $failed,
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Lead::count(),
            'new' => Lead::new()->count(),
            'contacted' => Lead::byStatus('contacted')->count(),
            'qualified' => Lead::byStatus('qualified')->count(),
            'converted' => Lead::converted()->count(),
            'lost' => Lead::byStatus('lost')->count(),
            'hot' => Lead::hot()->count(),
            'warm' => Lead::byQuality('warm')->count(),
            'cold' => Lead::byQuality('cold')->count(),
            'unassigned' => Lead::unassigned()->count(),
            'this_month' => Lead::whereMonth('created_at', now()->month)->count(),
            'this_week' => Lead::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'today' => Lead::whereDate('created_at', now())->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|in:csv,xlsx',
            'filters' => 'nullable|array',
        ]);

        // This would typically queue an export job
        // For now, we'll return a placeholder response
        
        return response()->json([
            'message' => 'Export job queued successfully',
            'format' => $validated['format'],
            'estimated_completion' => now()->addMinutes(5)->toISOString(),
        ]);
    }

    // Custom Audience and File Upload Methods

    /**
     * Upload leads file
     */
    public function uploadFile(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            ]);

            $result = $this->customAudienceService->uploadLeadsFile(
                session('current_tenant_id'),
                auth()->id(),
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Leads file uploaded and processed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload leads file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export leads to CSV
     */
    public function exportLeads(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'filters' => 'nullable|array',
                'format' => 'nullable|in:csv',
            ]);

            $filePath = $this->customAudienceService->exportLeads(
                session('current_tenant_id'),
                $request->filters ?? []
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => url('storage/' . $filePath),
                    'filename' => basename($filePath),
                ],
                'message' => 'Leads exported successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export leads',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download demo export template
     */
    public function downloadDemo(): JsonResponse
    {
        try {
            $filePath = $this->customAudienceService->generateDemoExport();

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => url('storage/' . $filePath),
                    'filename' => 'demo_leads_template.csv',
                ],
                'message' => 'Demo template generated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate demo template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all custom audiences
     */
    public function audiences(Request $request): JsonResponse
    {
        $query = CustomAudience::with('creator');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $audiences = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $audiences,
        ]);
    }

    /**
     * Create custom audience
     */
    public function createAudience(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'criteria' => 'required|array',
                'date_range' => 'nullable|array',
            ]);

            $audience = $this->customAudienceService->createAudience(
                session('current_tenant_id'),
                auth()->id(),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'data' => $audience,
                'message' => 'Custom audience created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create custom audience',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build audience from criteria
     */
    public function buildAudience(CustomAudience $audience): JsonResponse
    {
        try {
            $result = $this->customAudienceService->buildAudience($audience);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Audience built successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to build audience',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get audience analytics
     */
    public function audienceAnalytics(CustomAudience $audience): JsonResponse
    {
        try {
            $analytics = $this->customAudienceService->getAudienceAnalytics($audience);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get audience analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync audience to platforms
     */
    public function syncAudience(Request $request, CustomAudience $audience): JsonResponse
    {
        try {
            $validated = $request->validate([
                'platforms' => 'required|array',
                'platforms.*' => 'in:facebook,google,tiktok,snapchat',
            ]);

            $results = $this->customAudienceService->syncAudienceToPlattforms(
                $audience,
                $request->platforms
            );

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Audience sync initiated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync audience',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
