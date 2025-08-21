<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeatureFlagController extends Controller
{
    /**
     * Display a listing of feature flags
     */
    public function index(Request $request): JsonResponse
    {
        $query = FeatureFlag::with(['createdBy'])
            ->where('tenant_id', session('current_tenant_id'));

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by environment
        if ($request->has('environment')) {
            $query->where('environment', $request->environment);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $flags = $query->orderBy('name')
                      ->paginate($request->get('per_page', 15));

        return response()->json($flags);
    }

    /**
     * Store a newly created feature flag
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:feature_flags,name,NULL,id,tenant_id,' . session('current_tenant_id'),
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'environment' => 'required|in:development,staging,production,all',
            'rollout_percentage' => 'nullable|integer|min:0|max:100',
            'target_audience' => 'nullable|array',
            'conditions' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $flag = FeatureFlag::create([
            'tenant_id' => session('current_tenant_id'),
            'created_by' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active,
            'environment' => $request->environment,
            'rollout_percentage' => $request->rollout_percentage ?? 100,
            'target_audience' => $request->target_audience,
            'conditions' => $request->conditions,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json($flag->load('createdBy'), 201);
    }

    /**
     * Display the specified feature flag
     */
    public function show(FeatureFlag $featureFlag): JsonResponse
    {
        $this->authorize('view', $featureFlag);

        return response()->json($featureFlag->load(['createdBy']));
    }

    /**
     * Update the specified feature flag
     */
    public function update(Request $request, FeatureFlag $featureFlag): JsonResponse
    {
        $this->authorize('update', $featureFlag);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:feature_flags,name,' . $featureFlag->id . ',id,tenant_id,' . session('current_tenant_id'),
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'environment' => 'sometimes|in:development,staging,production,all',
            'rollout_percentage' => 'nullable|integer|min:0|max:100',
            'target_audience' => 'nullable|array',
            'conditions' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $featureFlag->update($request->only([
            'name', 'description', 'is_active', 'environment', 'rollout_percentage',
            'target_audience', 'conditions', 'start_date', 'end_date'
        ]));

        return response()->json($featureFlag->load('createdBy'));
    }

    /**
     * Remove the specified feature flag
     */
    public function destroy(FeatureFlag $featureFlag): JsonResponse
    {
        $this->authorize('delete', $featureFlag);

        $featureFlag->delete();

        return response()->json(['message' => 'Feature flag deleted successfully']);
    }

    /**
     * Toggle feature flag status
     */
    public function toggle(FeatureFlag $featureFlag): JsonResponse
    {
        $this->authorize('update', $featureFlag);

        $featureFlag->update([
            'is_active' => !$featureFlag->is_active,
        ]);

        return response()->json([
            'message' => 'Feature flag toggled successfully',
            'is_active' => $featureFlag->is_active,
        ]);
    }

    /**
     * Check if a feature is enabled for current user/context
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'feature_name' => 'required|string',
            'user_id' => 'nullable|integer',
            'context' => 'nullable|array',
        ]);

        $flag = FeatureFlag::where('tenant_id', session('current_tenant_id'))
            ->where('name', $request->feature_name)
            ->first();

        if (!$flag) {
            return response()->json([
                'enabled' => false,
                'reason' => 'Feature flag not found',
            ]);
        }

        $enabled = $this->isFeatureEnabled($flag, $request->user_id, $request->context ?? []);

        return response()->json([
            'enabled' => $enabled,
            'flag' => $flag,
            'reason' => $enabled ? 'Feature is enabled' : 'Feature is disabled',
        ]);
    }

    /**
     * Get all enabled features for current context
     */
    public function enabled(Request $request): JsonResponse
    {
        $flags = FeatureFlag::where('tenant_id', session('current_tenant_id'))
            ->where('is_active', true)
            ->get();

        $enabledFeatures = [];
        $userId = $request->get('user_id');
        $context = $request->get('context', []);

        foreach ($flags as $flag) {
            if ($this->isFeatureEnabled($flag, $userId, $context)) {
                $enabledFeatures[] = $flag->name;
            }
        }

        return response()->json([
            'enabled_features' => $enabledFeatures,
            'total_flags' => $flags->count(),
            'enabled_count' => count($enabledFeatures),
        ]);
    }

    /**
     * Get feature flag statistics
     */
    public function stats(): JsonResponse
    {
        $tenantId = session('current_tenant_id');

        $stats = [
            'total' => FeatureFlag::where('tenant_id', $tenantId)->count(),
            'active' => FeatureFlag::where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'inactive' => FeatureFlag::where('tenant_id', $tenantId)->where('is_active', false)->count(),
            'by_environment' => FeatureFlag::where('tenant_id', $tenantId)
                ->selectRaw('environment, COUNT(*) as count')
                ->groupBy('environment')
                ->pluck('count', 'environment'),
            'scheduled' => FeatureFlag::where('tenant_id', $tenantId)
                ->whereNotNull('start_date')
                ->where('start_date', '>', now())
                ->count(),
            'expiring_soon' => FeatureFlag::where('tenant_id', $tenantId)
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(7))
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Bulk update feature flags
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'flag_ids' => 'required|array',
            'flag_ids.*' => 'exists:feature_flags,id',
            'action' => 'required|in:activate,deactivate,delete',
        ]);

        $flags = FeatureFlag::where('tenant_id', session('current_tenant_id'))
            ->whereIn('id', $request->flag_ids)
            ->get();

        $updated = 0;

        foreach ($flags as $flag) {
            switch ($request->action) {
                case 'activate':
                    $flag->update(['is_active' => true]);
                    $updated++;
                    break;
                case 'deactivate':
                    $flag->update(['is_active' => false]);
                    $updated++;
                    break;
                case 'delete':
                    $flag->delete();
                    $updated++;
                    break;
            }
        }

        return response()->json([
            'message' => "Bulk {$request->action} completed successfully",
            'updated_count' => $updated,
        ]);
    }

    /**
     * Check if a feature is enabled based on conditions
     */
    private function isFeatureEnabled(FeatureFlag $flag, ?int $userId = null, array $context = []): bool
    {
        // Check if flag is active
        if (!$flag->is_active) {
            return false;
        }

        // Check date range
        if ($flag->start_date && now()->lt($flag->start_date)) {
            return false;
        }

        if ($flag->end_date && now()->gt($flag->end_date)) {
            return false;
        }

        // Check rollout percentage
        if ($flag->rollout_percentage < 100) {
            $hash = crc32($flag->name . ($userId ?? 'anonymous'));
            $percentage = abs($hash) % 100;
            if ($percentage >= $flag->rollout_percentage) {
                return false;
            }
        }

        // Check target audience conditions
        if ($flag->target_audience && !empty($flag->target_audience)) {
            // Simple implementation - in production, you'd have more sophisticated targeting
            if (isset($flag->target_audience['user_ids']) && $userId) {
                if (!in_array($userId, $flag->target_audience['user_ids'])) {
                    return false;
                }
            }
        }

        // Check custom conditions
        if ($flag->conditions && !empty($flag->conditions)) {
            // Simple implementation - in production, you'd have a condition evaluator
            foreach ($flag->conditions as $condition) {
                if (!$this->evaluateCondition($condition, $context)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition(array $condition, array $context): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        if (!$field || !isset($context[$field])) {
            return false;
        }

        $contextValue = $context[$field];

        switch ($operator) {
            case 'equals':
                return $contextValue == $value;
            case 'not_equals':
                return $contextValue != $value;
            case 'greater_than':
                return $contextValue > $value;
            case 'less_than':
                return $contextValue < $value;
            case 'contains':
                return str_contains($contextValue, $value);
            case 'in':
                return in_array($contextValue, (array) $value);
            default:
                return false;
        }
    }
}
