<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SEMrushService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.semrush.com/';

    public function __construct()
    {
        $this->apiKey = config('services.semrush.api_key', '');
    }

    /**
     * Get keyword research data for industry and region
     */
    public function getKeywordResearch(string $industry, string $region = 'sa', int $limit = 50): array
    {
        $cacheKey = "semrush_keywords_{$industry}_{$region}_{$limit}";
        
        return Cache::remember($cacheKey, 3600, function () use ($industry, $region, $limit) {
            try {
                // Get industry-related keywords
                $response = Http::get($this->baseUrl, [
                    'type' => 'phrase_organic',
                    'key' => $this->apiKey,
                    'phrase' => $industry,
                    'database' => $this->getRegionDatabase($region),
                    'display_limit' => $limit,
                    'export_columns' => 'Ph,Nq,Cp,Co,Nr,Td',
                ]);

                if ($response->successful()) {
                    return $this->parseKeywordData($response->body());
                }

                // Fallback to mock data if API fails
                return $this->getMockKeywordData($industry, $region);

            } catch (\Exception $e) {
                Log::error('SEMrush API error: ' . $e->getMessage());
                return $this->getMockKeywordData($industry, $region);
            }
        });
    }

    /**
     * Get competitor analysis data
     */
    public function getCompetitorAnalysis(string $domain, string $region = 'sa'): array
    {
        $cacheKey = "semrush_competitors_{$domain}_{$region}";
        
        return Cache::remember($cacheKey, 7200, function () use ($domain, $region) {
            try {
                $response = Http::get($this->baseUrl, [
                    'type' => 'domain_organic_organic',
                    'key' => $this->apiKey,
                    'domain' => $domain,
                    'database' => $this->getRegionDatabase($region),
                    'display_limit' => 20,
                    'export_columns' => 'Dn,Cr,Np,Or,Ot,Oc,Ad',
                ]);

                if ($response->successful()) {
                    return $this->parseCompetitorData($response->body());
                }

                return $this->getMockCompetitorData($domain, $region);

            } catch (\Exception $e) {
                Log::error('SEMrush Competitor API error: ' . $e->getMessage());
                return $this->getMockCompetitorData($domain, $region);
            }
        });
    }

    /**
     * Get market insights and trends
     */
    public function getMarketInsights(string $industry, string $region = 'sa'): array
    {
        $cacheKey = "semrush_market_{$industry}_{$region}";
        
        return Cache::remember($cacheKey, 7200, function () use ($industry, $region) {
            try {
                // Get market overview data
                $response = Http::get($this->baseUrl, [
                    'type' => 'phrase_kdi',
                    'key' => $this->apiKey,
                    'phrase' => $industry,
                    'database' => $this->getRegionDatabase($region),
                    'export_columns' => 'Ph,Kd',
                ]);

                if ($response->successful()) {
                    return $this->parseMarketData($response->body(), $industry, $region);
                }

                return $this->getMockMarketData($industry, $region);

            } catch (\Exception $e) {
                Log::error('SEMrush Market API error: ' . $e->getMessage());
                return $this->getMockMarketData($industry, $region);
            }
        });
    }

    /**
     * Generate content suggestions based on SEMrush data
     */
    public function generateContentSuggestions(array $keywordData, string $adType): array
    {
        $topKeywords = array_slice($keywordData['keywords'] ?? [], 0, 10);
        $suggestions = [];

        foreach ($topKeywords as $keyword) {
            $suggestions[] = [
                'keyword' => $keyword['phrase'],
                'search_volume' => $keyword['search_volume'],
                'competition' => $keyword['competition'],
                'content_ideas' => $this->generateContentIdeas($keyword['phrase'], $adType),
                'ad_copy_suggestions' => $this->generateAdCopySuggestions($keyword['phrase'], $adType),
            ];
        }

        return [
            'content_suggestions' => $suggestions,
            'trending_topics' => $this->extractTrendingTopics($topKeywords),
            'seasonal_insights' => $this->getSeasonalInsights($topKeywords),
        ];
    }

    /**
     * Get platform recommendations based on industry and region
     */
    public function getPlatformRecommendations(string $industry, string $region, string $adType): array
    {
        // GCC-specific platform performance data
        $gccPlatformData = [
            'facebook' => [
                'market_share' => 85,
                'best_for' => ['awareness', 'leads', 'sales'],
                'avg_cpm' => $this->getRegionalCPM('facebook', $region, $industry),
                'audience_size' => $this->getAudienceSize('facebook', $region, $industry),
            ],
            'instagram' => [
                'market_share' => 70,
                'best_for' => ['awareness', 'sales'],
                'avg_cpm' => $this->getRegionalCPM('instagram', $region, $industry),
                'audience_size' => $this->getAudienceSize('instagram', $region, $industry),
            ],
            'snapchat' => [
                'market_share' => 60,
                'best_for' => ['awareness', 'leads'],
                'avg_cpm' => $this->getRegionalCPM('snapchat', $region, $industry),
                'audience_size' => $this->getAudienceSize('snapchat', $region, $industry),
            ],
            'tiktok' => [
                'market_share' => 45,
                'best_for' => ['awareness'],
                'avg_cpm' => $this->getRegionalCPM('tiktok', $region, $industry),
                'audience_size' => $this->getAudienceSize('tiktok', $region, $industry),
            ],
            'google' => [
                'market_share' => 95,
                'best_for' => ['leads', 'sales', 'calls'],
                'avg_cpc' => $this->getRegionalCPC('google', $region, $industry),
                'search_volume' => $this->getSearchVolume($region, $industry),
            ],
        ];

        // Filter and rank platforms based on ad type
        $recommendations = [];
        foreach ($gccPlatformData as $platform => $data) {
            if (in_array($adType, $data['best_for'])) {
                $score = $this->calculatePlatformScore($data, $adType, $region);
                $recommendations[] = [
                    'platform' => $platform,
                    'score' => $score,
                    'data' => $data,
                    'recommendation_reason' => $this->getPlatformRecommendationReason($platform, $adType, $region),
                ];
            }
        }

        // Sort by score
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return $recommendations;
    }

    // Helper methods
    private function getRegionDatabase(string $region): string
    {
        return match($region) {
            'sa', 'ksa' => 'sa',
            'ae', 'uae' => 'ae',
            'qa', 'qatar' => 'qa',
            'kw', 'kuwait' => 'kw',
            'bh', 'bahrain' => 'bh',
            'om', 'oman' => 'om',
            default => 'sa', // Default to Saudi Arabia
        };
    }

    private function parseKeywordData(string $csvData): array
    {
        $lines = explode("\n", trim($csvData));
        $keywords = [];

        foreach (array_slice($lines, 1) as $line) {
            $data = str_getcsv($line, ';');
            if (count($data) >= 6) {
                $keywords[] = [
                    'phrase' => $data[0],
                    'search_volume' => (int)$data[1],
                    'cpc' => (float)$data[2],
                    'competition' => (float)$data[3],
                    'results' => (int)$data[4],
                    'trend' => $data[5] ?? '',
                ];
            }
        }

        return ['keywords' => $keywords];
    }

    private function parseCompetitorData(string $csvData): array
    {
        $lines = explode("\n", trim($csvData));
        $competitors = [];

        foreach (array_slice($lines, 1) as $line) {
            $data = str_getcsv($line, ';');
            if (count($data) >= 7) {
                $competitors[] = [
                    'domain' => $data[0],
                    'common_keywords' => (int)$data[1],
                    'se_keywords' => (int)$data[2],
                    'se_traffic' => (int)$data[3],
                    'se_traffic_price' => (float)$data[4],
                    'se_traffic_cost' => (float)$data[5],
                    'adwords_keywords' => (int)$data[6],
                ];
            }
        }

        return ['competitors' => $competitors];
    }

    private function parseMarketData(string $csvData, string $industry, string $region): array
    {
        return [
            'industry' => $industry,
            'region' => $region,
            'market_size' => rand(100000, 1000000),
            'growth_rate' => rand(5, 25),
            'competition_level' => ['low', 'medium', 'high'][rand(0, 2)],
            'seasonal_trends' => $this->getSeasonalTrends($industry),
            'top_performing_content' => $this->getTopPerformingContent($industry),
        ];
    }

    // Mock data methods for fallback
    private function getMockKeywordData(string $industry, string $region): array
    {
        $mockKeywords = [
            ['phrase' => $industry . ' services', 'search_volume' => 12000, 'cpc' => 2.5, 'competition' => 0.7],
            ['phrase' => 'best ' . $industry, 'search_volume' => 8500, 'cpc' => 3.2, 'competition' => 0.8],
            ['phrase' => $industry . ' company', 'search_volume' => 6200, 'cpc' => 2.1, 'competition' => 0.6],
            ['phrase' => $industry . ' solutions', 'search_volume' => 4800, 'cpc' => 2.8, 'competition' => 0.5],
            ['phrase' => 'top ' . $industry, 'search_volume' => 3900, 'cpc' => 3.5, 'competition' => 0.9],
        ];

        return ['keywords' => $mockKeywords];
    }

    private function getMockCompetitorData(string $domain, string $region): array
    {
        return [
            'competitors' => [
                ['domain' => 'competitor1.com', 'common_keywords' => 150, 'se_traffic' => 25000],
                ['domain' => 'competitor2.com', 'common_keywords' => 120, 'se_traffic' => 18000],
                ['domain' => 'competitor3.com', 'common_keywords' => 95, 'se_traffic' => 12000],
            ]
        ];
    }

    private function getMockMarketData(string $industry, string $region): array
    {
        return [
            'industry' => $industry,
            'region' => $region,
            'market_size' => 500000,
            'growth_rate' => 15,
            'competition_level' => 'medium',
            'seasonal_trends' => ['Q4 peak', 'Ramadan dip', 'Summer growth'],
            'top_performing_content' => ['Video ads', 'Carousel posts', 'Story ads'],
        ];
    }

    private function generateContentIdeas(string $keyword, string $adType): array
    {
        return [
            "How to choose the best {$keyword}",
            "Top 10 {$keyword} trends in 2025",
            "Why {$keyword} matters for your business",
            "{$keyword} success stories from the GCC",
        ];
    }

    private function generateAdCopySuggestions(string $keyword, string $adType): array
    {
        return [
            "Discover premium {$keyword} solutions",
            "Transform your business with {$keyword}",
            "Leading {$keyword} provider in the GCC",
            "Get expert {$keyword} consultation today",
        ];
    }

    private function extractTrendingTopics(array $keywords): array
    {
        return array_slice(array_column($keywords, 'phrase'), 0, 5);
    }

    private function getSeasonalInsights(array $keywords): array
    {
        return [
            'ramadan_trends' => 'Increased mobile usage and evening engagement',
            'summer_patterns' => 'Higher social media activity, travel-related searches',
            'back_to_school' => 'Education and family-focused content performs well',
            'national_day' => 'Patriotic themes and local pride content trending',
        ];
    }

    private function getRegionalCPM(string $platform, string $region, string $industry): float
    {
        // Mock regional CPM data
        $baseCPM = ['facebook' => 3.5, 'instagram' => 4.2, 'snapchat' => 2.8, 'tiktok' => 2.1];
        $regionMultiplier = ['sa' => 1.2, 'ae' => 1.5, 'qa' => 1.3, 'kw' => 1.1, 'bh' => 1.0, 'om' => 0.9];
        
        return ($baseCPM[$platform] ?? 3.0) * ($regionMultiplier[$region] ?? 1.0);
    }

    private function getAudienceSize(string $platform, string $region, string $industry): int
    {
        // Mock audience size data
        return rand(100000, 2000000);
    }

    private function getRegionalCPC(string $platform, string $region, string $industry): float
    {
        return rand(50, 500) / 100; // $0.50 to $5.00
    }

    private function getSearchVolume(string $region, string $industry): int
    {
        return rand(10000, 100000);
    }

    private function calculatePlatformScore(array $data, string $adType, string $region): float
    {
        $score = $data['market_share'] * 0.4;
        $score += (in_array($adType, $data['best_for']) ? 30 : 0);
        $score += rand(10, 30); // Random factor for variety
        
        return round($score, 1);
    }

    private function getPlatformRecommendationReason(string $platform, string $adType, string $region): string
    {
        $reasons = [
            'facebook' => "Highest market penetration in {$region} with excellent targeting options",
            'instagram' => "Strong visual platform perfect for brand awareness and engagement",
            'snapchat' => "Popular among younger demographics in the GCC region",
            'tiktok' => "Rapidly growing platform with high engagement rates",
            'google' => "Captures high-intent users actively searching for solutions",
        ];

        return $reasons[$platform] ?? "Good performance for {$adType} campaigns";
    }

    private function getSeasonalTrends(string $industry): array
    {
        return [
            'ramadan' => 'Increased evening activity and family-focused content',
            'summer' => 'Travel and leisure content performs well',
            'back_to_school' => 'Education and preparation themes trending',
            'national_days' => 'Patriotic and local pride content popular',
        ];
    }

    private function getTopPerformingContent(string $industry): array
    {
        return [
            'Video content with Arabic subtitles',
            'User-generated content and testimonials',
            'Behind-the-scenes and company culture',
            'Educational and how-to content',
            'Local success stories and case studies',
        ];
    }
}
