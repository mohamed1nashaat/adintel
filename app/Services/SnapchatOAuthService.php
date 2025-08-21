<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SnapchatOAuthService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected string $baseUrl = 'https://accounts.snapchat.com/login/oauth2';
    protected string $apiUrl = 'https://adsapi.snapchat.com/v1';

    public function __construct()
    {
        $this->clientId = config('services.snapchat.client_id');
        $this->clientSecret = config('services.snapchat.client_secret');
        $this->redirectUri = config('services.snapchat.redirect');
    }

    /**
     * Generate Snapchat OAuth authorization URL
     */
    public function getAuthorizationUrl(): string
    {
        if (!$this->clientId) {
            throw new \Exception('Snapchat Client ID not configured');
        }

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'snapchat-marketing-api',
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
            throw new \Exception('Snapchat OAuth credentials not configured');
        }

        $response = Http::asForm()->post($this->baseUrl . '/access_token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ]);

        if (!$response->successful()) {
            Log::error('Snapchat token exchange failed', [
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
            ->get($this->apiUrl . '/me');

        if (!$response->successful()) {
            Log::error('Snapchat user info request failed', [
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
                ->get($this->apiUrl . '/me');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Snapchat token validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): array
    {
        if (!$this->clientId || !$this->clientSecret) {
            throw new \Exception('Snapchat OAuth credentials not configured');
        }

        $response = Http::asForm()->post($this->baseUrl . '/access_token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('Snapchat token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to refresh token: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get Snapchat ad accounts
     */
    public function getAdAccounts(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get($this->apiUrl . '/me/adaccounts');

            if (!$response->successful()) {
                throw new \Exception('Failed to get ad accounts: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Snapchat ad accounts request failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to get Snapchat ad accounts: ' . $e->getMessage());
        }
    }

    /**
     * Get campaign insights
     */
    public function getCampaignInsights(string $accessToken, array $params = []): array
    {
        try {
            // This would require proper Snapchat Ads API implementation
            // For now, return mock data
            return [
                'campaigns' => [
                    [
                        'id' => 'snapchat_campaign_1',
                        'name' => 'Snapchat Campaign 1',
                        'impressions' => 8000,
                        'swipes' => 400,
                        'spend' => 200.00,
                        'conversions' => 20,
                        'date' => now()->format('Y-m-d')
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat campaign insights request failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to get campaign insights: ' . $e->getMessage());
        }
    }
}
