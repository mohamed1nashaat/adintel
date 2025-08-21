<?php

namespace App\Services;

use App\Models\CustomAudience;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use League\Csv\Reader;
use League\Csv\Writer;
use League\Csv\CannotInsertRecord;

class CustomAudienceService
{
    /**
     * Create a custom audience based on criteria
     */
    public function createAudience(int $tenantId, int $userId, array $data): CustomAudience
    {
        try {
            $audience = CustomAudience::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'criteria' => $data['criteria'] ?? [],
                'date_range' => $data['date_range'] ?? [],
                'created_by' => $userId,
                'status' => 'building',
            ]);

            // Calculate initial lead count
            $audience->updateLeadCount();
            
            // Mark as ready if we have leads
            if ($audience->lead_count > 0) {
                $audience->status = 'ready';
                $audience->save();
            }

            Log::info('Custom audience created', [
                'audience_id' => $audience->id,
                'tenant_id' => $tenantId,
                'lead_count' => $audience->lead_count
            ]);

            return $audience;
        } catch (\Exception $e) {
            Log::error('Failed to create custom audience', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Build audience from criteria
     */
    public function buildAudience(CustomAudience $audience): array
    {
        try {
            $audience->status = 'building';
            $audience->save();

            $matchingLeads = $audience->getMatchingLeads()->get();
            
            // Sync the leads with the audience
            $audience->leads()->sync($matchingLeads->pluck('id'));
            
            $audience->lead_count = $matchingLeads->count();
            $audience->status = $matchingLeads->count() > 0 ? 'ready' : 'building';
            $audience->save();

            return [
                'success' => true,
                'lead_count' => $audience->lead_count,
                'status' => $audience->status,
                'leads' => $matchingLeads->take(10)->toArray(), // Preview first 10
            ];
        } catch (\Exception $e) {
            $audience->status = 'error';
            $audience->save();
            
            Log::error('Failed to build audience', [
                'audience_id' => $audience->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Upload and process leads file
     */
    public function uploadLeadsFile(int $tenantId, int $userId, UploadedFile $file): array
    {
        try {
            // Validate file
            $this->validateLeadsFile($file);
            
            // Store file
            $path = $file->store('lead_uploads', 'local');
            $fullPath = Storage::path($path);
            
            // Process CSV
            $results = $this->processLeadsCSV($fullPath, $tenantId, $userId);
            
            // Clean up file
            Storage::delete($path);
            
            Log::info('Leads file uploaded and processed', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'results' => $results
            ]);
            
            return $results;
        } catch (\Exception $e) {
            Log::error('Failed to upload leads file', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Export leads to CSV
     */
    public function exportLeads(int $tenantId, array $filters = []): string
    {
        try {
            $query = Lead::where('tenant_id', $tenantId);
            
            // Apply filters
            if (!empty($filters['source'])) {
                $query->where('source', $filters['source']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (!empty($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }
            
            $leads = $query->get();
            
            // Create CSV
            $filename = 'leads_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $path = 'exports/' . $filename;
            $fullPath = Storage::path($path);
            
            // Ensure directory exists
            Storage::makeDirectory('exports');
            
            $csv = Writer::createFromPath($fullPath, 'w+');
            
            // Add headers
            $csv->insertOne([
                'ID',
                'Email',
                'Phone',
                'First Name',
                'Last Name',
                'Source',
                'Status',
                'Score',
                'Location',
                'Created At',
                'Updated At'
            ]);
            
            // Add data
            foreach ($leads as $lead) {
                $csv->insertOne([
                    $lead->id,
                    $lead->email,
                    $lead->phone,
                    $lead->first_name,
                    $lead->last_name,
                    $lead->source,
                    $lead->status,
                    $lead->score,
                    $lead->metadata['location']['city'] ?? '' . ', ' . $lead->metadata['location']['country'] ?? '',
                    $lead->created_at->format('Y-m-d H:i:s'),
                    $lead->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Failed to export leads', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            throw $e;
        }
    }

    /**
     * Sync audience to advertising platforms
     */
    public function syncAudienceToPlattforms(CustomAudience $audience, array $platforms): array
    {
        $results = [];
        
        foreach ($platforms as $platform) {
            try {
                $audience->updateSyncStatus($platform, 'syncing');
                
                $result = $this->syncToPlatform($audience, $platform);
                
                if ($result['success']) {
                    $audience->updateSyncStatus($platform, 'synced', $result['metadata']);
                    $results[$platform] = ['success' => true, 'message' => 'Synced successfully'];
                } else {
                    $audience->updateSyncStatus($platform, 'error', $result['metadata']);
                    $results[$platform] = ['success' => false, 'message' => $result['error']];
                }
            } catch (\Exception $e) {
                $audience->updateSyncStatus($platform, 'error', ['error' => $e->getMessage()]);
                $results[$platform] = ['success' => false, 'message' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    /**
     * Get audience analytics
     */
    public function getAudienceAnalytics(CustomAudience $audience): array
    {
        $leads = $audience->leads;
        
        return [
            'total_leads' => $leads->count(),
            'by_source' => $leads->groupBy('source')->map->count(),
            'by_status' => $leads->groupBy('status')->map->count(),
            'by_score_range' => [
                'high' => $leads->where('score', '>=', 80)->count(),
                'medium' => $leads->whereBetween('score', [50, 79])->count(),
                'low' => $leads->where('score', '<', 50)->count(),
            ],
            'by_location' => $leads->groupBy(function ($lead) {
                return $lead->metadata['location']['country'] ?? 'Unknown';
            })->map->count(),
            'created_over_time' => $leads->groupBy(function ($lead) {
                return $lead->created_at->format('Y-m-d');
            })->map->count(),
            'average_score' => round($leads->avg('score'), 2),
            'conversion_potential' => $this->calculateConversionPotential($leads),
        ];
    }

    /**
     * Generate demo export file
     */
    public function generateDemoExport(): string
    {
        try {
            $filename = 'demo_leads_template.csv';
            $path = 'exports/' . $filename;
            $fullPath = Storage::path($path);
            
            Storage::makeDirectory('exports');
            
            $csv = Writer::createFromPath($fullPath, 'w+');
            
            // Add headers
            $csv->insertOne([
                'email',
                'phone',
                'first_name',
                'last_name',
                'source',
                'status',
                'score',
                'city',
                'country',
                'company',
                'job_title'
            ]);
            
            // Add sample data
            $sampleData = [
                ['john.doe@example.com', '+1234567890', 'John', 'Doe', 'facebook', 'new', 85, 'New York', 'USA', 'Tech Corp', 'Manager'],
                ['jane.smith@example.com', '+1234567891', 'Jane', 'Smith', 'google', 'qualified', 92, 'Los Angeles', 'USA', 'Marketing Inc', 'Director'],
                ['ahmed.hassan@example.com', '+971501234567', 'Ahmed', 'Hassan', 'linkedin', 'converted', 78, 'Dubai', 'UAE', 'Business Solutions', 'CEO'],
                ['sara.johnson@example.com', '+44207123456', 'Sara', 'Johnson', 'website', 'new', 65, 'London', 'UK', 'Consulting Ltd', 'Analyst'],
                ['mohamed.ali@example.com', '+966501234567', 'Mohamed', 'Ali', 'referral', 'qualified', 88, 'Riyadh', 'Saudi Arabia', 'Tech Solutions', 'CTO'],
            ];
            
            foreach ($sampleData as $row) {
                $csv->insertOne($row);
            }
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Failed to generate demo export', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Private helper methods
     */
    private function validateLeadsFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }
        
        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB limit
            throw new \InvalidArgumentException('File size too large (max 10MB)');
        }
        
        $allowedMimes = ['text/csv', 'application/csv', 'text/plain'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid file type. Only CSV files are allowed');
        }
    }

    private function processLeadsCSV(string $filePath, int $tenantId, int $userId): array
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        
        $records = $csv->getRecords();
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($records as $offset => $record) {
            try {
                // Validate required fields
                if (empty($record['email'])) {
                    $skipped++;
                    $errors[] = "Row {$offset}: Email is required";
                    continue;
                }
                
                // Check if lead already exists
                $existingLead = Lead::where('tenant_id', $tenantId)
                    ->where('email', $record['email'])
                    ->first();
                
                if ($existingLead) {
                    $skipped++;
                    continue;
                }
                
                // Create lead
                Lead::create([
                    'tenant_id' => $tenantId,
                    'email' => $record['email'],
                    'phone' => $record['phone'] ?? null,
                    'first_name' => $record['first_name'] ?? null,
                    'last_name' => $record['last_name'] ?? null,
                    'source' => $record['source'] ?? 'import',
                    'status' => $record['status'] ?? 'new',
                    'score' => (int)($record['score'] ?? 50),
                    'metadata' => [
                        'location' => [
                            'city' => $record['city'] ?? null,
                            'country' => $record['country'] ?? null,
                        ],
                        'company' => $record['company'] ?? null,
                        'job_title' => $record['job_title'] ?? null,
                        'imported_by' => $userId,
                        'imported_at' => now()->toISOString(),
                    ],
                ]);
                
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$offset}: " . $e->getMessage();
                $skipped++;
            }
        }
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_processed' => $imported + $skipped,
        ];
    }

    private function syncToPlatform(CustomAudience $audience, string $platform): array
    {
        // Mock implementation - in real app, this would integrate with platform APIs
        switch ($platform) {
            case 'facebook':
                return $this->syncToFacebook($audience);
            case 'google':
                return $this->syncToGoogle($audience);
            case 'tiktok':
                return $this->syncToTikTok($audience);
            default:
                return ['success' => false, 'error' => 'Unsupported platform'];
        }
    }

    private function syncToFacebook(CustomAudience $audience): array
    {
        // Mock Facebook sync
        sleep(1); // Simulate API call
        return [
            'success' => true,
            'metadata' => [
                'facebook_audience_id' => 'fb_' . $audience->id . '_' . time(),
                'synced_leads' => $audience->lead_count,
                'match_rate' => rand(70, 95) . '%',
            ]
        ];
    }

    private function syncToGoogle(CustomAudience $audience): array
    {
        // Mock Google sync
        sleep(1); // Simulate API call
        return [
            'success' => true,
            'metadata' => [
                'google_audience_id' => 'ggl_' . $audience->id . '_' . time(),
                'synced_leads' => $audience->lead_count,
                'match_rate' => rand(65, 90) . '%',
            ]
        ];
    }

    private function syncToTikTok(CustomAudience $audience): array
    {
        // Mock TikTok sync
        sleep(1); // Simulate API call
        return [
            'success' => true,
            'metadata' => [
                'tiktok_audience_id' => 'tt_' . $audience->id . '_' . time(),
                'synced_leads' => $audience->lead_count,
                'match_rate' => rand(60, 85) . '%',
            ]
        ];
    }

    private function calculateConversionPotential($leads): array
    {
        $highScore = $leads->where('score', '>=', 80)->count();
        $mediumScore = $leads->whereBetween('score', [60, 79])->count();
        $lowScore = $leads->where('score', '<', 60)->count();
        
        return [
            'high_potential' => $highScore,
            'medium_potential' => $mediumScore,
            'low_potential' => $lowScore,
            'estimated_conversion_rate' => round(($highScore * 0.15 + $mediumScore * 0.08 + $lowScore * 0.03) / $leads->count() * 100, 2),
        ];
    }
}
