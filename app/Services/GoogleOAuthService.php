<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleOAuthService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected string $baseUrl = 'https://accounts.google.com/o/oauth2/v2';
    protected string $apiUrl = 'https://www.googleapis.com/oauth2/v2';

    public function __construct()
    {
        $this->clientId = config('services.google.client_id');
        $this->clientSecret = config('services.google.client_secret');
        $this->redirectUri = config('services.google.redirect');
    }

    /**
     * Generate Google OAuth authorization URL
     */
    public function getAuthorizationUrl(): string
    {
        if (!$this->clientId) {
            throw new \Exception('Google Client ID not configured');
        }

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'https://www.googleapis.com/auth/adwords https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => csrf_token(),
        ];

        return $this->baseUrl . '/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code): array
    {
        if (!$this->clientId || !$this->clientSecret) {
            throw new \Exception('Google OAuth credentials not configured');
        }

        $response = Http::asForm()->post($this->baseUrl . '/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ]);

        if (!$response->successful()) {
            Log::error('Google token exchange failed', [
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
            ->get($this->apiUrl . '/userinfo');

        if (!$response->successful()) {
            Log::error('Google user info request failed', [
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
                ->get($this->apiUrl . '/userinfo');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Google token validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): array
    {
        if (!$this->clientId || !$this->clientSecret) {
            throw new \Exception('Google OAuth credentials not configured');
        }

        $response = Http::asForm()->post($this->baseUrl . '/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('Google token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to refresh token: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get Google Ads accounts
     */
    public function getAdAccounts(string $accessToken): array
    {
        try {
            // This would require Google Ads API setup
            // For now, return mock data
            return [
                'accounts' => [
                    [
                        'id' => 'google_account_1',
                        'name' => 'Google Ads Account 1',
                        'currency' => 'USD',
                        'timezone' => 'America/New_York'
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Google Ads accounts request failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to get Google Ads accounts: ' . $e->getMessage());
        }
    }

    /**
     * Get campaign insights
     */
    public function getCampaignInsights(string $accessToken, array $params = []): array
    {
        try {
            // This would require Google Ads API setup
            // For now, return mock data
            return [
                'data' => [
                    [
                        'campaign_id' => 'google_campaign_1',
                        'campaign_name' => 'Google Campaign 1',
                        'impressions' => 10000,
                        'clicks' => 500,
                        'spend' => 250.00,
                        'conversions' => 25,
                        'date' => now()->format('Y-m-d')
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Google campaign insights request failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to get campaign insights: ' . $e->getMessage());
        }
    }
}
