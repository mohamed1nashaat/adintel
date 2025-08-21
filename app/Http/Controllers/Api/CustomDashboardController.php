<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomWidget;
use App\Models\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomDashboardController extends Controller
{
    /**
     * Get custom widgets for dashboard
     */
    public function widgets(Request $request): JsonResponse
    {
        $widgets = CustomWidget::where('tenant_id', session('current_tenant_id'))
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        return response()->json(['data' => $widgets]);
    }

    /**
     * Create a custom widget
     */
    public function createWidget(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'config' => 'required|array',
            'position' => 'nullable|integer',
            'size' => 'nullable|string|in:small,medium,large,full',
        ]);

        $widget = CustomWidget::create([
            'tenant_id' => session('current_tenant_id'),
            'created_by' => $request->user()->id,
            'name' => $request->name,
            'type' => $request->type,
            'config' => $request->config,
            'position' => $request->position ?? 0,
            'size' => $request->size ?? 'medium',
            'is_active' => true,
        ]);

        return response()->json($widget, 201);
    }

    /**
     * Update widget configuration
     */
    public function updateWidget(Request $request, CustomWidget $widget): JsonResponse
    {
        $this->authorize('update', $widget);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'config' => 'sometimes|array',
            'position' => 'sometimes|integer',
            'size' => 'sometimes|string|in:small,medium,large,full',
            'is_active' => 'sometimes|boolean',
        ]);

        $widget->update($request->only([
            'name', 'config', 'position', 'size', 'is_active'
        ]));

        return response()->json($widget);
    }

    /**
     * Delete a custom widget
     */
    public function deleteWidget(CustomWidget $widget): JsonResponse
    {
        $this->authorize('delete', $widget);

        $widget->delete();

        return response()->json(['message' => 'Widget deleted successfully']);
    }

    /**
     * Get available widget types
     */
    public function widgetTypes(): JsonResponse
    {
        $types = [
            [
                'type' => 'kpi_card',
                'name' => 'KPI Card',
                'description' => 'Display key performance indicators',
                'config_schema' => [
                    'metric' => 'string',
                    'title' => 'string',
                    'format' => 'string',
                ]
            ],
            [
                'type' => 'chart',
                'name' => 'Chart Widget',
                'description' => 'Display data in various chart formats',
                'config_schema' => [
                    'chart_type' => 'string',
                    'data_source' => 'string',
                    'metrics' => 'array',
                ]
            ],
            [
                'type' => 'table',
                'name' => 'Data Table',
                'description' => 'Display tabular data',
                'config_schema' => [
                    'data_source' => 'string',
                    'columns' => 'array',
                    'filters' => 'array',
                ]
            ],
            [
                'type' => 'progress',
                'name' => 'Progress Bar',
                'description' => 'Show progress towards goals',
                'config_schema' => [
                    'current_value' => 'number',
                    'target_value' => 'number',
                    'title' => 'string',
                ]
            ],
        ];

        return response()->json(['data' => $types]);
    }

    /**
     * Save dashboard layout
     */
    public function saveLayout(Request $request): JsonResponse
    {
        $request->validate([
            'layout' => 'required|array',
            'dashboard_id' => 'nullable|exists:dashboards,id',
        ]);

        $dashboard = null;
        if ($request->dashboard_id) {
            $dashboard = Dashboard::findOrFail($request->dashboard_id);
            $this->authorize('update', $dashboard);
        } else {
            $dashboard = Dashboard::create([
                'tenant_id' => session('current_tenant_id'),
                'user_id' => $request->user()->id,
                'name' => 'Custom Dashboard',
                'config' => [],
            ]);
        }

        $dashboard->update([
            'config' => array_merge($dashboard->config ?? [], [
                'layout' => $request->layout,
                'updated_at' => now(),
            ])
        ]);

        return response()->json([
            'message' => 'Layout saved successfully',
            'dashboard_id' => $dashboard->id,
        ]);
    }

    /**
     * Get dashboard layout
     */
    public function getLayout(Request $request): JsonResponse
    {
        $dashboardId = $request->get('dashboard_id');
        
        if ($dashboardId) {
            $dashboard = Dashboard::findOrFail($dashboardId);
            $this->authorize('view', $dashboard);
            
            return response()->json([
                'layout' => $dashboard->config['layout'] ?? [],
                'dashboard' => $dashboard,
            ]);
        }

        // Return default layout
        return response()->json([
            'layout' => $this->getDefaultLayout(),
            'dashboard' => null,
        ]);
    }

    /**
     * Get default dashboard layout
     */
    private function getDefaultLayout(): array
    {
        return [
            [
                'id' => 'kpi-overview',
                'type' => 'kpi_card',
                'position' => ['x' => 0, 'y' => 0, 'w' => 3, 'h' => 2],
                'config' => [
                    'title' => 'Total Spend',
                    'metric' => 'total_spend',
                    'format' => 'currency',
                ]
            ],
            [
                'id' => 'performance-chart',
                'type' => 'chart',
                'position' => ['x' => 3, 'y' => 0, 'w' => 6, 'h' => 4],
                'config' => [
                    'title' => 'Performance Over Time',
                    'chart_type' => 'line',
                    'data_source' => 'metrics',
                    'metrics' => ['impressions', 'clicks', 'conversions'],
                ]
            ],
            [
                'id' => 'campaign-table',
                'type' => 'table',
                'position' => ['x' => 0, 'y' => 4, 'w' => 9, 'h' => 4],
                'config' => [
                    'title' => 'Campaign Performance',
                    'data_source' => 'campaigns',
                    'columns' => ['name', 'spend', 'impressions', 'clicks', 'ctr', 'cpc'],
                ]
            ],
        ];
    }
}
