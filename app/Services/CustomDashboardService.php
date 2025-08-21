<?php

namespace App\Services;

use App\Models\CustomWidget;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class CustomDashboardService
{
    /**
     * Create a custom dashboard
     */
    public function createDashboard(int $tenantId, int $userId, array $data): Dashboard
    {
        try {
            $dashboard = Dashboard::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'layout' => $data['layout'] ?? 'grid',
                'settings' => $data['settings'] ?? [],
                'is_default' => $data['is_default'] ?? false,
                'is_shared' => $data['is_shared'] ?? false,
            ]);

            // If this is set as default, unset other defaults
            if ($data['is_default'] ?? false) {
                Dashboard::where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->where('id', '!=', $dashboard->id)
                    ->update(['is_default' => false]);
            }

            Log::info('Custom dashboard created', [
                'dashboard_id' => $dashboard->id,
                'tenant_id' => $tenantId,
                'user_id' => $userId
            ]);

            return $dashboard;
        } catch (\Exception $e) {
            Log::error('Failed to create custom dashboard', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Add widget to dashboard
     */
    public function addWidget(Dashboard $dashboard, array $widgetData): DashboardWidget
    {
        try {
            $widget = DashboardWidget::create([
                'dashboard_id' => $dashboard->id,
                'widget_type' => $widgetData['widget_type'],
                'title' => $widgetData['title'],
                'position_x' => $widgetData['position_x'] ?? 0,
                'position_y' => $widgetData['position_y'] ?? 0,
                'width' => $widgetData['width'] ?? 4,
                'height' => $widgetData['height'] ?? 3,
                'settings' => $widgetData['settings'] ?? [],
                'data_source' => $widgetData['data_source'] ?? null,
                'refresh_interval' => $widgetData['refresh_interval'] ?? 300, // 5 minutes default
            ]);

            Log::info('Widget added to dashboard', [
                'widget_id' => $widget->id,
                'dashboard_id' => $dashboard->id,
                'widget_type' => $widgetData['widget_type']
            ]);

            return $widget;
        } catch (\Exception $e) {
            Log::error('Failed to add widget to dashboard', [
                'error' => $e->getMessage(),
                'dashboard_id' => $dashboard->id
            ]);
            throw $e;
        }
    }

    /**
     * Get available widget types
     */
    public function getAvailableWidgetTypes(): array
    {
        return [
            'kpi_card' => [
                'name' => 'KPI Card',
                'description' => 'Display a single key performance indicator',
                'category' => 'metrics',
                'settings' => [
                    'metric' => ['type' => 'select', 'required' => true],
                    'comparison_period' => ['type' => 'select', 'default' => 'previous_period'],
                    'show_trend' => ['type' => 'boolean', 'default' => true],
                    'color_scheme' => ['type' => 'select', 'default' => 'blue'],
                ]
            ],
            'line_chart' => [
                'name' => 'Line Chart',
                'description' => 'Show trends over time',
                'category' => 'charts',
                'settings' => [
                    'metrics' => ['type' => 'multi_select', 'required' => true],
                    'time_period' => ['type' => 'select', 'default' => '30_days'],
                    'show_legend' => ['type' => 'boolean', 'default' => true],
                    'chart_height' => ['type' => 'number', 'default' => 300],
                ]
            ],
            'bar_chart' => [
                'name' => 'Bar Chart',
                'description' => 'Compare values across categories',
                'category' => 'charts',
                'settings' => [
                    'metric' => ['type' => 'select', 'required' => true],
                    'group_by' => ['type' => 'select', 'required' => true],
                    'limit' => ['type' => 'number', 'default' => 10],
                    'sort_order' => ['type' => 'select', 'default' => 'desc'],
                ]
            ],
            'pie_chart' => [
                'name' => 'Pie Chart',
                'description' => 'Show distribution of values',
                'category' => 'charts',
                'settings' => [
                    'metric' => ['type' => 'select', 'required' => true],
                    'group_by' => ['type' => 'select', 'required' => true],
                    'limit' => ['type' => 'number', 'default' => 8],
                    'show_labels' => ['type' => 'boolean', 'default' => true],
                ]
            ],
            'data_table' => [
                'name' => 'Data Table',
                'description' => 'Display detailed data in table format',
                'category' => 'data',
                'settings' => [
                    'columns' => ['type' => 'multi_select', 'required' => true],
                    'filters' => ['type' => 'array', 'default' => []],
                    'page_size' => ['type' => 'number', 'default' => 10],
                    'sortable' => ['type' => 'boolean', 'default' => true],
                ]
            ],
            'campaign_performance' => [
                'name' => 'Campaign Performance',
                'description' => 'Overview of campaign metrics',
                'category' => 'specialized',
                'settings' => [
                    'campaigns' => ['type' => 'multi_select'],
                    'metrics' => ['type' => 'multi_select', 'required' => true],
                    'time_period' => ['type' => 'select', 'default' => '7_days'],
                ]
            ],
            'top_performers' => [
                'name' => 'Top Performers',
                'description' => 'Show best performing campaigns or ads',
                'category' => 'specialized',
                'settings' => [
                    'metric' => ['type' => 'select', 'required' => true],
                    'entity_type' => ['type' => 'select', 'default' => 'campaigns'],
                    'limit' => ['type' => 'number', 'default' => 5],
                    'time_period' => ['type' => 'select', 'default' => '7_days'],
                ]
            ],
            'budget_tracker' => [
                'name' => 'Budget Tracker',
                'description' => 'Monitor budget utilization',
                'category' => 'specialized',
                'settings' => [
                    'budget_type' => ['type' => 'select', 'default' => 'monthly'],
                    'show_forecast' => ['type' => 'boolean', 'default' => true],
                    'alert_threshold' => ['type' => 'number', 'default' => 80],
                ]
            ],
            'conversion_funnel' => [
                'name' => 'Conversion Funnel',
                'description' => 'Visualize conversion process',
                'category' => 'specialized',
                'settings' => [
                    'funnel_steps' => ['type' => 'array', 'required' => true],
                    'time_period' => ['type' => 'select', 'default' => '30_days'],
                    'show_percentages' => ['type' => 'boolean', 'default' => true],
                ]
            ],
            'alert_center' => [
                'name' => 'Alert Center',
                'description' => 'Display active alerts and notifications',
                'category' => 'monitoring',
                'settings' => [
                    'alert_types' => ['type' => 'multi_select'],
                    'priority_filter' => ['type' => 'select', 'default' => 'all'],
                    'max_alerts' => ['type' => 'number', 'default' => 10],
                ]
            ],
        ];
    }

    /**
     * Get widget data
     */
    public function getWidgetData(DashboardWidget $widget): array
    {
        try {
            switch ($widget->widget_type) {
                case 'kpi_card':
                    return $this->getKpiCardData($widget);
                case 'line_chart':
                    return $this->getLineChartData($widget);
                case 'bar_chart':
                    return $this->getBarChartData($widget);
                case 'pie_chart':
                    return $this->getPieChartData($widget);
                case 'data_table':
                    return $this->getDataTableData($widget);
                case 'campaign_performance':
                    return $this->getCampaignPerformanceData($widget);
                case 'top_performers':
                    return $this->getTopPerformersData($widget);
                case 'budget_tracker':
                    return $this->getBudgetTrackerData($widget);
                case 'conversion_funnel':
                    return $this->getConversionFunnelData($widget);
                case 'alert_center':
                    return $this->getAlertCenterData($widget);
                default:
                    return ['error' => 'Unknown widget type'];
            }
        } catch (\Exception $e) {
            Log::error('Failed to get widget data', [
                'widget_id' => $widget->id,
                'widget_type' => $widget->widget_type,
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Failed to load widget data'];
        }
    }

    /**
     * Clone dashboard
     */
    public function cloneDashboard(Dashboard $dashboard, int $userId, string $newName = null): Dashboard
    {
        try {
            $clonedDashboard = Dashboard::create([
                'tenant_id' => $dashboard->tenant_id,
                'user_id' => $userId,
                'name' => $newName ?? ($dashboard->name . ' (Copy)'),
                'description' => $dashboard->description,
                'layout' => $dashboard->layout,
                'settings' => $dashboard->settings,
                'is_default' => false,
                'is_shared' => false,
            ]);

            // Clone widgets
            foreach ($dashboard->widgets as $widget) {
                DashboardWidget::create([
                    'dashboard_id' => $clonedDashboard->id,
                    'widget_type' => $widget->widget_type,
                    'title' => $widget->title,
                    'position_x' => $widget->position_x,
                    'position_y' => $widget->position_y,
                    'width' => $widget->width,
                    'height' => $widget->height,
                    'settings' => $widget->settings,
                    'data_source' => $widget->data_source,
                    'refresh_interval' => $widget->refresh_interval,
                ]);
            }

            return $clonedDashboard;
        } catch (\Exception $e) {
            Log::error('Failed to clone dashboard', [
                'dashboard_id' => $dashboard->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export dashboard configuration
     */
    public function exportDashboard(Dashboard $dashboard): array
    {
        return [
            'name' => $dashboard->name,
            'description' => $dashboard->description,
            'layout' => $dashboard->layout,
            'settings' => $dashboard->settings,
            'widgets' => $dashboard->widgets->map(function ($widget) {
                return [
                    'widget_type' => $widget->widget_type,
                    'title' => $widget->title,
                    'position_x' => $widget->position_x,
                    'position_y' => $widget->position_y,
                    'width' => $widget->width,
                    'height' => $widget->height,
                    'settings' => $widget->settings,
                    'data_source' => $widget->data_source,
                    'refresh_interval' => $widget->refresh_interval,
                ];
            })->toArray(),
            'exported_at' => now()->toISOString(),
            'version' => '1.0',
        ];
    }

    /**
     * Import dashboard configuration
     */
    public function importDashboard(int $tenantId, int $userId, array $config): Dashboard
    {
        try {
            $dashboard = $this->createDashboard($tenantId, $userId, [
                'name' => $config['name'] . ' (Imported)',
                'description' => $config['description'],
                'layout' => $config['layout'],
                'settings' => $config['settings'],
            ]);

            foreach ($config['widgets'] as $widgetConfig) {
                $this->addWidget($dashboard, $widgetConfig);
            }

            return $dashboard;
        } catch (\Exception $e) {
            Log::error('Failed to import dashboard', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Widget data methods (mock implementations)
     */
    protected function getKpiCardData(DashboardWidget $widget): array
    {
        $metric = $widget->settings['metric'] ?? 'cpm';
        return [
            'value' => rand(100, 1000) / 100,
            'previous_value' => rand(80, 120) / 100,
            'change_percentage' => rand(-20, 30),
            'trend' => rand(0, 1) ? 'up' : 'down',
            'formatted_value' => '$' . number_format(rand(100, 1000) / 100, 2),
        ];
    }

    protected function getLineChartData(DashboardWidget $widget): array
    {
        $days = 30;
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $data[] = [
                'date' => now()->subDays($days - $i)->format('Y-m-d'),
                'value' => rand(50, 200),
            ];
        }
        return ['data' => $data];
    }

    protected function getBarChartData(DashboardWidget $widget): array
    {
        return [
            'data' => [
                ['name' => 'Campaign A', 'value' => rand(100, 500)],
                ['name' => 'Campaign B', 'value' => rand(100, 500)],
                ['name' => 'Campaign C', 'value' => rand(100, 500)],
                ['name' => 'Campaign D', 'value' => rand(100, 500)],
                ['name' => 'Campaign E', 'value' => rand(100, 500)],
            ]
        ];
    }

    protected function getPieChartData(DashboardWidget $widget): array
    {
        return [
            'data' => [
                ['name' => 'Facebook', 'value' => rand(20, 40)],
                ['name' => 'Google', 'value' => rand(20, 40)],
                ['name' => 'TikTok', 'value' => rand(10, 30)],
                ['name' => 'Snapchat', 'value' => rand(5, 20)],
            ]
        ];
    }

    protected function getDataTableData(DashboardWidget $widget): array
    {
        return [
            'columns' => ['Campaign', 'Spend', 'Impressions', 'CTR', 'CPC'],
            'data' => [
                ['Campaign A', '$' . rand(100, 1000), rand(10000, 50000), rand(1, 5) . '%', '$' . rand(1, 10)],
                ['Campaign B', '$' . rand(100, 1000), rand(10000, 50000), rand(1, 5) . '%', '$' . rand(1, 10)],
                ['Campaign C', '$' . rand(100, 1000), rand(10000, 50000), rand(1, 5) . '%', '$' . rand(1, 10)],
            ]
        ];
    }

    protected function getCampaignPerformanceData(DashboardWidget $widget): array
    {
        return [
            'campaigns' => [
                ['name' => 'Summer Sale', 'spend' => rand(1000, 5000), 'roas' => rand(200, 500) / 100],
                ['name' => 'Brand Awareness', 'spend' => rand(1000, 5000), 'roas' => rand(200, 500) / 100],
                ['name' => 'Product Launch', 'spend' => rand(1000, 5000), 'roas' => rand(200, 500) / 100],
            ]
        ];
    }

    protected function getTopPerformersData(DashboardWidget $widget): array
    {
        return [
            'performers' => [
                ['name' => 'Campaign A', 'metric_value' => rand(100, 500), 'metric_name' => 'ROAS'],
                ['name' => 'Campaign B', 'metric_value' => rand(100, 500), 'metric_name' => 'ROAS'],
                ['name' => 'Campaign C', 'metric_value' => rand(100, 500), 'metric_name' => 'ROAS'],
            ]
        ];
    }

    protected function getBudgetTrackerData(DashboardWidget $widget): array
    {
        $budget = rand(5000, 20000);
        $spent = rand(2000, $budget);
        return [
            'total_budget' => $budget,
            'spent' => $spent,
            'remaining' => $budget - $spent,
            'utilization_percentage' => round(($spent / $budget) * 100, 1),
            'days_remaining' => rand(5, 25),
        ];
    }

    protected function getConversionFunnelData(DashboardWidget $widget): array
    {
        return [
            'steps' => [
                ['name' => 'Impressions', 'value' => 100000, 'percentage' => 100],
                ['name' => 'Clicks', 'value' => 5000, 'percentage' => 5],
                ['name' => 'Leads', 'value' => 500, 'percentage' => 0.5],
                ['name' => 'Sales', 'value' => 50, 'percentage' => 0.05],
            ]
        ];
    }

    protected function getAlertCenterData(DashboardWidget $widget): array
    {
        return [
            'alerts' => [
                ['type' => 'budget', 'message' => 'Campaign budget 90% utilized', 'priority' => 'high'],
                ['type' => 'performance', 'message' => 'CTR dropped by 20%', 'priority' => 'medium'],
                ['type' => 'system', 'message' => 'Data sync completed', 'priority' => 'low'],
            ]
        ];
    }
}
