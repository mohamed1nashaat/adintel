<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\FacebookOAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FacebookIntegrationController extends Controller
{
    protected FacebookOAuthService $facebookService;

    public function __construct(FacebookOAuthService $facebookService)
    {
        $this->facebookService = $facebookService;
    }

    /**
     * Get Facebook OAuth URL for one-click authentication
     */
    public function getAuthUrl(Request $request): JsonResponse
    {
        try {
            $redirectUri = $request->get('redirect_uri', config('app.url') . '/integrations/facebook/callback');
            $authUrl = $this->facebookService->getAuthUrl($redirectUri);

            return response()->json([
                'auth_url' => $authUrl,
                'state' => session('facebook_oauth_state'),
            ]);
        } catch (\Exception $e) {
            Log::error('Facebook OAuth URL generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to generate Facebook authentication URL',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Facebook OAuth callback and create integration
     */
    public function handleCallback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'state' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid callback parameters',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify state parameter
            if ($request->state !== session('facebook_oauth_state')) {
                return response()->json([
                    'error' => 'Invalid state parameter'
                ], 400);
            }

            // Exchange code for access token
            $tokenData = $this->facebookService->exchangeCodeForToken(
                $request->code,
                config('app.url') . '/integrations/facebook/callback'
            );

            // Get user info and ad accounts
            $userInfo = $this->facebookService->getUserInfo($tokenData['access_token']);
            $adAccounts = $this->facebookService->getAdAccounts($tokenData['access_token']);

            // Create or update integration
            $integration = Integration::updateOrCreate(
                [
                    'tenant_id' => session('current_tenant_id'),
                    'platform' => 'facebook',
                ],
                [
                    'app_config' => [
                        'app_id' => config('services.facebook.app_id'),
                        'app_secret' => config('services.facebook.app_secret'),
                        'access_token' => $tokenData['access_token'],
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'expires_in' => $tokenData['expires_in'] ?? null,
                        'user_id' => $userInfo['id'],
                        'user_name' => $userInfo['name'],
                        'user_email' => $userInfo['email'] ?? null,
                        'permissions' => $this->facebookService->getGrantedPermissions($tokenData['access_token']),
                        'connected_at' => now()->toISOString(),
                    ],
                    'created_by' => $request->user()->id,
                    'status' => 'active',
                ]
            );

            // Store ad accounts
            $this->storeAdAccounts($integration, $adAccounts, $tokenData['access_token']);

            return response()->json([
                'success' => true,
                'integration' => $integration->load('adAccounts'),
                'message' => 'Facebook integration connected successfully',
                'accounts_found' => count($adAccounts),
                'user_info' => $userInfo,
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Failed to complete Facebook integration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test existing Facebook integration
     */
    public function testConnection(Integration $integration): JsonResponse
    {
        try {
            $credentials = $integration->getCredentials();
            
            if (!isset($credentials['access_token'])) {
                return response()->json([
                    'error' => 'No access token found'
                ], 400);
            }

            // Test the connection by fetching user info
            $userInfo = $this->facebookService->getUserInfo($credentials['access_token']);
            $adAccounts = $this->facebookService->getAdAccounts($credentials['access_token']);

            return response()->json([
                'success' => true,
                'message' => 'Facebook integration is working correctly',
                'user_info' => $userInfo,
                'accounts_count' => count($adAccounts),
                'last_tested' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook integration test failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);

            // Update integration status to error
            $integration->update(['status' => 'error']);

            return response()->json([
                'error' => 'Facebook integration test failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh Facebook access token
     */
    public function refreshToken(Integration $integration): JsonResponse
    {
        try {
            $credentials = $integration->getCredentials();
            
            if (!isset($credentials['access_token'])) {
                return response()->json([
                    'error' => 'No access token found'
                ], 400);
            }

            // Get long-lived token
            $newTokenData = $this->facebookService->getLongLivedToken($credentials['access_token']);

            // Update integration with new token
            $updatedCredentials = array_merge($credentials, [
                'access_token' => $newTokenData['access_token'],
                'expires_in' => $newTokenData['expires_in'] ?? null,
                'token_refreshed_at' => now()->toISOString(),
            ]);

            $integration->updateCredentials($updatedCredentials);

            return response()->json([
                'success' => true,
                'message' => 'Facebook access token refreshed successfully',
                'expires_in' => $newTokenData['expires_in'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook token refresh failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to refresh Facebook access token',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Facebook integration insights
     */
    public function getInsights(Integration $integration): JsonResponse
    {
        try {
            $credentials = $integration->getCredentials();
            
            if (!isset($credentials['access_token'])) {
                return response()->json([
                    'error' => 'No access token found'
                ], 400);
            }

            $insights = $this->facebookService->getAccountInsights(
                $credentials['access_token'],
                $integration->adAccounts->pluck('external_account_id')->toArray()
            );

            return response()->json([
                'success' => true,
                'insights' => $insights,
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook insights fetch failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch Facebook insights',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store ad accounts from Facebook API
     */
    private function storeAdAccounts(Integration $integration, array $adAccounts, string $accessToken): void
    {
        foreach ($adAccounts as $account) {
            $integration->adAccounts()->updateOrCreate(
                [
                    'external_account_id' => $account['id'],
                ],
                [
                    'tenant_id' => $integration->tenant_id,
                    'account_name' => $account['name'],
                    'currency' => $account['currency'] ?? 'USD',
                    'timezone' => $account['timezone_name'] ?? 'UTC',
                    'status' => $account['account_status'] == 1 ? 'active' : 'inactive',
                ]
            );
        }
    }
}
