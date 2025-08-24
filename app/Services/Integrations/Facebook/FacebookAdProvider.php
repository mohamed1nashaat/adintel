<?php

namespace App\Services\Integrations\Facebook;

use App\Services\Integrations\Contracts\AdProvider;
use App\Services\Integrations\Contracts\Account;
use App\Services\Integrations\Contracts\Campaign;
use App\Services\Integrations\Contracts\AdSet;
use App\Services\Integrations\Contracts\Ad;
use App\Services\Integrations\Contracts\Creative;
use App\Services\Integrations\Contracts\Insight;
use App\Services\Integrations\Contracts\RateLimitInfo;
use Facebook\Facebook;
use Illuminate\Support\Facades\Log;

class FacebookAdProvider implements AdProvider
{
    private Facebook $facebook;
    private string $accessToken;
    private string $apiVersion = 'v18.0';

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
        $this->facebook = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => $this->apiVersion,
        ]);
    }

    public function getAccounts(): array
    {
        try {
            $response = $this->facebook->get(
                '/me/adaccounts',
                $this->accessToken
            );
            
            $accounts = [];
            $data = $response->getDecodedBody()['data'];
            
            foreach ($data as $accountData) {
                $accounts[] = new Account(
                    id: $accountData['id'],
                    name: $accountData['name'],
                    status: $accountData['account_status'] ?? 'UNKNOWN'
                );
            }
            
            return $accounts;
        } catch (\Exception $e) {
            Log::error('Facebook API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getCampaigns(string $accountId): array
    {
        try {
            $response = $this->facebook->get(
                "/{$accountId}/campaigns?fields=id,name,status,objective,daily_budget,start_time,end_time",
                $this->accessToken
            );
            
            $campaigns = [];
            $data = $response->getDecodedBody()['data'];
            
            foreach ($data as $campaignData) {
                $campaigns[] = new Campaign(
                    id: $campaignData['id'],
                    name: $campaignData['name'],
                    accountId: $accountId,
                    status: $campaignData['status'],
                    objective: $campaignData['objective'] ?? 'NONE',
                    dailyBudget: (float) ($campaignData['daily_budget'] ?? 0),
                    startTime: $campaignData['start_time'] ?? null,
                    endTime: $campaignData['end_time'] ?? null
                );
            }
            
            return $campaigns;
        } catch (\Exception $e) {
            Log::error('Facebook API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAdSets(string $campaignId): array
    {
        try {
            $response = $this->facebook->get(
                "/{$campaignId}/adsets?fields=id,name,status,daily_budget,targeting,start_time,end_time",
                $this->accessToken
            );
            
            $adSets = [];
            $data = $response->getDecodedBody()['data'];
            
            foreach ($data as $adSetData) {
                $targeting = new Targeting(
                    countries: $adSetData['targeting']['geo_locations']['countries'] ?? [],
                    ageMin: $adSetData['targeting']['age_min'] ?? 18,
                    ageMax: $adSetData['targeting']['age_max'] ?? 65,
                    genders: $adSetData['targeting']['genders'] ?? [1, 2],
                    interests: $adSetData['targeting']['interests'] ?? []
                );
                
                $adSets[] = new AdSet(
                    id: $adSetData['id'],
                    name: $adSetData['name'],
                    campaignId: $campaignId,
                    targeting: $targeting,
                    status: $adSetData['status'],
                    dailyBudget: (float) ($adSetData['daily_budget'] ?? 0),
                    startTime: $adSetData['start_time'] ?? null,
                    endTime: $adSetData['end_time'] ?? null
                );
            }
            
            return $adSets;
        } catch (\Exception $e) {
            Log::error('Facebook API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAds(string $adSetId): array
    {
        try {
            $response = $this->facebook->get(
                "/{$adSetId}/ads?fields=id,name,status,creative",
                $this->accessToken
            );
            
            $ads = [];
            $data = $response->getDecodedBody()['data'];
            
            foreach ($data as $adData) {
                $ads[] = new Ad(
                    id: $adData['id'],
                    name: $adData['name'],
                    adSetId: $adSetId,
                    creative: new Creative(
                        id: $adData['creative']['id'] ?? '',
                        name: $adData['creative']['name'] ?? '',
                        type: $adData['creative']['type'] ?? 'IMAGE'
                    ),
                    status: $adData['status']
                );
            }
            
            return $ads;
        } catch (\Exception $e) {
            Log::error('Facebook API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getInsights(string $entityId, string $entityType, array $fields = [], array $params = []): array
    {
        try {
            $fieldsString = implode(',', $fields);
            $paramsString = http_build_query($params);
            
            $response = $this->facebook->get(
                "/{$entityId}/insights?fields={$fieldsString}&{$paramsString}",
                $this->accessToken
            );
            
            $insights = [];
            $data = $response->getDecodedBody()['data'];
            
            foreach ($data as $insightData) {
                $insights[] = new Insight(
                    entityId: $entityId,
                    entityType: $entityType,
                    impressions: (int) ($insightData['impressions'] ?? 0),
                    clicks: (int) ($insightData['clicks'] ?? 0),
                    spend: (float) ($insightData['spend'] ?? 0),
                    conversions: (int) ($insightData['conversions'] ?? 0),
                    ctr: (float) ($insightData['ctr'] ?? 0),
                    cpc: (float) ($insightData['cpc'] ?? 0),
                    date: $insightData['date_start'] ?? null
                );
            }
            
            return $insights;
        } catch (\Exception $e) {
            Log::error('Facebook API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getRateLimitInfo(): RateLimitInfo
    {
        // Facebook SDK handles rate limiting internally
        return new RateLimitInfo(
            limit: 200,
            remaining: 200,
            resetTime: time() + 3600
        );
    }
}
