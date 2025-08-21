<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $dashboards = Dashboard::with('widgets')
            ->where('user_id', $request->user()->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($dashboards);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'objective' => 'required|in:awareness,leads,sales,calls',
            'is_default' => 'boolean',
        ]);

        // If setting as default, unset other defaults
        if ($request->is_default) {
            Dashboard::where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        }

        $dashboard = Dashboard::create([
            'tenant_id' => session('current_tenant_id'),
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'objective' => $request->objective,
            'is_default' => $request->is_default ?? false,
        ]);

        // Create default widgets based on objective
        $this->createDefaultWidgets($dashboard);

        return response()->json($dashboard->load('widgets'), 201);
    }

    public function show(Request $request, Dashboard $dashboard)
    {
        $this->authorize('view', $dashboard);

        return response()->json($dashboard->load('widgets'));
    }

    public function update(Request $request, Dashboard $dashboard)
    {
        $this->authorize('update', $dashboard);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'objective' => 'sometimes|in:awareness,leads,sales,calls',
            'is_default' => 'boolean',
        ]);

        // If setting as default, unset other defaults
        if ($request->is_default) {
            Dashboard::where('user_id', $request->user()->id)
                ->where('id', '!=', $dashboard->id)
                ->update(['is_default' => false]);
        }

        $dashboard->update($request->only(['title', 'objective', 'is_default']));

        return response()->json($dashboard->load('widgets'));
    }

    public function destroy(Dashboard $dashboard)
    {
        $this->authorize('delete', $dashboard);

        $dashboard->delete();

        return response()->json(['message' => 'Dashboard deleted successfully']);
    }

    public function addWidget(Request $request, Dashboard $dashboard)
    {
        $this->authorize('update', $dashboard);

        $request->validate([
            'type' => 'required|in:kpi_grid,line_chart,bar_chart,data_table',
            'position' => 'required|integer|min:0',
            'config' => 'required|array',
        ]);

        $widget = $dashboard->widgets()->create([
            'type' => $request->type,
            'position' => $request->position,
            'config' => $request->config,
        ]);

        return response()->json($widget, 201);
    }

    public function updateWidget(Request $request, Dashboard $dashboard, DashboardWidget $widget)
    {
        $this->authorize('update', $dashboard);

        if ($widget->dashboard_id !== $dashboard->id) {
            return response()->json(['message' => 'Widget not found'], 404);
        }

        $request->validate([
            'type' => 'sometimes|in:kpi_grid,line_chart,bar_chart,data_table',
            'position' => 'sometimes|integer|min:0',
            'config' => 'sometimes|array',
        ]);

        $widget->update($request->only(['type', 'position', 'config']));

        return response()->json($widget);
    }

    public function removeWidget(Dashboard $dashboard, DashboardWidget $widget)
    {
        $this->authorize('update', $dashboard);

        if ($widget->dashboard_id !== $dashboard->id) {
            return response()->json(['message' => 'Widget not found'], 404);
        }

        $widget->delete();

        return response()->json(['message' => 'Widget removed successfully']);
    }

    private function createDefaultWidgets(Dashboard $dashboard)
    {
        $widgets = $this->getDefaultWidgetsByObjective($dashboard->objective);

        foreach ($widgets as $position => $widget) {
            $dashboard->widgets()->create([
                'type' => $widget['type'],
                'position' => $position,
                'config' => $widget['config'],
            ]);
        }
    }

    private function getDefaultWidgetsByObjective(string $objective): array
    {
        $baseWidgets = [
            [
                'type' => 'kpi_grid',
                'config' => [
                    'title' => 'Key Performance Indicators',
                    'show_primary' => true,
                    'show_secondary' => true,
                ],
            ],
            [
                'type' => 'line_chart',
                'config' => [
                    'title' => 'Trend Over Time',
                    'metric' => $this->getPrimaryMetricForObjective($objective),
                    'group_by' => 'date',
                ],
            ],
            [
                'type' => 'bar_chart',
                'config' => [
                    'title' => 'Performance by Campaign',
                    'metric' => $this->getPrimaryMetricForObjective($objective),
                    'group_by' => 'campaign',
                ],
            ],
            [
                'type' => 'data_table',
                'config' => [
                    'title' => 'Detailed Metrics',
                    'columns' => $this->getTableColumnsForObjective($objective),
                ],
            ],
        ];

        return $baseWidgets;
    }

    private function getPrimaryMetricForObjective(string $objective): string
    {
        return match ($objective) {
            'awareness' => 'cpm',
            'leads' => 'cpl',
            'sales' => 'roas',
            'calls' => 'cost_per_call',
        };
    }

    private function getTableColumnsForObjective(string $objective): array
    {
        $baseColumns = ['date', 'platform', 'campaign', 'spend', 'impressions', 'clicks'];

        $objectiveColumns = match ($objective) {
            'awareness' => ['reach', 'cpm', 'ctr'],
            'leads' => ['leads', 'cpl', 'cvr'],
            'sales' => ['revenue', 'purchases', 'roas', 'aov'],
            'calls' => ['calls', 'cost_per_call', 'call_conversion_rate'],
        };

        return array_merge($baseColumns, $objectiveColumns);
    }
}
