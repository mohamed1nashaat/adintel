<?php

namespace App\Services\Integrations\Google;

use App\Services\Integrations\Contracts\AdProvider;
use App\Services\Integrations\Contracts\Account;
use App\Services\Integrations\Contracts\Campaign;
use App\Services\Integrations\Contracts\AdSet;
use App\Services\Integrations\Contracts\Ad;
use App\Services\Integrations\Contracts\Creative;
use App\Services\Integrations\Contracts\Insight;
use App\Services\Integrations\Contracts\RateLimitInfo;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V15\Services\GoogleAdsServiceClient;
use Google\Ads\GoogleAds\V15\Resources\Campaign as GoogleCampaign;
use Google\Ads\GoogleAds\V15\Resources\AdGroup as GoogleAdGroup;
use Google\Ads\GoogleAds\V15\Resources\AdGroupAd as GoogleAdGroupAd;
use Google\Ads\GoogleAds\V15\Resources\Customer;
use Google\Ads\GoogleAds\V15\Common\Metrics;
use Google\Ads\GoogleAds\V15\Common\Segments;
use Google\Ads\GoogleAds\V15\Services\SearchGoogleAdsRequest;
use Illuminate\Support\Facades\Log;

class GoogleAdProvider implements AdProvider
{
    private GoogleAdsClient $googleAdsClient;
    private string $customerId;

    public function __construct(string $customerId, string $refreshToken)
    {
        $this->customerId = $customerId;
        
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId(config('services.google.client_id'))
            ->withClientSecret(config('services.google.client_secret'))
            ->withRefreshToken($refreshToken)
            ->build();

        $this->googleAdsClient = (new GoogleAdsClientBuilder())
            ->withDeveloperToken(config('services.google.ads_developer_token'))
            ->withOAuth2Credential($oAuth2Credential)
            ->build();
    }

    public function getAccounts(): array
    {
        try {
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $query = 'SELECT customer.id, customer.descriptive_name, customer.status FROM customer';
            
            $response = $googleAdsServiceClient->search(
                $this->customerId,
                $query
            );
            
            $accounts = [];
            foreach ($response->iterateAllElements() as $googleAdsRow) {
                $customer = $googleAdsRow->getCustomer();
                $accounts[] = new Account(
                    id: $customer->getId(),
                    name: $customer->getDescriptiveName(),
                    status: $customer->getStatus()
                );
            }
            
            return $accounts;
        } catch (\Exception $e) {
            Log::error('Google Ads API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getCampaigns(string $accountId): array
    {
        try {
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $query = 'SELECT campaign.id, campaign.name, campaign.status, campaign.advertising_channel_type, campaign.daily_budget, campaign.start_date, campaign.end_date FROM campaign';
            
            $response = $googleAdsServiceClient->search(
                $accountId,
                $query
            );
            
            $campaigns = [];
            foreach ($response->iterateAllElements() as $googleAdsRow) {
                $campaign = $googleAdsRow->getCampaign();
                $campaigns[] = new Campaign(
                    id: $campaign->getId(),
                    name: $campaign->getName(),
                    accountId: $accountId,
                    status: $campaign->getStatus(),
                    objective: $campaign->getAdvertisingChannelType(),
                    dailyBudget: $campaign->getDailyBudget() ? (float) ($campaign->getDailyBudget() / 1000000) : 0,
                    startTime: $campaign->getStartDate(),
                    endTime: $campaign->getEndDate()
                );
            }
            
            return $campaigns;
        } catch (\Exception $e) {
            Log::error('Google Ads API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAdSets(string $campaignId): array
    {
        try {
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $query = 'SELECT ad_group.id, ad_group.name, ad_group.status, ad_group.cpc_bid_micros, ad_group.targeting_setting FROM ad_group WHERE ad_group.campaign = ' . $campaignId;
            
            $response = $googleAdsServiceClient->search(
                $this->customerId,
                $query
            );
            
            $adSets = [];
            foreach ($response->iterateAllElements() as $googleAdsRow) {
                $adGroup = $googleAdsRow->getAdGroup();
                
                $targeting = new Targeting(
                    countries: [],
                    ageMin: 18,
                    ageMax: 65,
                    genders: [1, 2],
                    interests: []
                );
                
                $adSets[] = new AdSet(
                    id: $adGroup->getId(),
                    name: $adGroup->getName(),
                    campaignId: $campaignId,
                    targeting: $targeting,
                    status: $adGroup->getStatus(),
                    dailyBudget: 0,
                    startTime: null,
                    endTime: null
                );
            }
            
            return $adSets;
        } catch (\Exception $e) {
            Log::error('Google Ads API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAds(string $adSetId): array
    {
        try {
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $query = 'SELECT ad_group_ad.ad.id, ad_group_ad.ad.name, ad_group_ad.ad.type, ad_group_ad.status FROM ad_group_ad WHERE ad_group_ad.ad_group = ' . $adSetId;
            
            $response = $googleAdsServiceClient->search(
                $this->customerId,
                $query
            );
            
            $ads = [];
            foreach ($response->iterateAllElements() as $googleAdsRow) {
                $adGroupAd = $googleAdsRow->getAdGroupAd();
                $ad = $adGroupAd->getAd();
                
                $ads[] = new Ad(
                    id: $ad->getId(),
                    name: $ad->getName() ?? 'Unnamed Ad',
                    adSetId: $adSetId,
                    creative: new Creative(
                        id: $ad->getId(),
                        name: $ad->getName() ?? 'Unnamed Creative',
                        type: $ad->getType()
                    ),
                    status: $adGroupAd->getStatus()
                );
            }
            
            return $ads;
        } catch (\Exception $e) {
            Log::error('Google Ads API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getInsights(string $entityId, string $entityType, array $fields = [], array $params = []): array
    {
        try {
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $selectFields = implode(', ', array_map(fn($field) => "metrics.{$field}", $fields));
            $dateRange = $params['date_range'] ?? 'LAST_30_DAYS';
            
            $query = "SELECT {$selectFields}, segments.date FROM {$entityType} WHERE {$entityType}.id = {$entityId} AND segments.date DURING {$dateRange}";
            
            $response = $googleAdsServiceClient->search(
                $this->customerId,
                $query
            );
            
            $insights = [];
            foreach ($response->iterateAllElements() as $googleAdsRow) {
                $metrics = $googleAdsRow->getMetrics();
                $segments = $googleAdsRow->getSegments();
                
                $insights[] = new Insight(
                    entityId: $entityId,
                    entityType: $entityType,
                    impressions: $metrics->getImpressions() ?? 0,
                    clicks: $metrics->getClicks() ?? 0,
                    spend: $metrics->getCostMicros() ? (float) ($metrics->getCostMicros() / 1000000) : 0,
                    conversions: $metrics->getConversions() ?? 0,
                    ctr: $metrics->getCtr() ?? 0,
                    cpc: $metrics->getAverageCpc() ? (float) ($metrics->getAverageCpc() / 1000000) : 0,
                    date: $segments->getDate()
                );
            }
            
            return $insights;
        } catch (\Exception $e) {
            Log::error('Google Ads API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getRateLimitInfo(): RateLimitInfo
    {
        // Google Ads API has different rate limiting
        return new RateLimitInfo(
            limit: 1000,
            remaining: 1000,
            resetTime: time() + 3600
        );
    }
}
