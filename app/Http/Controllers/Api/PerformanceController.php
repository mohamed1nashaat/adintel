<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerformanceBenchmark;
use App\Models\AdCampaign;
use App\Models\AdAccount;
use App\Services\PerformanceCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PerformanceController extends Controller
{
    public function __construct(
        private PerformanceCalculatorService $performanceCalculator
    ) {}

    public function analyzeCampaign(Request $request, AdCampaign $campaign): JsonResponse
    {
        try {
            $analysis = $this->performanceCalculator->calculateCampaignPerformance($campaign);
            
            return response()->json([
                'data' => $analysis,
                'campaign' => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'objective' => $campaign->objective,
                    'platform' => $campaign->adAccount->integration->platform,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to analyze campaign performance',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function analyzeAccount(Request $request, AdAccount $account): JsonResponse
    {
        $validated = $request->validate([
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after:date_range.start',
        ]);

        try {
            $dateRange = $validated['date_range'] ?? null;
            $analysis = $this->performanceCalculator->calculateAccountPerformance($account->id, $dateRange);
            
            return response()->json([
                'data' => $analysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to analyze account performance',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function analyzeCustom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'industry' => 'required|string',
            'platform' => 'required|string',
            'objective' => 'required|string',
            'region' => 'nullable|string',
            'audience_size' => 'nullable|string',
            'metrics' => 'required|array',
            'metrics.spend' => 'nullable|numeric|min:0',
            'metrics.impressions' => 'nullable|integer|min:0',
            'metrics.clicks' => 'nullable|integer|min:0',
            'metrics.conversions' => 'nullable|integer|min:0',
            'metrics.revenue' => 'nullable|numeric|min:0',
            'metrics.cpm' => 'nullable|numeric|min:0',
            'metrics.cpc' => 'nullable|numeric|min:0',
            'metrics.ctr' => 'nullable|numeric|min:0|max:100',
            'metrics.cvr' => 'nullable|numeric|min:0|max:100',
            'metrics.roas' => 'nullable|numeric|min:0',
        ]);

        try {
            $analysis = $this->performanceCalculator->calculatePerformanceAnalysis(
                $validated['industry'],
                $validated['platform'],
                $validated['objective'],
                $validated['metrics'],
                $validated['region'] ?? 'global',
                $validated['audience_size'] ?? 'medium'
            );
            
            return response()->json(['data' => $analysis]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to analyze performance',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getBenchmarks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'industry' => 'nullable|string',
            'platform' => 'nullable|string',
            'objective' => 'nullable|string',
            'region' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PerformanceBenchmark::query();

        if ($validated['industry'] ?? false) {
            $query->forIndustry($validated['industry']);
        }

        if ($validated['platform'] ?? false) {
            $query->forPlatform($validated['platform']);
        }

        if ($validated['objective'] ?? false) {
            $query->forObjective($validated['objective']);
        }

        if ($validated['region'] ?? false) {
            $query->forRegion($validated['region']);
        }

        if ($validated['is_active'] ?? true) {
            $query->active();
        }

        $benchmarks = $query->orderBy('period_end', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'data' => $benchmarks->items(),
            'meta' => [
                'current_page' => $benchmarks->currentPage(),
                'last_page' => $benchmarks->lastPage(),
                'per_page' => $benchmarks->perPage(),
                'total' => $benchmarks->total(),
            ],
        ]);
    }

    public function getBenchmark(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'industry' => 'required|string',
            'platform' => 'required|string',
            'objective' => 'required|string',
            'region' => 'nullable|string',
        ]);

        try {
            $benchmarkData = $this->performanceCalculator->getBenchmarkData(
                $validated['industry'],
                $validated['platform'],
                $validated['objective'],
                $validated['region'] ?? 'global'
            );

            if (!$benchmarkData) {
                return response()->json([
                    'error' => 'No benchmark data found for the specified criteria',
                ], 404);
            }

            return response()->json(['data' => $benchmarkData]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve benchmark data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function createBenchmark(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'industry' => 'required|string',
            'platform' => 'required|string',
            'objective' => 'required|string',
            'region' => 'nullable|string',
            'audience_size' => 'nullable|string',
            'cpm_benchmark' => 'nullable|numeric|min:0',
            'cpc_benchmark' => 'nullable|numeric|min:0',
            'cpl_benchmark' => 'nullable|numeric|min:0',
            'cpa_benchmark' => 'nullable|numeric|min:0',
            'ctr_benchmark' => 'nullable|numeric|min:0|max:100',
            'cvr_benchmark' => 'nullable|numeric|min:0|max:100',
            'roas_benchmark' => 'nullable|numeric|min:0',
            'frequency_benchmark' => 'nullable|numeric|min:0',
            'reach_benchmark' => 'nullable|numeric|min:0',
            'additional_metrics' => 'nullable|array',
            'data_source' => 'required|in:internal,industry_report,platform_data,third_party',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'notes' => 'nullable|string',
        ]);

        $validated['tenant_id'] = session('current_tenant_id');
        $validated['region'] = $validated['region'] ?? 'global';
        $validated['audience_size'] = $validated['audience_size'] ?? 'medium';

        try {
            $benchmark = PerformanceBenchmark::create($validated);
            
            return response()->json([
                'data' => $benchmark,
                'message' => 'Benchmark created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create benchmark',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateBenchmark(Request $request, PerformanceBenchmark $benchmark): JsonResponse
    {
        $validated = $request->validate([
            'cpm_benchmark' => 'nullable|numeric|min:0',
            'cpc_benchmark' => 'nullable|numeric|min:0',
            'cpl_benchmark' => 'nullable|numeric|min:0',
            'cpa_benchmark' => 'nullable|numeric|min:0',
            'ctr_benchmark' => 'nullable|numeric|min:0|max:100',
            'cvr_benchmark' => 'nullable|numeric|min:0|max:100',
            'roas_benchmark' => 'nullable|numeric|min:0',
            'frequency_benchmark' => 'nullable|numeric|min:0',
            'reach_benchmark' => 'nullable|numeric|min:0',
            'additional_metrics' => 'nullable|array',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after:period_start',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            $benchmark->update($validated);
            
            return response()->json([
                'data' => $benchmark->fresh(),
                'message' => 'Benchmark updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update benchmark',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteBenchmark(PerformanceBenchmark $benchmark): JsonResponse
    {
        try {
            $benchmark->delete();
            
            return response()->json([
                'message' => 'Benchmark deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete benchmark',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOptions(): JsonResponse
    {
        return response()->json([
            'data' => [
                'industries' => PerformanceBenchmark::getIndustryOptions(),
                'platforms' => PerformanceBenchmark::getPlatformOptions(),
                'objectives' => PerformanceBenchmark::getObjectiveOptions(),
                'regions' => PerformanceBenchmark::getRegionOptions(),
                'audience_sizes' => PerformanceBenchmark::getAudienceSizeOptions(),
            ],
        ]);
    }

    public function getIndustryBenchmarks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'industry' => 'required|string',
            'region' => 'nullable|string',
        ]);

        $query = PerformanceBenchmark::active()
            ->forIndustry($validated['industry']);

        if ($validated['region'] ?? false) {
            $query->forRegion($validated['region']);
        }

        $benchmarks = $query->get()->groupBy(['platform', 'objective']);

        $result = [];
        foreach ($benchmarks as $platform => $objectives) {
            $result[$platform] = [];
            foreach ($objectives as $objective => $benchmarkList) {
                $latest = $benchmarkList->sortByDesc('period_end')->first();
                if ($latest) {
                    $result[$platform][$objective] = [
                        'metrics' => $latest->getAvailableMetrics(),
                        'period' => [
                            'start' => $latest->period_start->format('Y-m-d'),
                            'end' => $latest->period_end->format('Y-m-d'),
                        ],
                        'data_source' => $latest->getDataSourceLabel(),
                    ];
                }
            }
        }

        return response()->json([
            'data' => [
                'industry' => $validated['industry'],
                'region' => $validated['region'] ?? 'global',
                'benchmarks' => $result,
            ],
        ]);
    }

    public function compareMetrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaigns' => 'required|array|min:2|max:5',
            'campaigns.*' => 'exists:ad_campaigns,id',
            'metrics' => 'nullable|array',
            'metrics.*' => 'string|in:cpm,cpc,ctr,cvr,cpl,cpa,roas',
        ]);

        try {
            $campaigns = AdCampaign::whereIn('id', $validated['campaigns'])->get();
            $metricsToCompare = $validated['metrics'] ?? ['cpm', 'cpc', 'ctr', 'cvr'];
            
            $comparison = [];
            
            foreach ($campaigns as $campaign) {
                $analysis = $this->performanceCalculator->calculateCampaignPerformance($campaign);
                
                if (!isset($analysis['error'])) {
                    $comparison[] = [
                        'campaign' => [
                            'id' => $campaign->id,
                            'name' => $campaign->name,
                            'objective' => $campaign->objective,
                            'platform' => $campaign->adAccount->integration->platform,
                        ],
                        'performance' => $analysis['performance_comparison'] ?? [],
                        'optimization_score' => $analysis['optimization_score'] ?? null,
                    ];
                }
            }

            return response()->json([
                'data' => [
                    'campaigns' => $comparison,
                    'metrics_compared' => $metricsToCompare,
                    'summary' => $this->generateComparisonSummary($comparison, $metricsToCompare),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to compare campaigns',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function generateComparisonSummary(array $comparison, array $metrics): array
    {
        $summary = [
            'best_performing' => [],
            'worst_performing' => [],
            'average_scores' => [],
        ];

        foreach ($metrics as $metric) {
            $metricData = [];
            
            foreach ($comparison as $campaignData) {
                if (isset($campaignData['performance'][$metric])) {
                    $metricData[] = [
                        'campaign_id' => $campaignData['campaign']['id'],
                        'campaign_name' => $campaignData['campaign']['name'],
                        'value' => $campaignData['performance'][$metric]['actual'],
                        'performance' => $campaignData['performance'][$metric]['performance'],
                    ];
                }
            }

            if (!empty($metricData)) {
                // Sort by performance rating
                $performanceOrder = ['excellent', 'good', 'average', 'below_average', 'poor'];
                usort($metricData, function ($a, $b) use ($performanceOrder) {
                    $aIndex = array_search($a['performance'], $performanceOrder);
                    $bIndex = array_search($b['performance'], $performanceOrder);
                    return $aIndex <=> $bIndex;
                });

                $summary['best_performing'][$metric] = $metricData[0];
                $summary['worst_performing'][$metric] = end($metricData);
            }
        }

        // Calculate average optimization scores
        $scores = array_column($comparison, 'optimization_score');
        $scores = array_filter($scores, fn($score) => $score !== null);
        
        if (!empty($scores)) {
            $summary['average_scores'] = [
                'score' => round(array_sum(array_column($scores, 'score')) / count($scores), 1),
                'grade' => $this->calculateAverageGrade($scores),
            ];
        }

        return $summary;
    }

    private function calculateAverageGrade(array $scores): string
    {
        $gradeValues = [
            'A+' => 95, 'A' => 85, 'B' => 75, 'C' => 65, 'D' => 55, 'F' => 25
        ];

        $totalValue = 0;
        foreach ($scores as $score) {
            $totalValue += $gradeValues[$score['grade']] ?? 0;
        }

        $averageValue = $totalValue / count($scores);

        return match(true) {
            $averageValue >= 90 => 'A+',
            $averageValue >= 80 => 'A',
            $averageValue >= 70 => 'B',
            $averageValue >= 60 => 'C',
            $averageValue >= 50 => 'D',
            default => 'F',
        };
    }
}
