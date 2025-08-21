<?php

namespace App\Services;

use App\Models\ScheduledPost;
use App\Models\ContentPost;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SocialMediaPublishingService
{
    public function publishScheduledPost(ScheduledPost $scheduledPost): array
    {
        $scheduledPost->markAsPublishing();
        
        $results = [];
        $overallSuccess = true;

        foreach ($scheduledPost->platforms as $platform) {
            try {
                $result = $this->publishToPlatform($scheduledPost, $platform);
                $results[$platform] = $result;
                
                if (!$result['success']) {
                    $overallSuccess = false;
                }
                
                Log::info("Published to {$platform}", [
                    'scheduled_post_id' => $scheduledPost->id,
                    'success' => $result['success'],
                ]);
                
            } catch (\Exception $e) {
                $results[$platform] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'published_at' => now()->toISOString(),
                ];
                $overallSuccess = false;
                
                Log::error("Failed to publish to {$platform}", [
                    'scheduled_post_id' => $scheduledPost->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($overallSuccess) {
            $scheduledPost->markAsPublished($results);
        } else {
            $scheduledPost->markAsFailed('One or more platforms failed to publish');
            $scheduledPost->update(['publish_results' => $results]);
        }

        return $results;
    }

    private function publishToPlatform(ScheduledPost $scheduledPost, string $platform): array
    {
        $contentPost = $scheduledPost->contentPost;
        $platformConfig = $scheduledPost->getPlatformConfig($platform);
        
        return match($platform) {
            'facebook' => $this->publishToFacebook($contentPost, $platformConfig),
            'instagram' => $this->publishToInstagram($contentPost, $platformConfig),
            'twitter' => $this->publishToTwitter($contentPost, $platformConfig),
            'linkedin' => $this->publishToLinkedIn($contentPost, $platformConfig),
            'tiktok' => $this->publishToTikTok($contentPost, $platformConfig),
            'youtube' => $this->publishToYouTube($contentPost, $platformConfig),
            default => throw new \InvalidArgumentException("Unsupported platform: {$platform}"),
        };
    }

    private function publishToFacebook(ContentPost $contentPost, array $config): array
    {
        try {
            $accessToken = $config['access_token'] ?? null;
            $pageId = $config['page_id'] ?? null;
            
            if (!$accessToken || !$pageId) {
                throw new \Exception('Facebook access token or page ID not configured');
            }

            $content = $contentPost->platform_specific_content['facebook'] ?? $contentPost->content;
            
            $postData = [
                'message' => $content,
                'access_token' => $accessToken,
            ];

            // Add media if present
            if (!empty($contentPost->media_urls)) {
                $postData['link'] = $contentPost->media_urls[0];
            }

            $response = Http::post("https://graph.facebook.com/v18.0/{$pageId}/feed", $postData);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'platform_post_id' => $data['id'] ?? null,
                    'published_at' => now()->toISOString(),
                    'response' => $data,
                ];
            } else {
                throw new \Exception('Facebook API error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'published_at' => now()->toISOString(),
            ];
        }
    }

    private function publishToInstagram(ContentPost $contentPost, array $config): array
    {
        try {
            $accessToken = $config['access_token'] ?? null;
            $accountId = $config['account_id'] ?? null;
            
            if (!$accessToken || !$accountId) {
                throw new \Exception('Instagram access token or account ID not configured');
            }

            $content = $contentPost->platform_specific_content['instagram'] ?? $contentPost->content;
            $mediaUrl = $contentPost->media_urls[0] ?? null;
            
            if (!$mediaUrl) {
                throw new \Exception('Instagram requires media content');
            }

            // Step 1: Create media container
            $containerData = [
                'image_url' => $mediaUrl,
                'caption' => $content,
                'access_token' => $accessToken,
            ];

            $containerResponse = Http::post("https://graph.facebook.com/v18.0/{$accountId}/media", $containerData);
            
            if (!$containerResponse->successful()) {
                throw new \Exception('Instagram container creation failed: ' . $containerResponse->body());
            }

            $containerId = $containerResponse->json()['id'];

            // Step 2: Publish the media
            $publishData = [
                'creation_id' => $containerId,
                'access_token' => $accessToken,
            ];

            $publishResponse = Http::post("https://graph.facebook.com/v18.0/{$accountId}/media_publish", $publishData);
            
            if ($publishResponse->successful()) {
                $data = $publishResponse->json();
                return [
                    'success' => true,
                    'platform_post_id' => $data['id'] ?? null,
                    'published_at' => now()->toISOString(),
                    'response' => $data,
                ];
            } else {
                throw new \Exception('Instagram publish failed: ' . $publishResponse->body());
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'published_at' => now()->toISOString(),
            ];
        }
    }

    private function publishToTwitter(ContentPost $contentPost, array $config): array
    {
        try {
            $bearerToken = $config['bearer_token'] ?? null;
            
            if (!$bearerToken) {
                throw new \Exception('Twitter bearer token not configured');
            }

            $content = $contentPost->platform_specific_content['twitter'] ?? $contentPost->content;
            
            // Truncate content to Twitter's character limit
            if (strlen($content) > 280) {
                $content = substr($content, 0, 277) . '...';
            }

            $postData = [
                'text' => $content,
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$bearerToken}",
                'Content-Type' => 'application/json',
            ])->post('https://api.twitter.com/2/tweets', $postData);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'platform_post_id' => $data['data']['id'] ?? null,
                    'published_at' => now()->toISOString(),
                    'response' => $data,
                ];
            } else {
                throw new \Exception('Twitter API error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'published_at' => now()->toISOString(),
            ];
        }
    }

    private function publishToLinkedIn(ContentPost $contentPost, array $config): array
    {
        try {
            $accessToken = $config['access_token'] ?? null;
            $personId = $config['person_id'] ?? null;
            
            if (!$accessToken || !$personId) {
                throw new \Exception('LinkedIn access token or person ID not configured');
            }

            $content = $contentPost->platform_specific_content['linkedin'] ?? $contentPost->content;
            
            $postData = [
                'author' => "urn:li:person:{$personId}",
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $content,
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0',
            ])->post('https://api.linkedin.com/v2/ugcPosts', $postData);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'platform_post_id' => $data['id'] ?? null,
                    'published_at' => now()->toISOString(),
                    'response' => $data,
                ];
            } else {
                throw new \Exception('LinkedIn API error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'published_at' => now()->toISOString(),
            ];
        }
    }

    private function publishToTikTok(ContentPost $contentPost, array $config): array
    {
        try {
            $accessToken = $config['access_token'] ?? null;
            
            if (!$accessToken) {
                throw new \Exception('TikTok access token not configured');
            }

            $content = $contentPost->platform_specific_content['tiktok'] ?? $contentPost->content;
            $videoUrl = $contentPost->media_urls[0] ?? null;
            
            if (!$videoUrl) {
                throw new \Exception('TikTok requires video content');
            }

            // Note: TikTok API implementation would be more complex
            // This is a simplified version
            $postData = [
                'video_url' => $videoUrl,
                'text' => $content,
                'privacy_level' => 'PUBLIC_TO_EVERYONE',
            ];

            // TikTok API endpoint (placeholder)
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post('https://open-api.tiktok.com/share/video/upload/', $postData);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'platform_post_id' => $data['share_id'] ?? null,
                    'published_at' => now()->toISOString(),
                    'response' => $data,
                ];
            } else {
                throw new \Exception('TikTok API error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'published_at' => now()->toISOString(),
            ];
        }
    }

    private function publishToYouTube(ContentPost $contentPost, array $config): array
    {
        try {
            $accessToken = $config['access_token'] ?? null;
            
            if (!$accessToken) {
                throw new \Exception('YouTube access token not configured');
            }

            $content = $contentPost->platform_specific_content['youtube'] ?? $contentPost->content;
            $videoUrl = $contentPost->media_urls[0] ?? null;
            
            if (!$videoUrl) {
                throw new \Exception('YouTube requires video content');
            }

            // Note: YouTube API implementation would require video upload
            // This is a simplified version for demonstration
            return [
                'success' => false,
                'error' => 'YouTube publishing not yet implemented',
                'published_at' => now()->toISOString(),
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'published_at' => now()->toISOString(),
            ];
        }
    }

    public function generatePreview(ContentPost $contentPost, array $platforms, array $platformConfigs = []): array
    {
        $preview = [
            'content' => $contentPost->content,
            'media' => $contentPost->media_urls ?? [],
            'platforms' => [],
        ];

        foreach ($platforms as $platform) {
            $config = $platformConfigs[$platform] ?? [];
            $platformContent = $contentPost->platform_specific_content[$platform] ?? $contentPost->content;
            
            $preview['platforms'][$platform] = [
                'content' => $this->formatContentForPlatform($platformContent, $platform),
                'hashtags' => $contentPost->hashtags ?? [],
                'mentions' => $contentPost->mentions ?? [],
                'character_count' => strlen($platformContent),
                'character_limit' => $this->getCharacterLimit($platform),
                'estimated_reach' => $this->estimateReach($platform),
                'best_time_to_post' => $this->getBestTimeToPost($platform),
                'platform_specific_tips' => $this->getPlatformTips($platform),
            ];
        }

        return $preview;
    }

    private function formatContentForPlatform(string $content, string $platform): string
    {
        return match($platform) {
            'twitter' => strlen($content) > 280 ? substr($content, 0, 277) . '...' : $content,
            'instagram' => $content . "\n\n" . $this->generateHashtags($platform),
            'linkedin' => $this->formatForProfessional($content),
            default => $content,
        };
    }

    private function getCharacterLimit(string $platform): int
    {
        return match($platform) {
            'twitter' => 280,
            'facebook' => 63206,
            'instagram' => 2200,
            'linkedin' => 3000,
            'tiktok' => 150,
            'youtube' => 5000,
            default => 1000,
        };
    }

    private function estimateReach(string $platform): int
    {
        return match($platform) {
            'facebook' => rand(100, 1000),
            'instagram' => rand(200, 1500),
            'twitter' => rand(50, 500),
            'linkedin' => rand(25, 250),
            'tiktok' => rand(500, 5000),
            'youtube' => rand(100, 2000),
            default => rand(100, 500),
        };
    }

    private function getBestTimeToPost(string $platform): string
    {
        return match($platform) {
            'facebook' => '1:00 PM - 3:00 PM',
            'instagram' => '11:00 AM - 1:00 PM',
            'twitter' => '9:00 AM - 10:00 AM',
            'linkedin' => '8:00 AM - 10:00 AM',
            'tiktok' => '6:00 AM - 10:00 AM',
            'youtube' => '2:00 PM - 4:00 PM',
            default => '12:00 PM - 2:00 PM',
        };
    }

    private function getPlatformTips(string $platform): array
    {
        return match($platform) {
            'facebook' => [
                'Use engaging visuals',
                'Ask questions to encourage comments',
                'Post when your audience is most active',
            ],
            'instagram' => [
                'Use high-quality images',
                'Include relevant hashtags',
                'Post consistently',
            ],
            'twitter' => [
                'Keep it concise',
                'Use trending hashtags',
                'Engage with replies quickly',
            ],
            'linkedin' => [
                'Share professional insights',
                'Use industry-specific language',
                'Include a call-to-action',
            ],
            'tiktok' => [
                'Use trending sounds',
                'Keep videos short and engaging',
                'Post at peak hours',
            ],
            default => [
                'Know your audience',
                'Post consistently',
                'Engage with your community',
            ],
        };
    }

    private function generateHashtags(string $platform): string
    {
        $hashtags = match($platform) {
            'instagram' => ['#marketing', '#socialmedia', '#business', '#growth'],
            'twitter' => ['#marketing', '#socialmedia'],
            'linkedin' => ['#business', '#professional', '#networking'],
            default => ['#marketing', '#business'],
        };

        return implode(' ', $hashtags);
    }

    private function formatForProfessional(string $content): string
    {
        // Add professional formatting for LinkedIn
        return $content . "\n\nWhat are your thoughts on this?";
    }

    public function retryFailedPosts(): array
    {
        $failedPosts = ScheduledPost::needsRetry()->limit(10)->get();
        $results = ['processed' => 0, 'failed' => 0];

        foreach ($failedPosts as $post) {
            try {
                $this->publishScheduledPost($post);
                $results['processed']++;
            } catch (\Exception $e) {
                $post->scheduleRetry();
                $results['failed']++;
                
                Log::error('Retry failed for scheduled post', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
