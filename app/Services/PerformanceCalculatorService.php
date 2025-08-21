<?php

namespace App\Services;

use App\Models\PerformanceBenchmark;
use App\Models\AdMetric;
use App\Models\AdCampaign;
use Illuminate\Support\Collection;

class PerformanceCalculatorService
{
    public function calculatePerformanceAnalysis(
        string $industry,
        string $platform,
        string $objective,
        array $actualMetrics,
        string $region = 'global',
        string $audienceSize = 'medium'
    ): array {
        // Find the most relevant benchmark
        $benchmark = PerformanceBenchmark::findClosestBenchmark(
            $industry,
            $platform,
            $objective,
            $region,
            $audienceSize
        );

        if (!$benchmark) {
            return [
                'error' => 'No benchmark data available for the specified criteria',
                'suggestions' => $this->getGeneralOptimizationSuggestions($objective),
            ];
        }

        // Compare actual metrics with benchmark
        $comparison = $benchmark->compareWithActual($actualMetrics);

        // Generate insights and recommendations
        $insights = $this->generateInsights($comparison, $objective, $platform);
        $recommendations = $this->generateRecommendations($comparison, $objective, $platform);
        $optimizationScore = $this->calculateOptimizationScore($comparison);

        return [
            'benchmark_info' => [
                'industry' => $benchmark->getIndustryLabel(),
                'platform' => $benchmark->getPlatformLabel(),
                'objective' => $benchmark->getObjectiveLabel(),
                'region' => $benchmark->getRegionLabel(),
                'audience_size' => $benchmark->getAudienceSizeLabel(),
                'data_source' => $benchmark->getDataSourceLabel(),
                'period' => [
                    'start' => $benchmark->period_start->format('Y-m-d'),
                    'end' => $benchmark->period_end->format('Y-m-d'),
                ],
            ],
            'performance_comparison' => $comparison,
            'optimization_score' => $optimizationScore,
            'insights' => $insights,
            'recommendations' => $recommendations,
            'action_items' => $this->generateActionItems($comparison, $objective),
        ];
    }

    public function calculateCampaignPerformance(AdCampaign $campaign): array
    {
        // Get campaign metrics
        $metrics = $campaign->adMetrics()
            ->selectRaw('
                SUM(spend) as total_spend,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                SUM(revenue) as total_revenue,
                AVG(cpm) as avg_cpm,
                AVG(cpc) as avg_cpc,
                AVG(ctr) as avg_ctr,
                AVG(cvr) as avg_cvr
            ')
            ->first();

        if (!$metrics || $metrics->total_spend == 0) {
            return ['error' => 'No metrics data available for this campaign'];
        }

        // Calculate derived metrics
        $actualMetrics = [
            'spend' => (float) $metrics->total_spend,
            'impressions' => (int) $metrics->total_impressions,
            'clicks' => (int) $metrics->total_clicks,
            'conversions' => (int) $metrics->total_conversions,
            'revenue' => (float) $metrics->total_revenue,
            'cpm' => (float) $metrics->avg_cpm,
            'cpc' => (float) $metrics->avg_cpc,
            'ctr' => (float) $metrics->avg_ctr,
            'cvr' => (float) $metrics->avg_cvr,
        ];

        // Calculate additional metrics
        if ($actualMetrics['conversions'] > 0) {
            $actualMetrics['cpa'] = $actualMetrics['spend'] / $actualMetrics['conversions'];
        }

        if ($actualMetrics['revenue'] > 0) {
            $actualMetrics['roas'] = $actualMetrics['revenue'] / $actualMetrics['spend'];
        }

        // Get campaign details for benchmark lookup
        $industry = $campaign->adAccount->tenant->settings['industry'] ?? 'general';
        $platform = $campaign->adAccount->integration->platform;
        $objective = $campaign->objective;

        return $this->calculatePerformanceAnalysis(
            $industry,
            $platform,
            $objective,
            $actualMetrics
        );
    }

    public function calculateAccountPerformance(int $adAccountId, array $dateRange = null): array
    {
        $query = AdMetric::whereHas('campaign.account', function ($q) use ($adAccountId) {
            $q->where('id', $adAccountId);
        });

        if ($dateRange) {
            $query->whereBetween('date', [$dateRange['start'], $dateRange['end']]);
        }

        $metrics = $query->selectRaw('
            SUM(spend) as total_spend,
            SUM(impressions) as total_impressions,
            SUM(clicks) as total_clicks,
            SUM(conversions) as total_conversions,
            SUM(revenue) as total_revenue,
            AVG(cpm) as avg_cpm,
            AVG(cpc) as avg_cpc,
            AVG(ctr) as avg_ctr,
            AVG(cvr) as avg_cvr
        ')->first();

        if (!$metrics || $metrics->total_spend == 0) {
            return ['error' => 'No metrics data available for this account'];
        }

        // Get account details
        $account = \App\Models\AdAccount::find($adAccountId);
        $industry = $account->tenant->settings['industry'] ?? 'general';
        $platform = $account->integration->platform;

        // Calculate for different objectives
        $objectives = ['awareness', 'leads', 'sales', 'calls'];
        $results = [];

        foreach ($objectives as $objective) {
            $actualMetrics = $this->prepareMetricsForObjective($metrics, $objective);
            
            $analysis = $this->calculatePerformanceAnalysis(
                $industry,
                $platform,
                $objective,
                $actualMetrics
            );

            if (!isset($analysis['error'])) {
                $results[$objective] = $analysis;
            }
        }

        return [
            'account_info' => [
                'name' => $account->name,
                'platform' => $account->integration->platform,
                'industry' => $industry,
            ],
            'period' => $dateRange,
            'overall_metrics' => $this->formatMetrics($metrics),
            'objective_analysis' => $results,
        ];
    }

    private function prepareMetricsForObjective($metrics, string $objective): array
    {
        $baseMetrics = [
            'spend' => (float) $metrics->total_spend,
            'impressions' => (int) $metrics->total_impressions,
            'clicks' => (int) $metrics->total_clicks,
            'cpm' => (float) $metrics->avg_cpm,
            'cpc' => (float) $metrics->avg_cpc,
            'ctr' => (float) $metrics->avg_ctr,
        ];

        switch ($objective) {
            case 'awareness':
                // Focus on reach and frequency metrics
                $baseMetrics['reach'] = $metrics->total_impressions * 0.7; // Estimated
                $baseMetrics['frequency'] = $metrics->total_impressions / ($baseMetrics['reach'] ?: 1);
                break;

            case 'leads':
                $baseMetrics['conversions'] = (int) $metrics->total_conversions;
                $baseMetrics['cvr'] = (float) $metrics->avg_cvr;
                if ($baseMetrics['conversions'] > 0) {
                    $baseMetrics['cpl'] = $baseMetrics['spend'] / $baseMetrics['conversions'];
                }
                break;

            case 'sales':
                $baseMetrics['conversions'] = (int) $metrics->total_conversions;
                $baseMetrics['revenue'] = (float) $metrics->total_revenue;
                $baseMetrics['cvr'] = (float) $metrics->avg_cvr;
                if ($baseMetrics['conversions'] > 0) {
                    $baseMetrics['cpa'] = $baseMetrics['spend'] / $baseMetrics['conversions'];
                }
                if ($baseMetrics['revenue'] > 0) {
                    $baseMetrics['roas'] = $baseMetrics['revenue'] / $baseMetrics['spend'];
                }
                break;

            case 'calls':
                // Assume conversions are calls for this objective
                $baseMetrics['calls'] = (int) $metrics->total_conversions;
                if ($baseMetrics['calls'] > 0) {
                    $baseMetrics['cost_per_call'] = $baseMetrics['spend'] / $baseMetrics['calls'];
                }
                break;
        }

        return $baseMetrics;
    }

    private function generateInsights(array $comparison, string $objective, string $platform): array
    {
        $insights = [];

        foreach ($comparison as $metric => $data) {
            $performance = $data['performance'];
            $percentageDiff = $data['percentage_difference'];

            switch ($performance) {
                case 'excellent':
                    $insights[] = [
                        'type' => 'success',
                        'metric' => $metric,
                        'message' => "Your {$metric} is performing excellently, " . abs($percentageDiff) . "% better than industry benchmark.",
                        'impact' => 'high_positive',
                    ];
                    break;

                case 'good':
                    $insights[] = [
                        'type' => 'success',
                        'metric' => $metric,
                        'message' => "Your {$metric} is performing well, " . abs($percentageDiff) . "% better than industry benchmark.",
                        'impact' => 'medium_positive',
                    ];
                    break;

                case 'below_average':
                    $insights[] = [
                        'type' => 'warning',
                        'metric' => $metric,
                        'message' => "Your {$metric} is below industry average by " . abs($percentageDiff) . "%. Consider optimization.",
                        'impact' => 'medium_negative',
                    ];
                    break;

                case 'poor':
                    $insights[] = [
                        'type' => 'error',
                        'metric' => $metric,
                        'message' => "Your {$metric} is significantly underperforming, " . abs($percentageDiff) . "% worse than benchmark. Immediate action needed.",
                        'impact' => 'high_negative',
                    ];
                    break;
            }
        }

        return $insights;
    }

    private function generateRecommendations(array $comparison, string $objective, string $platform): array
    {
        $recommendations = [];

        foreach ($comparison as $metric => $data) {
            if ($data['performance'] === 'poor' || $data['performance'] === 'below_average') {
                $recommendations = array_merge($recommendations, $this->getMetricRecommendations($metric, $objective, $platform));
            }
        }

        return array_unique($recommendations, SORT_REGULAR);
    }

    private function getMetricRecommendations(string $metric, string $objective, string $platform): array
    {
        $recommendations = [];

        switch ($metric) {
            case 'cpm':
                $recommendations[] = [
                    'category' => 'targeting',
                    'title' => 'Optimize Audience Targeting',
                    'description' => 'Refine your audience to reduce CPM. Consider lookalike audiences or interest-based targeting.',
                    'priority' => 'high',
                ];
                $recommendations[] = [
                    'category' => 'creative',
                    'title' => 'Improve Ad Creative',
                    'description' => 'Test different creative formats and messaging to improve relevance scores.',
                    'priority' => 'medium',
                ];
                break;

            case 'cpc':
                $recommendations[] = [
                    'category' => 'bidding',
                    'title' => 'Adjust Bidding Strategy',
                    'description' => 'Consider switching to automatic bidding or adjusting bid amounts.',
                    'priority' => 'high',
                ];
                $recommendations[] = [
                    'category' => 'keywords',
                    'title' => 'Optimize Keywords',
                    'description' => 'Add negative keywords and focus on high-intent, long-tail keywords.',
                    'priority' => 'medium',
                ];
                break;

            case 'ctr':
                $recommendations[] = [
                    'category' => 'creative',
                    'title' => 'A/B Test Ad Creative',
                    'description' => 'Test different headlines, images, and call-to-action buttons.',
                    'priority' => 'high',
                ];
                $recommendations[] = [
                    'category' => 'targeting',
                    'title' => 'Refine Audience Targeting',
                    'description' => 'Target more specific audiences that are likely to engage with your ads.',
                    'priority' => 'medium',
                ];
                break;

            case 'cvr':
                $recommendations[] = [
                    'category' => 'landing_page',
                    'title' => 'Optimize Landing Page',
                    'description' => 'Improve landing page load speed, design, and conversion flow.',
                    'priority' => 'high',
                ];
                $recommendations[] = [
                    'category' => 'targeting',
                    'title' => 'Target High-Intent Audiences',
                    'description' => 'Focus on audiences with higher purchase intent or previous engagement.',
                    'priority' => 'medium',
                ];
                break;

            case 'roas':
                $recommendations[] = [
                    'category' => 'bidding',
                    'title' => 'Optimize for Value',
                    'description' => 'Switch to value-based bidding strategies to maximize return on ad spend.',
                    'priority' => 'high',
                ];
                $recommendations[] = [
                    'category' => 'product',
                    'title' => 'Review Product Pricing',
                    'description' => 'Analyze product margins and consider promotional strategies.',
                    'priority' => 'medium',
                ];
                break;
        }

        return $recommendations;
    }

    private function generateActionItems(array $comparison, string $objective): array
    {
        $actionItems = [];
        $priority = 1;

        foreach ($comparison as $metric => $data) {
            if ($data['performance'] === 'poor') {
                $actionItems[] = [
                    'priority' => $priority++,
                    'title' => "Fix {$metric} Performance",
                    'description' => "Your {$metric} is " . abs($data['percentage_difference']) . "% worse than benchmark",
                    'urgency' => 'high',
                    'estimated_impact' => 'high',
                    'timeframe' => '1-2 weeks',
                ];
            } elseif ($data['performance'] === 'below_average') {
                $actionItems[] = [
                    'priority' => $priority++,
                    'title' => "Improve {$metric}",
                    'description' => "Optimize {$metric} to reach industry benchmark",
                    'urgency' => 'medium',
                    'estimated_impact' => 'medium',
                    'timeframe' => '2-4 weeks',
                ];
            }
        }

        return $actionItems;
    }

    private function calculateOptimizationScore(array $comparison): array
    {
        if (empty($comparison)) {
            return ['score' => 0, 'grade' => 'F', 'description' => 'No data available'];
        }

        $totalScore = 0;
        $metricCount = 0;

        foreach ($comparison as $metric => $data) {
            $metricScore = match($data['performance']) {
                'excellent' => 100,
                'good' => 80,
                'average' => 60,
                'below_average' => 40,
                'poor' => 20,
                default => 0,
            };

            $totalScore += $metricScore;
            $metricCount++;
        }

        $averageScore = $metricCount > 0 ? $totalScore / $metricCount : 0;

        $grade = match(true) {
            $averageScore >= 90 => 'A+',
            $averageScore >= 80 => 'A',
            $averageScore >= 70 => 'B',
            $averageScore >= 60 => 'C',
            $averageScore >= 50 => 'D',
            default => 'F',
        };

        $description = match($grade) {
            'A+', 'A' => 'Excellent performance across all metrics',
            'B' => 'Good performance with room for improvement',
            'C' => 'Average performance, optimization recommended',
            'D' => 'Below average performance, immediate attention needed',
            'F' => 'Poor performance, comprehensive optimization required',
        };

        return [
            'score' => round($averageScore, 1),
            'grade' => $grade,
            'description' => $description,
        ];
    }

    private function getGeneralOptimizationSuggestions(string $objective): array
    {
        return match($objective) {
            'awareness' => [
                'Focus on reach and frequency optimization',
                'Use video content for better engagement',
                'Target broad but relevant audiences',
                'Monitor brand lift metrics',
            ],
            'leads' => [
                'Optimize landing pages for conversions',
                'Use lead magnets and compelling offers',
                'Implement proper lead scoring',
                'Test different form lengths',
            ],
            'sales' => [
                'Focus on high-value customer segments',
                'Implement dynamic product ads',
                'Use retargeting for cart abandoners',
                'Optimize for lifetime value',
            ],
            'calls' => [
                'Use call extensions and click-to-call',
                'Target local audiences during business hours',
                'Highlight phone numbers in ad creative',
                'Track call quality and duration',
            ],
            default => [
                'Define clear campaign objectives',
                'Test different ad formats',
                'Monitor and optimize regularly',
                'Use data-driven insights',
            ],
        };
    }

    private function formatMetrics($metrics): array
    {
        return [
            'spend' => number_format($metrics->total_spend, 2),
            'impressions' => number_format($metrics->total_impressions),
            'clicks' => number_format($metrics->total_clicks),
            'conversions' => number_format($metrics->total_conversions),
            'revenue' => number_format($metrics->total_revenue, 2),
            'cpm' => number_format($metrics->avg_cpm, 2),
            'cpc' => number_format($metrics->avg_cpc, 2),
            'ctr' => number_format($metrics->avg_ctr, 2) . '%',
            'cvr' => number_format($metrics->avg_cvr, 2) . '%',
        ];
    }

    public function getBenchmarkData(
        string $industry,
        string $platform,
        string $objective,
        string $region = 'global'
    ): ?array {
        $benchmark = PerformanceBenchmark::findClosestBenchmark($industry, $platform, $objective, $region);
        
        if (!$benchmark) {
            return null;
        }

        return [
            'industry' => $benchmark->getIndustryLabel(),
            'platform' => $benchmark->getPlatformLabel(),
            'objective' => $benchmark->getObjectiveLabel(),
            'region' => $benchmark->getRegionLabel(),
            'metrics' => $benchmark->getAvailableMetrics(),
            'period' => [
                'start' => $benchmark->period_start->format('Y-m-d'),
                'end' => $benchmark->period_end->format('Y-m-d'),
            ],
        ];
    }
}
