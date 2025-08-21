<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfflineConversion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OfflineConversionController extends Controller
{
    /**
     * Display a listing of offline conversions
     */
    public function index(Request $request): JsonResponse
    {
        $query = OfflineConversion::with(['createdBy'])
            ->where('tenant_id', session('current_tenant_id'));

        // Filter by conversion type
        if ($request->has('conversion_type')) {
            $query->where('conversion_type', $request->conversion_type);
        }

        // Filter by source
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('conversion_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('conversion_date', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_email', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $request->search . '%');
            });
        }

        $conversions = $query->orderBy('conversion_date', 'desc')
                           ->paginate($request->get('per_page', 15));

        return response()->json($conversions);
    }

    /**
     * Store a newly created offline conversion
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'conversion_type' => 'required|string|max:100',
            'conversion_date' => 'required|date',
            'conversion_value' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'source' => 'required|string|max:100',
            'campaign_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'custom_fields' => 'nullable|array',
        ]);

        $conversion = OfflineConversion::create([
            'tenant_id' => session('current_tenant_id'),
            'created_by' => $request->user()->id,
            'conversion_type' => $request->conversion_type,
            'conversion_date' => $request->conversion_date,
            'conversion_value' => $request->conversion_value,
            'currency' => $request->currency,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'source' => $request->source,
            'campaign_reference' => $request->campaign_reference,
            'notes' => $request->notes,
            'custom_fields' => $request->custom_fields,
        ]);

        return response()->json($conversion->load('createdBy'), 201);
    }

    /**
     * Display the specified offline conversion
     */
    public function show(OfflineConversion $offlineConversion): JsonResponse
    {
        $this->authorize('view', $offlineConversion);

        return response()->json($offlineConversion->load(['createdBy']));
    }

    /**
     * Update the specified offline conversion
     */
    public function update(Request $request, OfflineConversion $offlineConversion): JsonResponse
    {
        $this->authorize('update', $offlineConversion);

        $request->validate([
            'conversion_type' => 'sometimes|string|max:100',
            'conversion_date' => 'sometimes|date',
            'conversion_value' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'source' => 'sometimes|string|max:100',
            'campaign_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'custom_fields' => 'nullable|array',
        ]);

        $offlineConversion->update($request->only([
            'conversion_type', 'conversion_date', 'conversion_value', 'currency',
            'customer_name', 'customer_email', 'customer_phone', 'source',
            'campaign_reference', 'notes', 'custom_fields'
        ]));

        return response()->json($offlineConversion->load('createdBy'));
    }

    /**
     * Remove the specified offline conversion
     */
    public function destroy(OfflineConversion $offlineConversion): JsonResponse
    {
        $this->authorize('delete', $offlineConversion);

        $offlineConversion->delete();

        return response()->json(['message' => 'Offline conversion deleted successfully']);
    }

    /**
     * Bulk import offline conversions
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'conversions' => 'required|array|min:1',
            'conversions.*.conversion_type' => 'required|string|max:100',
            'conversions.*.conversion_date' => 'required|date',
            'conversions.*.conversion_value' => 'required|numeric|min:0',
            'conversions.*.currency' => 'required|string|size:3',
            'conversions.*.source' => 'required|string|max:100',
        ]);

        $imported = [];
        $errors = [];

        foreach ($request->conversions as $index => $conversionData) {
            try {
                $conversion = OfflineConversion::create([
                    'tenant_id' => session('current_tenant_id'),
                    'created_by' => $request->user()->id,
                    ...$conversionData
                ]);

                $imported[] = $conversion;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'data' => $conversionData,
                ];
            }
        }

        return response()->json([
            'message' => 'Bulk import completed',
            'imported_count' => count($imported),
            'error_count' => count($errors),
            'imported' => $imported,
            'errors' => $errors,
        ]);
    }

    /**
     * Get conversion statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $tenantId = session('current_tenant_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = OfflineConversion::where('tenant_id', $tenantId)
            ->whereBetween('conversion_date', [$dateFrom, $dateTo]);

        $stats = [
            'total_conversions' => $query->count(),
            'total_value' => $query->sum('conversion_value'),
            'average_value' => $query->avg('conversion_value'),
            'by_type' => $query->selectRaw('conversion_type, COUNT(*) as count, SUM(conversion_value) as total_value')
                ->groupBy('conversion_type')
                ->get(),
            'by_source' => $query->selectRaw('source, COUNT(*) as count, SUM(conversion_value) as total_value')
                ->groupBy('source')
                ->get(),
            'by_currency' => $query->selectRaw('currency, COUNT(*) as count, SUM(conversion_value) as total_value')
                ->groupBy('currency')
                ->get(),
            'daily_trend' => $query->selectRaw('DATE(conversion_date) as date, COUNT(*) as count, SUM(conversion_value) as total_value')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Export offline conversions
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:csv,xlsx',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'conversion_type' => 'nullable|string',
            'source' => 'nullable|string',
        ]);

        $query = OfflineConversion::where('tenant_id', session('current_tenant_id'));

        // Apply filters
        if ($request->date_from) {
            $query->where('conversion_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('conversion_date', '<=', $request->date_to);
        }

        if ($request->conversion_type) {
            $query->where('conversion_type', $request->conversion_type);
        }

        if ($request->source) {
            $query->where('source', $request->source);
        }

        $conversions = $query->orderBy('conversion_date', 'desc')->get();

        // In a real implementation, you would generate the actual file
        // For now, we'll return the data structure
        return response()->json([
            'message' => 'Export prepared successfully',
            'format' => $request->format,
            'total_records' => $conversions->count(),
            'download_url' => '/api/offline-conversions/download/' . uniqid(),
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Get conversion types
     */
    public function conversionTypes(): JsonResponse
    {
        $types = [
            'sale' => 'Sale/Purchase',
            'lead' => 'Lead Generation',
            'signup' => 'Sign Up',
            'call' => 'Phone Call',
            'appointment' => 'Appointment Booking',
            'demo' => 'Demo Request',
            'quote' => 'Quote Request',
            'subscription' => 'Subscription',
            'renewal' => 'Renewal',
            'upsell' => 'Upsell',
            'other' => 'Other',
        ];

        return response()->json(['data' => $types]);
    }

    /**
     * Get conversion sources
     */
    public function conversionSources(): JsonResponse
    {
        $sources = [
            'phone' => 'Phone Call',
            'email' => 'Email',
            'website' => 'Website Form',
            'store' => 'Physical Store',
            'event' => 'Event/Trade Show',
            'referral' => 'Referral',
            'direct' => 'Direct Contact',
            'whatsapp' => 'WhatsApp',
            'social' => 'Social Media',
            'other' => 'Other',
        ];

        return response()->json(['data' => $sources]);
    }
}
