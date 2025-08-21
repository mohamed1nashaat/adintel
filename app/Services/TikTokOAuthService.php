<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokOAuthService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected string $baseUrl = 'https://www.tiktok.com/v2/auth';
    protected string $apiUrl = 'https://open.tiktokapis.com/v2';

    public function __construct()
    {
        $this->clientId = config('services.tiktok.client_id');
        $this->clientSecret = config('services.tiktok.client_secret');
        $this->redirectUri = config('services.tiktok.redirect');
    }

    /**
     * Generate TikTok OAuth authorization URL
     */
    public function getAuthorizationUrl(): string
    {
        if (!$this->clientId) {
            throw new \Exception('TikTok Client ID not configured');
        }

        $params = [
            'client_key' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'user.info.basic,video.list',
            'state' => csrf_token(),
        ];

        return $this->baseUrl . '/authorize?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code): array
    {
        if (!$this->clientId || !$this->clientSecret) {
            throw new \Exception('TikTok OAuth credentials not configured');
        }

        $response = Http::asForm()->post($this->baseUrl . '/token', [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ]);

        if (!$response->successful()) {
            Log::error('TikTok token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to exchange code for token: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get user information using access token
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->post($this->apiUrl . '/user/info/', [
                'fields' => 'open_id,union_id,avatar_url,display_name'
            ]);

        if (!$response->successful()) {
            Log::error('TikTok user info request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to get user info: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Validate access token
     */
    public function validateToken(string $accessToken): bool
    {
        try {
            $response = Http::withToken($accessToken)
                ->post($this->apiUrl . '/user/info/', [
                    'fields' => 'open_id'
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('TikTok token validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): array
    {
        if (!$this->clientId || !$this->clientSecret) {
            throw new \Exception('TikTok OAuth credentials not configured');
        }

        $response = Http::asForm()->post($this->baseUrl . '/refresh_token', [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('TikTok token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to refresh token: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get TikTok videos
     */
    public function getVideos(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->post($this->apiUrl . '/video/list/', [
                    'fields' => 'id,title,video_description,duration,cover_image_url,embed_html,embed_link'
                ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to get videos: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('TikTok videos request failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to get TikTok videos: ' . $e->getMessage());
        }
    }

    /**
     * Get campaign insights (mock data for now)
     */
    public function getCampaignInsights(string $accessToken, array $params = []): array
    {
        try {
            // This would require TikTok Ads API implementation
            // For now, return mock data
            return [
                'campaigns' => [
                    [
                        'id' => 'tiktok_campaign_1',
                        'name' => 'TikTok Campaign 1',
                        'impressions' => 15000,
                        'clicks' => 750,
                        'spend' => 300.00,
                        'conversions' => 30,
                        'date' => now()->format('Y-m-d')
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('TikTok campaign insights request failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to get campaign insights: ' . $e->getMessage());
        }
    }
}
