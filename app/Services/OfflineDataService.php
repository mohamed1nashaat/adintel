<?php

namespace App\Services;

use App\Models\OfflineConversion;
use App\Models\Lead;
use App\Models\AdCampaign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OfflineDataService
{
    /**
     * Record offline conversion
     */
    public function recordConversion(int $tenantId, array $data): array
    {
        try {
            DB::beginTransaction();

            $conversion = OfflineConversion::create([
                'tenant_id' => $tenantId,
                'lead_id' => $data['lead_id'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'conversion_type' => $data['conversion_type'],
                'conversion_value' => $data['conversion_value'] ?? 0,
                'conversion_currency' => $data['conversion_currency'] ?? 'USD',
                'conversion_date' => $data['conversion_date'] ?? now(),
                'source' => $data['source'] ?? 'manual',
                'external_id' => $data['external_id'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'notes' => $data['notes'] ?? null,
                'status' => 'confirmed',
            ]);

            // Update lead if provided
            if (!empty($data['lead_id'])) {
                $this->updateLeadConversionStatus($data['lead_id'], $conversion);
            }

            // Update campaign metrics if provided
            if (!empty($data['campaign_id'])) {
                $this->updateCampaignOfflineMetrics($data['campaign_id']);
            }

            DB::commit();

            Log::info('Offline conversion recorded', [
                'tenant_id' => $tenantId,
                'conversion_id' => $conversion->id,
                'type' => $data['conversion_type'],
                'value' => $data['conversion_value'] ?? 0
            ]);

            return [
                'success' => true,
                'conversion' => $conversion->toArray(),
                'message' => 'Offline conversion recorded successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to record offline conversion', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Bulk import offline conversions
     */
    public function bulkImportConversions(int $tenantId, array $conversions): array
    {
        try {
            DB::beginTransaction();

            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => [],
                'conversions' => []
            ];

            foreach ($conversions as $index => $conversionData) {
                try {
                    $conversionData['tenant_id'] = $tenantId;
                    $result = $this->recordConversion($tenantId, $conversionData);
                    $results['success']++;
                    $results['conversions'][] = $result['conversion'];
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                        'data' => $conversionData
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk offline conversions imported', [
                'tenant_id' => $tenantId,
                'total' => count($conversions),
                'success' => $results['success'],
                'failed' => $results['failed']
            ]);

            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk import offline conversions', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            throw $e;
        }
    }

    /**
     * Get offline conversions with filters
     */
    public function getConversions(int $tenantId, array $filters = []): array
    {
        try {
            $query = OfflineConversion::where('tenant_id', $tenantId)
                ->with(['lead', 'campaign']);

            // Apply filters
            if (!empty($filters['conversion_type'])) {
                $query->where('conversion_type', $filters['conversion_type']);
            }

            if (!empty($filters['source'])) {
                $query->where('source', $filters['source']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['campaign_id'])) {
                $query->where('campaign_id', $filters['campaign_id']);
            }

            if (!empty($filters['date_from'])) {
                $query->where('conversion_date', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->where('conversion_date', '<=', $filters['date_to']);
            }

            if (!empty($filters['min_value'])) {
                $query->where('conversion_value', '>=', $filters['min_value']);
            }

            if (!empty($filters['max_value'])) {
                $query->where('conversion_value', '<=', $filters['max_value']);
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'conversion_date';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $filters['per_page'] ?? 50;
            $conversions = $query->paginate($perPage);

            return [
                'conversions' => $conversions->items(),
                'pagination' => [
                    'current_page' => $conversions->currentPage(),
                    'last_page' => $conversions->lastPage(),
                    'per_page' => $conversions->perPage(),
                    'total' => $conversions->total(),
                ],
                'summary' => $this->getConversionsSummary($tenantId, $filters)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get offline conversions', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Get conversions summary
     */
    public function getConversionsSummary(int $tenantId, array $filters = []): array
    {
        try {
            $query = OfflineConversion::where('tenant_id', $tenantId);

            // Apply same filters as getConversions
            if (!empty($filters['conversion_type'])) {
                $query->where('conversion_type', $filters['conversion_type']);
            }

            if (!empty($filters['source'])) {
                $query->where('source', $filters['source']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['campaign_id'])) {
                $query->where('campaign_id', $filters['campaign_id']);
            }

            if (!empty($filters['date_from'])) {
                $query->where('conversion_date', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->where('conversion_date', '<=', $filters['date_to']);
            }

            $summary = $query->selectRaw('
                COUNT(*) as total_conversions,
                SUM(conversion_value) as total_value,
                AVG(conversion_value) as avg_value,
                MAX(conversion_value) as max_value,
                MIN(conversion_value) as min_value,
                COUNT(DISTINCT conversion_type) as unique_types,
                COUNT(DISTINCT campaign_id) as campaigns_with_conversions
            ')->first();

            // Get conversion types breakdown
            $typeBreakdown = OfflineConversion::where('tenant_id', $tenantId)
                ->selectRaw('conversion_type, COUNT(*) as count, SUM(conversion_value) as total_value')
                ->groupBy('conversion_type')
                ->get()
                ->keyBy('conversion_type');

            // Get monthly trend
            $monthlyTrend = OfflineConversion::where('tenant_id', $tenantId)
                ->selectRaw('DATE_FORMAT(conversion_date, "%Y-%m") as month, COUNT(*) as count, SUM(conversion_value) as total_value')
                ->where('conversion_date', '>=', now()->subMonths(12))
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            return [
                'total_conversions' => $summary->total_conversions ?? 0,
                'total_value' => $summary->total_value ?? 0,
                'avg_value' => $summary->avg_value ?? 0,
                'max_value' => $summary->max_value ?? 0,
                'min_value' => $summary->min_value ?? 0,
                'unique_types' => $summary->unique_types ?? 0,
                'campaigns_with_conversions' => $summary->campaigns_with_conversions ?? 0,
                'type_breakdown' => $typeBreakdown,
                'monthly_trend' => $monthlyTrend,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get conversions summary', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            throw $e;
        }
    }

    /**
     * Update conversion status
     */
    public function updateConversionStatus(int $conversionId, string $status, ?string $notes = null): bool
    {
        try {
            $conversion = OfflineConversion::findOrFail($conversionId);
            
            $conversion->update([
                'status' => $status,
                'notes' => $notes ?? $conversion->notes,
                'updated_at' => now()
            ]);

            Log::info('Conversion status updated', [
                'conversion_id' => $conversionId,
                'status' => $status
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update conversion status', [
                'error' => $e->getMessage(),
                'conversion_id' => $conversionId,
                'status' => $status
            ]);
            throw $e;
        }
    }

    /**
     * Delete conversion
     */
    public function deleteConversion(int $conversionId): bool
    {
        try {
            $conversion = OfflineConversion::findOrFail($conversionId);
            $conversion->delete();

            Log::info('Conversion deleted', [
                'conversion_id' => $conversionId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete conversion', [
                'error' => $e->getMessage(),
                'conversion_id' => $conversionId
            ]);
            throw $e;
        }
    }

    /**
     * Sync conversions to ad platforms
     */
    public function syncConversionsToAdPlatforms(int $tenantId, array $conversionIds = []): array
    {
        try {
            $query = OfflineConversion::where('tenant_id', $tenantId)
                ->where('status', 'confirmed')
                ->whereNotNull('campaign_id');

            if (!empty($conversionIds)) {
                $query->whereIn('id', $conversionIds);
            }

            $conversions = $query->get();
            $results = [
                'facebook' => ['success' => 0, 'failed' => 0, 'errors' => []],
                'google' => ['success' => 0, 'failed' => 0, 'errors' => []],
                'tiktok' => ['success' => 0, 'failed' => 0, 'errors' => []],
            ];

            foreach ($conversions as $conversion) {
                // Sync to Facebook
                try {
                    $this->syncToFacebook($conversion);
                    $results['facebook']['success']++;
                } catch (\Exception $e) {
                    $results['facebook']['failed']++;
                    $results['facebook']['errors'][] = $e->getMessage();
                }

                // Sync to Google
                try {
                    $this->syncToGoogle($conversion);
                    $results['google']['success']++;
                } catch (\Exception $e) {
                    $results['google']['failed']++;
                    $results['google']['errors'][] = $e->getMessage();
                }

                // Sync to TikTok
                try {
                    $this->syncToTikTok($conversion);
                    $results['tiktok']['success']++;
                } catch (\Exception $e) {
                    $results['tiktok']['failed']++;
                    $results['tiktok']['errors'][] = $e->getMessage();
                }
            }

            Log::info('Conversions synced to ad platforms', [
                'tenant_id' => $tenantId,
                'total_conversions' => $conversions->count(),
                'results' => $results
            ]);

            return $results;
        } catch (\Exception $e) {
            Log::error('Failed to sync conversions to ad platforms', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            throw $e;
        }
    }

    /**
     * Get conversion types
     */
    public function getConversionTypes(): array
    {
        return [
            'purchase' => 'Purchase',
            'lead' => 'Lead',
            'signup' => 'Sign Up',
            'call' => 'Phone Call',
            'appointment' => 'Appointment',
            'demo' => 'Demo Request',
            'trial' => 'Trial Started',
            'subscription' => 'Subscription',
            'download' => 'Download',
            'contact' => 'Contact Form',
            'quote' => 'Quote Request',
            'other' => 'Other'
        ];
    }

    /**
     * Get conversion sources
     */
    public function getConversionSources(): array
    {
        return [
            'manual' => 'Manual Entry',
            'crm' => 'CRM System',
            'phone' => 'Phone System',
            'email' => 'Email Marketing',
            'website' => 'Website',
            'store' => 'Physical Store',
            'event' => 'Event/Trade Show',
            'referral' => 'Referral',
            'other' => 'Other'
        ];
    }

    /**
     * Private helper methods
     */
    private function updateLeadConversionStatus(int $leadId, OfflineConversion $conversion): void
    {
        try {
            $lead = Lead::find($leadId);
            if ($lead) {
                $lead->update([
                    'status' => 'converted',
                    'conversion_date' => $conversion->conversion_date,
                    'conversion_value' => $conversion->conversion_value,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update lead conversion status', [
                'lead_id' => $leadId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function updateCampaignOfflineMetrics(int $campaignId): void
    {
        try {
            $campaign = AdCampaign::find($campaignId);
            if ($campaign) {
                $offlineConversions = OfflineConversion::where('campaign_id', $campaignId)
                    ->where('status', 'confirmed')
                    ->count();
                
                $offlineValue = OfflineConversion::where('campaign_id', $campaignId)
                    ->where('status', 'confirmed')
                    ->sum('conversion_value');

                // Update campaign metadata
                $metadata = $campaign->metadata ?? [];
                $metadata['offline_conversions'] = $offlineConversions;
                $metadata['offline_value'] = $offlineValue;
                
                $campaign->update(['metadata' => $metadata]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update campaign offline metrics', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function syncToFacebook(OfflineConversion $conversion): void
    {
        // Placeholder for Facebook Conversions API integration
        Log::info('Syncing conversion to Facebook', [
            'conversion_id' => $conversion->id
        ]);
        
        // TODO: Implement Facebook Conversions API sync
        // This would use the Facebook Marketing API to send offline conversions
    }

    private function syncToGoogle(OfflineConversion $conversion): void
    {
        // Placeholder for Google Ads offline conversion import
        Log::info('Syncing conversion to Google', [
            'conversion_id' => $conversion->id
        ]);
        
        // TODO: Implement Google Ads offline conversion import
        // This would use the Google Ads API to import offline conversions
    }

    private function syncToTikTok(OfflineConversion $conversion): void
    {
        // Placeholder for TikTok Events API integration
        Log::info('Syncing conversion to TikTok', [
            'conversion_id' => $conversion->id
        ]);
        
        // TODO: Implement TikTok Events API sync
        // This would use the TikTok Marketing API to send offline events
    }
}
