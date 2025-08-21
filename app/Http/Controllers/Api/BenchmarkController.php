<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Benchmark;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BenchmarkController extends Controller
{
    /**
     * Get industry benchmarks for comparison
     */
    public function getIndustryBenchmarks(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => 'required|string',
            'region' => 'string|in:gcc,ksa,uae,qatar,bahrain,kuwait,oman,global',
            'platform' => 'string|in:facebook,google,snapchat,tiktok',
            'objective' => 'string|in:awareness,leads,sales,calls',
        ]);

        $industry = $request->input('industry');
        $region = $request->input('region', 'gcc');
        $platform = $request->input('platform');
        $objective = $request->input('objective');

        $query = Benchmark::active()
            ->forIndustry($industry)
            ->forRegion($region)
            ->recent();

        if ($platform) {
            $query->forPlatform($platform);
        }

        if ($objective) {
            $query->forObjective($objective);
        }

        $benchmarks = $query->get()->groupBy(['platform', 'objective', 'metric_name']);

        return response()->json([
            'data' => $benchmarks,
            'meta' => [
                'industry' => $industry,
                'region' => $region,
                'platform' => $platform,
                'objective' => $objective,
                'total_benchmarks' => $query->count(),
            ]
        ]);
    }

    /**
     * Compare performance against benchmarks
     */
    public function comparePerformance(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => 'required|string',
            'region' => 'string|in:gcc,ksa,uae,qatar,bahrain,kuwait,oman,global',
            'platform' => 'required|string|in:facebook,google,snapchat,tiktok',
            'objective' => 'required|string|in:awareness,leads,sales,calls',
            'metrics' => 'required|array',
            'metrics.*' => 'numeric',
        ]);

        $comparison = Benchmark::comparePerformance(
            $request->input('industry'),
            $request->input('region', 'gcc'),
            $request->input('platform'),
            $request->input('objective'),
            $request->input('metrics')
        );

        // Calculate overall performance score
        $scores = array_column($comparison, 'performance_score');
        $overallScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        return response()->json([
            'data' => $comparison,
            'meta' => [
                'overall_score' => round($overallScore, 2),
                'performance_level' => $this->getOverallPerformanceLevel($overallScore),
                'metrics_compared' => count($comparison),
                'industry' => $request->input('industry'),
                'region' => $request->input('region', 'gcc'),
                'platform' => $request->input('platform'),
                'objective' => $request->input('objective'),
            ]
        ]);
    }

    /**
     * Get GCC market insights
     */
    public function getGccInsights(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => 'string',
            'platform' => 'string|in:facebook,google,snapchat,tiktok',
            'objective' => 'string|in:awareness,leads,sales,calls',
        ]);

        $gccCountries = ['gcc', 'ksa', 'uae', 'qatar', 'bahrain', 'kuwait', 'oman'];
        
        $query = Benchmark::active()->recent();

        if ($request->input('industry')) {
            $query->forIndustry($request->input('industry'));
        }

        if ($request->input('platform')) {
            $query->forPlatform($request->input('platform'));
        }

        if ($request->input('objective')) {
            $query->forObjective($request->input('objective'));
        }

        $insights = $query->whereIn('region', $gccCountries)
            ->get()
            ->groupBy(['region', 'platform', 'metric_name']);

        // Calculate regional averages
        $regionalAverages = [];
        foreach ($gccCountries as $region) {
            if (isset($insights[$region])) {
                $regionalAverages[$region] = $this->calculateRegionalAverages($insights[$region]);
            }
        }

        return response()->json([
            'data' => $insights,
            'regional_averages' => $regionalAverages,
            'meta' => [
                'regions_included' => $gccCountries,
                'industry' => $request->input('industry'),
                'platform' => $request->input('platform'),
                'objective' => $request->input('objective'),
            ]
        ]);
    }

    /**
     * Get trending metrics for KSA market
     */
    public function getKsaTrends(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => 'string',
            'platform' => 'string|in:facebook,google,snapchat,tiktok',
            'period' => 'string|in:30d,90d,180d,1y',
        ]);

        $days = match($request->input('period', '90d')) {
            '30d' => 30,
            '90d' => 90,
            '180d' => 180,
            '1y' => 365,
        };

        $query = Benchmark::active()
            ->forRegion('ksa')
            ->where('period_end', '>=', now()->subDays($days));

        if ($request->input('industry')) {
            $query->forIndustry($request->input('industry'));
        }

        if ($request->input('platform')) {
            $query->forPlatform($request->input('platform'));
        }

        $trends = $query->orderBy('period_end', 'desc')
            ->get()
            ->groupBy(['metric_name', 'platform']);

        // Calculate trend direction for each metric
        $trendAnalysis = [];
        foreach ($trends as $metric => $platforms) {
            foreach ($platforms as $platform => $data) {
                $values = $data->pluck('benchmark_value')->toArray();
                if (count($values) >= 2) {
                    $trendAnalysis[$metric][$platform] = [
                        'current_value' => $values[0],
                        'previous_value' => $values[1] ?? $values[0],
                        'trend_direction' => $values[0] > ($values[1] ?? $values[0]) ? 'up' : 'down',
                        'change_percentage' => $this->calculateChangePercentage($values[0], $values[1] ?? $values[0]),
                        'data_points' => count($values),
                    ];
                }
            }
        }

        return response()->json([
            'data' => $trends,
            'trend_analysis' => $trendAnalysis,
            'meta' => [
                'region' => 'ksa',
                'period' => $request->input('period', '90d'),
                'industry' => $request->input('industry'),
                'platform' => $request->input('platform'),
            ]
        ]);
    }

    /**
     * Get available industries and regions
     */
    public function getAvailableOptions(): JsonResponse
    {
        $industries = Benchmark::active()
            ->distinct()
            ->pluck('industry')
            ->sort()
            ->values();

        $regions = Benchmark::active()
            ->distinct()
            ->pluck('region')
            ->sort()
            ->values();

        $platforms = ['facebook', 'google', 'snapchat', 'tiktok'];
        $objectives = ['awareness', 'leads', 'sales', 'calls'];

        return response()->json([
            'industries' => $industries,
            'regions' => $regions,
            'platforms' => $platforms,
            'objectives' => $objectives,
        ]);
    }

    /**
     * Store new benchmark data (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => 'required|string',
            'region' => 'required|string|in:gcc,ksa,uae,qatar,bahrain,kuwait,oman,global',
            'platform' => 'required|string|in:facebook,google,snapchat,tiktok',
            'objective' => 'required|string|in:awareness,leads,sales,calls',
            'metric_name' => 'required|string',
            'benchmark_value' => 'required|numeric',
            'percentile_25' => 'nullable|numeric',
            'percentile_50' => 'nullable|numeric',
            'percentile_75' => 'nullable|numeric',
            'percentile_90' => 'nullable|numeric',
            'sample_size' => 'integer|min:1',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'additional_filters' => 'nullable|array',
            'data_source' => 'required|string|in:internal,industry_report,competitor_analysis,gcc_market_data',
            'notes' => 'nullable|string',
        ]);

        $benchmark = Benchmark::create([
            'tenant_id' => session('current_tenant_id'),
            ...$request->validated()
        ]);

        return response()->json($benchmark, 201);
    }

    // Helper methods
    private function getOverallPerformanceLevel(float $score): string
    {
        if ($score <= 1.5) return 'poor';
        if ($score <= 2.5) return 'below_average';
        if ($score <= 3.5) return 'average';
        if ($score <= 4.5) return 'good';
        return 'excellent';
    }

    private function calculateRegionalAverages(array $platformData): array
    {
        $averages = [];
        foreach ($platformData as $platform => $metrics) {
            foreach ($metrics as $metric => $benchmarks) {
                $values = collect($benchmarks)->pluck('benchmark_value');
                $averages[$platform][$metric] = [
                    'average' => $values->avg(),
                    'min' => $values->min(),
                    'max' => $values->max(),
                    'count' => $values->count(),
                ];
            }
        }
        return $averages;
    }

    private function calculateChangePercentage(float $current, float $previous): float
    {
        if ($previous == 0) return 0;
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
