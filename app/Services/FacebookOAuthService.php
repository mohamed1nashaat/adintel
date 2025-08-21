<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookOAuthService
{
    protected string $appId;
    protected string $appSecret;
    protected string $apiVersion;
    protected string $baseUrl;

    public function __construct()
    {
        $this->appId = config('services.facebook.app_id');
        $this->appSecret = config('services.facebook.app_secret');
        $this->apiVersion = config('services.facebook.api_version', 'v18.0');
        $this->baseUrl = "https://graph.facebook.com/{$this->apiVersion}";
    }

    /**
     * Generate Facebook OAuth URL for user authorization
     */
    public function getAuthUrl(string $redirectUri): string
    {
        $state = Str::random(32);
        session(['facebook_oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => $this->getRequiredScopes(),
            'response_type' => 'code',
            'auth_type' => 'rerequest', // Force permission dialog
        ]);

        return "https://www.facebook.com/v{$this->apiVersion}/dialog/oauth?{$params}";
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $response = Http::get('https://graph.facebook.com/v' . $this->apiVersion . '/oauth/access_token', [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception(
                'Failed to exchange code for token: ' . 
                ($error['error']['message'] ?? 'Unknown error')
            );
        }

        $tokenData = $response->json();

        // Get long-lived token immediately
        if (isset($tokenData['access_token'])) {
            $longLivedToken = $this->getLongLivedToken($tokenData['access_token']);
            $tokenData = array_merge($tokenData, $longLivedToken);
        }

        return $tokenData;
    }

    /**
     * Get long-lived access token (60 days)
     */
    public function getLongLivedToken(string $shortLivedToken): array
    {
        $response = Http::get($this->baseUrl . '/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception(
                'Failed to get long-lived token: ' . 
                ($error['error']['message'] ?? 'Unknown error')
            );
        }

        return $response->json();
    }

    /**
     * Get user information
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = Http::get($this->baseUrl . '/me', [
            'access_token' => $accessToken,
            'fields' => 'id,name,email,picture.type(large)',
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception(
                'Failed to get user info: ' . 
                ($error['error']['message'] ?? 'Unknown error')
            );
        }

        return $response->json();
    }

    /**
     * Get user's ad accounts
     */
    public function getAdAccounts(string $accessToken): array
    {
        $response = Http::get($this->baseUrl . '/me/adaccounts', [
            'access_token' => $accessToken,
            'fields' => 'id,name,account_status,currency,timezone_name,business,owner',
            'limit' => 100,
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception(
                'Failed to get ad accounts: ' . 
                ($error['error']['message'] ?? 'Unknown error')
            );
        }

        $data = $response->json();
        return $data['data'] ?? [];
    }

    /**
     * Get granted permissions for the access token
     */
    public function getGrantedPermissions(string $accessToken): array
    {
        $response = Http::get($this->baseUrl . '/me/permissions', [
            'access_token' => $accessToken,
        ]);

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        $permissions = [];

        foreach ($data['data'] ?? [] as $permission) {
            if ($permission['status'] === 'granted') {
                $permissions[] = $permission['permission'];
            }
        }

        return $permissions;
    }

    /**
     * Get account insights for multiple ad accounts
     */
    public function getAccountInsights(string $accessToken, array $accountIds): array
    {
        $insights = [];

        foreach ($accountIds as $accountId) {
            try {
                $response = Http::get($this->baseUrl . "/{$accountId}/insights", [
                    'access_token' => $accessToken,
                    'fields' => 'spend,impressions,clicks,ctr,cpm,cpp,reach,frequency',
                    'time_range' => json_encode([
                        'since' => now()->subDays(30)->format('Y-m-d'),
                        'until' => now()->format('Y-m-d'),
                    ]),
                    'level' => 'account',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $insights[$accountId] = $data['data'][0] ?? [];
                }
            } catch (\Exception $e) {
                Log::warning("Failed to get insights for account {$accountId}", [
                    'error' => $e->getMessage()
                ]);
                $insights[$accountId] = [];
            }
        }

        return $insights;
    }

    /**
     * Get campaigns for an ad account
     */
    public function getCampaigns(string $accessToken, string $accountId): array
    {
        $response = Http::get($this->baseUrl . "/{$accountId}/campaigns", [
            'access_token' => $accessToken,
            'fields' => 'id,name,status,objective,created_time,updated_time,start_time,stop_time',
            'limit' => 100,
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception(
                'Failed to get campaigns: ' . 
                ($error['error']['message'] ?? 'Unknown error')
            );
        }

        $data = $response->json();
        return $data['data'] ?? [];
    }

    /**
     * Get campaign insights
     */
    public function getCampaignInsights(string $accessToken, string $campaignId, array $dateRange = null): array
    {
        $params = [
            'access_token' => $accessToken,
            'fields' => 'campaign_id,campaign_name,spend,impressions,clicks,ctr,cpm,cpp,reach,frequency,actions,cost_per_action_type',
            'level' => 'campaign',
        ];

        if ($dateRange) {
            $params['time_range'] = json_encode([
                'since' => $dateRange['since'],
                'until' => $dateRange['until'],
            ]);
        }

        $response = Http::get($this->baseUrl . "/{$campaignId}/insights", $params);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception(
                'Failed to get campaign insights: ' . 
                ($error['error']['message'] ?? 'Unknown error')
            );
        }

        $data = $response->json();
        return $data['data'] ?? [];
    }

    /**
     * Validate access token
     */
    public function validateToken(string $accessToken): bool
    {
        try {
            $response = Http::get($this->baseUrl . '/me', [
                'access_token' => $accessToken,
                'fields' => 'id',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get debug information for access token
     */
    public function debugToken(string $accessToken): array
    {
        $response = Http::get($this->baseUrl . '/debug_token', [
            'input_token' => $accessToken,
            'access_token' => $this->appId . '|' . $this->appSecret,
        ]);

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        return $data['data'] ?? [];
    }

    /**
     * Get required Facebook permissions
     */
    protected function getRequiredScopes(): string
    {
        return implode(',', [
            'ads_read',
            'ads_management',
            'business_management',
            'pages_read_engagement',
            'pages_show_list',
            'email',
            'public_profile',
        ]);
    }

    /**
     * Handle Facebook API errors
     */
    protected function handleApiError(array $error): void
    {
        $code = $error['code'] ?? 0;
        $message = $error['message'] ?? 'Unknown Facebook API error';
        $type = $error['type'] ?? 'FacebookApiException';

        Log::error('Facebook API Error', [
            'code' => $code,
            'message' => $message,
            'type' => $type,
        ]);

        // Handle specific error codes
        switch ($code) {
            case 190: // Invalid access token
                throw new \Exception('Facebook access token is invalid or expired. Please reconnect your account.');
            case 102: // Session key invalid
                throw new \Exception('Facebook session has expired. Please reconnect your account.');
            case 10: // Permission denied
                throw new \Exception('Insufficient permissions for Facebook API. Please grant required permissions.');
            default:
                throw new \Exception("Facebook API Error: {$message}");
        }
    }
}
