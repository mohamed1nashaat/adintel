<?php

namespace App\Services\Integrations\Contracts;

interface AdProvider
{
    /**
     * Generate authorization URL for OAuth flow
     */
    public function authorizeUrl(string $redirectUrl, array $scopes): string;

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCode(string $code, string $redirectUrl): TokenPayload;

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): TokenPayload;

    /**
     * List all ad accounts accessible with current credentials
     * @return Account[]
     */
    public function listAdAccounts(): array;

    /**
     * Fetch campaigns for given account
     * @return Campaign[]
     */
    public function fetchCampaigns(array $params): array;

    /**
     * Fetch ad creatives for given campaign
     * @return Creative[]
     */
    public function fetchCreatives(array $params): array;

    /**
     * Fetch ad metrics for given date range
     * @return Metric[]
     */
    public function fetchMetrics(array $params): array;

    /**
     * Get rate limit information
     */
    public function getRateLimitInfo(): RateLimitInfo;
}
