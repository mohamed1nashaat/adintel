<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\TikTokOAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TikTokIntegrationController extends Controller
{
    protected TikTokOAuthService $tiktokService;

    public function __construct(TikTokOAuthService $tiktokService)
    {
        $this->tiktokService = $tiktokService;
    }

    /**
     * Get TikTok OAuth authorization URL
     */
    public function getAuthUrl(): JsonResponse
    {
        try {
            $authUrl = $this->tiktokService->getAuthorizationUrl();
            
            return response()->json([
                'success' => true,
                'auth_url' => $authUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate TikTok authorization URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle TikTok OAuth callback
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $code = $request->input('code');

            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization code not provided'
                ], 400);
            }

            // Exchange code for access token
            $tokenData = $this->tiktokService->exchangeCodeForToken($code);
            
            // Get user info
            $userInfo = $this->tiktokService->getUserInfo($tokenData['access_token']);
            
            // Store integration
            $integration = Integration::create([
                'tenant_id' => Auth::user()->default_tenant_id,
                'platform' => 'tiktok',
                'external_account_id' => $userInfo['data']['user']['user_id'],
                'account_name' => $userInfo['data']['user']['display_name'] ?? 'TikTok User',
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in']) 
                    ? now()->addSeconds($tokenData['expires_in']) 
                    : null,
                'status' => 'active',
                'app_config' => [
                    'scopes' => $tokenData['scope'] ?? 'user.info.basic,video.list',
                    'token_type' => $tokenData['token_type'] ?? 'Bearer'
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'TikTok Ads account connected successfully',
                'integration' => $integration
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect TikTok Ads account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test TikTok connection
     */
    public function testConnection(Integration $integration): JsonResponse
    {
        try {
            $isValid = $this->tiktokService->validateToken($integration->access_token);
            
            if (!$isValid && $integration->refresh_token) {
                // Try to refresh token
                $newTokenData = $this->tiktokService->refreshToken($integration->refresh_token);
                
                $integration->update([
                    'access_token' => $newTokenData['access_token'],
                    'token_expires_at' => isset($newTokenData['expires_in']) 
                        ? now()->addSeconds($newTokenData['expires_in']) 
                        : null,
                ]);
                
                $isValid = true;
            }

            return response()->json([
                'success' => $isValid,
                'message' => $isValid ? 'Connection is valid' : 'Connection failed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh TikTok access token
     */
    public function refreshToken(Integration $integration): JsonResponse
    {
        try {
            if (!$integration->refresh_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No refresh token available'
                ], 400);
            }

            $newTokenData = $this->tiktokService->refreshToken($integration->refresh_token);
            
            $integration->update([
                'access_token' => $newTokenData['access_token'],
                'token_expires_at' => isset($newTokenData['expires_in']) 
                    ? now()->addSeconds($newTokenData['expires_in']) 
                    : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'integration' => $integration->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token: ' . $e->getMessage()
            ], 500);
        }
    }
}
