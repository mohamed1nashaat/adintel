<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdMetric;
use App\Services\Calculators\ObjectiveCalculatorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function summary(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'objective' => 'required|in:awareness,leads,sales,calls',
            'account_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'platform' => 'nullable|in:facebook,google,tiktok',
        ]);

        $query = AdMetric::forDateRange($request->from, $request->to);

        if ($request->account_id) {
            $query->forAccount($request->account_id);
        }

        if ($request->campaign_id) {
            $query->forCampaign($request->campaign_id);
        }

        if ($request->platform) {
            $query->forPlatform($request->platform);
        }

        $metrics = $query->get();
        
        $calculator = ObjectiveCalculatorFactory::make($request->objective);
        $kpis = $calculator->calculateKpis($metrics);

        return response()->json([
            'objective' => $request->objective,
            'date_range' => [
                'from' => $request->from,
                'to' => $request->to,
            ],
            'kpis' => $kpis,
            'primary_kpis' => $calculator->getPrimaryKpis(),
            'secondary_kpis' => $calculator->getSecondaryKpis(),
        ]);
    }

    public function timeseries(Request $request)
    {
        $request->validate([
            'metric' => 'required|in:roas,cpl,cpm,cpc,cvr,cost_per_call,spend,revenue,impressions,clicks,leads,calls',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'objective' => 'required|in:awareness,leads,sales,calls',
            'group_by' => 'nullable|in:date,campaign,account,platform',
            'account_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'platform' => 'nullable|in:facebook,google,tiktok',
        ]);

        $groupBy = $request->group_by ?? 'date';
        
        $query = AdMetric::forDateRange($request->from, $request->to);

        if ($request->account_id) {
            $query->forAccount($request->account_id);
        }

        if ($request->campaign_id) {
            $query->forCampaign($request->campaign_id);
        }

        if ($request->platform) {
            $query->forPlatform($request->platform);
        }

        // Group and aggregate data
        $selectFields = $this->getSelectFields($request->metric, $groupBy);
        $groupByField = $this->getGroupByField($groupBy);

        $results = $query
            ->selectRaw($selectFields)
            ->groupBy($groupByField)
            ->orderBy($groupByField)
            ->get();

        // Calculate KPIs for each group
        $calculator = ObjectiveCalculatorFactory::make($request->objective);
        $timeseriesData = $results->map(function ($row) use ($request, $calculator) {
            $kpis = $calculator->calculateKpis(collect([$row]));
            
            return [
                'period' => $row->{$this->getGroupByAlias($request->group_by ?? 'date')},
                'value' => $kpis[$request->metric] ?? 0,
                'raw_metrics' => [
                    'spend' => $row->total_spend ?? 0,
                    'impressions' => $row->total_impressions ?? 0,
                    'clicks' => $row->total_clicks ?? 0,
                    'revenue' => $row->total_revenue ?? 0,
                    'leads' => $row->total_leads ?? 0,
                    'calls' => $row->total_calls ?? 0,
                ],
            ];
        });

        return response()->json([
            'metric' => $request->metric,
            'objective' => $request->objective,
            'group_by' => $groupBy,
            'date_range' => [
                'from' => $request->from,
                'to' => $request->to,
            ],
            'data' => $timeseriesData,
        ]);
    }

    private function getSelectFields(string $metric, string $groupBy): string
    {
        $groupByField = $this->getGroupByField($groupBy);
        $groupByAlias = $this->getGroupByAlias($groupBy);

        return "{$groupByField} as {$groupByAlias}, " .
               "SUM(spend) as total_spend, " .
               "SUM(impressions) as total_impressions, " .
               "SUM(reach) as total_reach, " .
               "SUM(clicks) as total_clicks, " .
               "SUM(video_views) as total_video_views, " .
               "SUM(conversions) as total_conversions, " .
               "SUM(revenue) as total_revenue, " .
               "SUM(purchases) as total_purchases, " .
               "SUM(leads) as total_leads, " .
               "SUM(calls) as total_calls, " .
               "SUM(sessions) as total_sessions, " .
               "SUM(atc) as total_atc";
    }

    private function getGroupByField(string $groupBy): string
    {
        return match ($groupBy) {
            'date' => 'date',
            'campaign' => 'ad_campaign_id',
            'account' => 'ad_account_id',
            'platform' => 'platform',
            default => 'date',
        };
    }

    private function getGroupByAlias(string $groupBy): string
    {
        return match ($groupBy) {
            'date' => 'date',
            'campaign' => 'campaign_id',
            'account' => 'account_id',
            'platform' => 'platform',
            default => 'date',
        };
    }
}
