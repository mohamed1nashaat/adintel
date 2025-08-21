<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\SnapchatOAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SnapchatIntegrationController extends Controller
{
    protected SnapchatOAuthService $snapchatService;

    public function __construct(SnapchatOAuthService $snapchatService)
    {
        $this->snapchatService = $snapchatService;
    }

    /**
     * Get Snapchat OAuth authorization URL
     */
    public function getAuthUrl(): JsonResponse
    {
        try {
            $authUrl = $this->snapchatService->getAuthorizationUrl();
            
            return response()->json([
                'success' => true,
                'auth_url' => $authUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Snapchat authorization URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Snapchat OAuth callback
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
            $tokenData = $this->snapchatService->exchangeCodeForToken($code);
            
            // Get user info
            $userInfo = $this->snapchatService->getUserInfo($tokenData['access_token']);
            
            // Store integration
            $integration = Integration::create([
                'tenant_id' => Auth::user()->default_tenant_id,
                'platform' => 'snapchat',
                'external_account_id' => $userInfo['me']['id'],
                'account_name' => $userInfo['me']['display_name'] ?? $userInfo['me']['email'],
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in']) 
                    ? now()->addSeconds($tokenData['expires_in']) 
                    : null,
                'status' => 'active',
                'app_config' => [
                    'scopes' => $tokenData['scope'] ?? 'snapchat-marketing-api',
                    'token_type' => $tokenData['token_type'] ?? 'Bearer'
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Snapchat Ads account connected successfully',
                'integration' => $integration
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect Snapchat Ads account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Snapchat connection
     */
    public function testConnection(Integration $integration): JsonResponse
    {
        try {
            $isValid = $this->snapchatService->validateToken($integration->access_token);
            
            if (!$isValid && $integration->refresh_token) {
                // Try to refresh token
                $newTokenData = $this->snapchatService->refreshToken($integration->refresh_token);
                
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
     * Refresh Snapchat access token
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

            $newTokenData = $this->snapchatService->refreshToken($integration->refresh_token);
            
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
