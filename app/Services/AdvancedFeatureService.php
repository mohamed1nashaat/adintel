<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdvancedFeatureService
{
    /**
     * Check if feature is enabled for tenant
     */
    public function isFeatureEnabled(int $tenantId, string $featureKey): bool
    {
        try {
            $cacheKey = "feature_flag_{$tenantId}_{$featureKey}";
            
            return Cache::remember($cacheKey, 300, function () use ($tenantId, $featureKey) {
                $flag = FeatureFlag::where('tenant_id', $tenantId)
                    ->where('feature_key', $featureKey)
                    ->where('is_active', true)
                    ->first();

                if (!$flag) {
                    // Check global feature flag
                    $globalFlag = FeatureFlag::whereNull('tenant_id')
                        ->where('feature_key', $featureKey)
                        ->where('is_active', true)
                        ->first();
                    
                    return $globalFlag ? $this->evaluateFeatureConditions($globalFlag, $tenantId) : false;
                }

                return $this->evaluateFeatureConditions($flag, $tenantId);
            });
        } catch (\Exception $e) {
            Log::error('Failed to check feature flag', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'feature_key' => $featureKey
            ]);
            return false;
        }
    }

    /**
     * Get all features for tenant
     */
    public function getTenantFeatures(int $tenantId): array
    {
        try {
            $tenantFlags = FeatureFlag::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get()
                ->keyBy('feature_key');

            $globalFlags = FeatureFlag::whereNull('tenant_id')
                ->where('is_active', true)
                ->get()
                ->keyBy('feature_key');

            $allFeatures = $this->getAvailableFeatures();
            $enabledFeatures = [];

            foreach ($allFeatures as $featureKey => $featureInfo) {
                $isEnabled = false;
                
                if ($tenantFlags->has($featureKey)) {
                    $isEnabled = $this->evaluateFeatureConditions($tenantFlags[$featureKey], $tenantId);
                } elseif ($globalFlags->has($featureKey)) {
                    $isEnabled = $this->evaluateFeatureConditions($globalFlags[$featureKey], $tenantId);
                }

                $enabledFeatures[$featureKey] = [
                    'enabled' => $isEnabled,
                    'name' => $featureInfo['name'],
                    'description' => $featureInfo['description'],
                    'category' => $featureInfo['category'],
                    'premium' => $featureInfo['premium'] ?? false,
                ];
            }

            return $enabledFeatures;
        } catch (\Exception $e) {
            Log::error('Failed to get tenant features', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            throw $e;
        }
    }

    /**
     * Enable feature for tenant
     */
    public function enableFeature(int $tenantId, string $featureKey, array $conditions = []): bool
    {
        try {
            $flag = FeatureFlag::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'feature_key' => $featureKey,
                ],
                [
                    'is_active' => true,
                    'conditions' => $conditions,
                    'enabled_at' => now(),
                ]
            );

            // Clear cache
            $this->clearFeatureCache($tenantId, $featureKey);

            Log::info('Feature enabled for tenant', [
                'tenant_id' => $tenantId,
                'feature_key' => $featureKey,
                'flag_id' => $flag->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to enable feature', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'feature_key' => $featureKey
            ]);
            throw $e;
        }
    }

    /**
     * Disable feature for tenant
     */
    public function disableFeature(int $tenantId, string $featureKey): bool
    {
        try {
            $flag = FeatureFlag::where('tenant_id', $tenantId)
                ->where('feature_key', $featureKey)
                ->first();

            if ($flag) {
                $flag->update([
                    'is_active' => false,
                    'disabled_at' => now(),
                ]);
            }

            // Clear cache
            $this->clearFeatureCache($tenantId, $featureKey);

            Log::info('Feature disabled for tenant', [
                'tenant_id' => $tenantId,
                'feature_key' => $featureKey
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disable feature', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'feature_key' => $featureKey
            ]);
            throw $e;
        }
    }

    /**
     * Create global feature flag
     */
    public function createGlobalFeature(string $featureKey, array $data): array
    {
        try {
            $flag = FeatureFlag::create([
                'tenant_id' => null,
                'feature_key' => $featureKey,
                'is_active' => $data['is_active'] ?? true,
                'conditions' => $data['conditions'] ?? [],
                'rollout_percentage' => $data['rollout_percentage'] ?? 100,
                'enabled_at' => $data['is_active'] ? now() : null,
            ]);

            // Clear all tenant caches for this feature
            $this->clearAllFeatureCaches($featureKey);

            Log::info('Global feature flag created', [
                'feature_key' => $featureKey,
                'flag_id' => $flag->id
            ]);

            return $flag->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to create global feature flag', [
                'error' => $e->getMessage(),
                'feature_key' => $featureKey
            ]);
            throw $e;
        }
    }

    /**
     * Get feature usage analytics
     */
    public function getFeatureAnalytics(string $featureKey, array $filters = []): array
    {
        try {
            $query = FeatureFlag::where('feature_key', $featureKey);

            if (!empty($filters['tenant_id'])) {
                $query->where('tenant_id', $filters['tenant_id']);
            }

            if (!empty($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $flags = $query->get();

            $analytics = [
                'total_flags' => $flags->count(),
                'active_flags' => $flags->where('is_active', true)->count(),
                'inactive_flags' => $flags->where('is_active', false)->count(),
                'tenant_flags' => $flags->whereNotNull('tenant_id')->count(),
                'global_flags' => $flags->whereNull('tenant_id')->count(),
                'usage_by_tenant' => [],
                'rollout_stats' => [],
            ];

            // Usage by tenant
            $tenantUsage = $flags->whereNotNull('tenant_id')
                ->groupBy('tenant_id')
                ->map(function ($tenantFlags) {
                    return [
                        'total' => $tenantFlags->count(),
                        'active' => $tenantFlags->where('is_active', true)->count(),
                        'last_updated' => $tenantFlags->max('updated_at'),
                    ];
                });

            $analytics['usage_by_tenant'] = $tenantUsage;

            // Rollout statistics
            $rolloutStats = $flags->groupBy('rollout_percentage')
                ->map(function ($group, $percentage) {
                    return [
                        'percentage' => $percentage,
                        'count' => $group->count(),
                        'active' => $group->where('is_active', true)->count(),
                    ];
                });

            $analytics['rollout_stats'] = $rolloutStats;

            return $analytics;
        } catch (\Exception $e) {
            Log::error('Failed to get feature analytics', [
                'error' => $e->getMessage(),
                'feature_key' => $featureKey
            ]);
            throw $e;
        }
    }

    /**
     * Bulk update feature flags
     */
    public function bulkUpdateFeatures(array $updates): array
    {
        try {
            DB::beginTransaction();

            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => []
            ];

            foreach ($updates as $update) {
                try {
                    if ($update['action'] === 'enable') {
                        $this->enableFeature(
                            $update['tenant_id'],
                            $update['feature_key'],
                            $update['conditions'] ?? []
                        );
                    } elseif ($update['action'] === 'disable') {
                        $this->disableFeature($update['tenant_id'], $update['feature_key']);
                    }
                    
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'tenant_id' => $update['tenant_id'],
                        'feature_key' => $update['feature_key'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk feature update completed', [
                'total' => count($updates),
                'success' => $results['success'],
                'failed' => $results['failed']
            ]);

            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk update features', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get feature rollout status
     */
    public function getFeatureRolloutStatus(string $featureKey): array
    {
        try {
            $flags = FeatureFlag::where('feature_key', $featureKey)->get();
            
            $totalTenants = Tenant::count();
            $tenantsWithFeature = $flags->whereNotNull('tenant_id')->count();
            $activeFlags = $flags->where('is_active', true)->count();
            
            $rolloutPercentage = $totalTenants > 0 ? ($tenantsWithFeature / $totalTenants) * 100 : 0;
            
            return [
                'feature_key' => $featureKey,
                'total_tenants' => $totalTenants,
                'tenants_with_feature' => $tenantsWithFeature,
                'active_flags' => $activeFlags,
                'rollout_percentage' => round($rolloutPercentage, 2),
                'global_flag_exists' => $flags->whereNull('tenant_id')->isNotEmpty(),
                'rollout_status' => $this->determineRolloutStatus($rolloutPercentage),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get feature rollout status', [
                'error' => $e->getMessage(),
                'feature_key' => $featureKey
            ]);
            throw $e;
        }
    }

    /**
     * Get available features
     */
    public function getAvailableFeatures(): array
    {
        return [
            'advanced_analytics' => [
                'name' => 'Advanced Analytics',
                'description' => 'Enhanced analytics with custom metrics and advanced reporting',
                'category' => 'analytics',
                'premium' => true,
            ],
            'white_label' => [
                'name' => 'White Label',
                'description' => 'Custom branding and white-label capabilities',
                'category' => 'branding',
                'premium' => true,
            ],
            'api_access' => [
                'name' => 'API Access',
                'description' => 'Full API access for integrations and custom development',
                'category' => 'integration',
                'premium' => true,
            ],
            'custom_dashboards' => [
                'name' => 'Custom Dashboards',
                'description' => 'Create and customize unlimited dashboards',
                'category' => 'dashboard',
                'premium' => false,
            ],
            'automated_reporting' => [
                'name' => 'Automated Reporting',
                'description' => 'Scheduled reports and automated insights',
                'category' => 'reporting',
                'premium' => true,
            ],
            'advanced_integrations' => [
                'name' => 'Advanced Integrations',
                'description' => 'Connect with CRM, email marketing, and other tools',
                'category' => 'integration',
                'premium' => true,
            ],
            'ai_insights' => [
                'name' => 'AI Insights',
                'description' => 'AI-powered recommendations and insights',
                'category' => 'ai',
                'premium' => true,
            ],
            'bulk_operations' => [
                'name' => 'Bulk Operations',
                'description' => 'Bulk import/export and batch operations',
                'category' => 'operations',
                'premium' => false,
            ],
            'advanced_filters' => [
                'name' => 'Advanced Filters',
                'description' => 'Complex filtering and search capabilities',
                'category' => 'interface',
                'premium' => false,
            ],
            'priority_support' => [
                'name' => 'Priority Support',
                'description' => '24/7 priority customer support',
                'category' => 'support',
                'premium' => true,
            ],
        ];
    }

    /**
     * Private helper methods
     */
    private function evaluateFeatureConditions(FeatureFlag $flag, int $tenantId): bool
    {
        try {
            // Check rollout percentage
            if ($flag->rollout_percentage < 100) {
                $hash = crc32($tenantId . $flag->feature_key);
                $percentage = abs($hash) % 100;
                if ($percentage >= $flag->rollout_percentage) {
                    return false;
                }
            }

            // Evaluate custom conditions
            if (!empty($flag->conditions)) {
                return $this->evaluateCustomConditions($flag->conditions, $tenantId);
            }

            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to evaluate feature conditions', [
                'error' => $e->getMessage(),
                'flag_id' => $flag->id,
                'tenant_id' => $tenantId
            ]);
            return false;
        }
    }

    private function evaluateCustomConditions(array $conditions, int $tenantId): bool
    {
        try {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return false;
            }

            foreach ($conditions as $condition) {
                $field = $condition['field'] ?? '';
                $operator = $condition['operator'] ?? '=';
                $value = $condition['value'] ?? '';

                switch ($field) {
                    case 'tenant_status':
                        if (!$this->compareValues($tenant->status, $operator, $value)) {
                            return false;
                        }
                        break;
                    case 'user_count':
                        $userCount = $tenant->users()->count();
                        if (!$this->compareValues($userCount, $operator, $value)) {
                            return false;
                        }
                        break;
                    case 'created_days_ago':
                        $daysAgo = $tenant->created_at->diffInDays(now());
                        if (!$this->compareValues($daysAgo, $operator, $value)) {
                            return false;
                        }
                        break;
                    default:
                        // Custom field evaluation can be added here
                        break;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to evaluate custom conditions', [
                'error' => $e->getMessage(),
                'conditions' => $conditions,
                'tenant_id' => $tenantId
            ]);
            return false;
        }
    }

    private function compareValues($actual, string $operator, $expected): bool
    {
        switch ($operator) {
            case '=':
            case '==':
                return $actual == $expected;
            case '!=':
                return $actual != $expected;
            case '>':
                return $actual > $expected;
            case '>=':
                return $actual >= $expected;
            case '<':
                return $actual < $expected;
            case '<=':
                return $actual <= $expected;
            case 'in':
                return in_array($actual, (array) $expected);
            case 'not_in':
                return !in_array($actual, (array) $expected);
            default:
                return false;
        }
    }

    private function clearFeatureCache(int $tenantId, string $featureKey): void
    {
        $cacheKey = "feature_flag_{$tenantId}_{$featureKey}";
        Cache::forget($cacheKey);
    }

    private function clearAllFeatureCaches(string $featureKey): void
    {
        // This would ideally use cache tags, but for simplicity we'll log it
        Log::info('Clearing all feature caches', ['feature_key' => $featureKey]);
        
        // In a production environment, you might want to implement a more sophisticated cache clearing strategy
        // For example, using Redis with pattern matching or cache tags
    }

    private function determineRolloutStatus(float $percentage): string
    {
        if ($percentage == 0) {
            return 'not_started';
        } elseif ($percentage < 25) {
            return 'limited';
        } elseif ($percentage < 75) {
            return 'partial';
        } elseif ($percentage < 100) {
            return 'wide';
        } else {
            return 'complete';
        }
    }
}
