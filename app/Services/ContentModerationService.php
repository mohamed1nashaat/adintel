<?php

namespace App\Services;

use App\Models\ContentPost;
use App\Models\ContentModeration;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContentModerationService
{
    /**
     * Create a moderation record for a content post
     */
    public function createModerationRecord(ContentPost $post): ContentModeration
    {
        $moderation = ContentModeration::create([
            'tenant_id' => $post->tenant_id,
            'content_post_id' => $post->id,
            'status' => 'pending',
            'priority' => $this->calculatePriority($post),
        ]);

        // Run AI moderation analysis
        $this->runAIModerationAnalysis($moderation);

        return $moderation;
    }

    /**
     * Re-evaluate moderation for a post
     */
    public function reevaluateModeration(ContentPost $post): void
    {
        if ($post->moderation) {
            $post->moderation->update([
                'status' => 'pending',
                'reviewer_id' => null,
                'reviewed_at' => null,
                'feedback' => null,
            ]);

            // Run AI analysis again
            $this->runAIModerationAnalysis($post->moderation);
        } else {
            $this->createModerationRecord($post);
        }
    }

    /**
     * Run AI-powered moderation analysis
     */
    private function runAIModerationAnalysis(ContentModeration $moderation): void
    {
        try {
            $post = $moderation->contentPost;
            $analysisResult = $this->analyzeContentWithAI($post);

            $moderation->update([
                'ai_confidence_score' => $analysisResult['confidence_score'],
                'ai_suggestions' => $analysisResult['suggestions'],
                'compliance_flags' => $analysisResult['compliance_flags'],
                'requires_legal_review' => $analysisResult['requires_legal_review'],
                'requires_brand_review' => $analysisResult['requires_brand_review'],
            ]);

            // Auto-approve if high confidence and no issues
            if ($analysisResult['confidence_score'] >= 0.9 && empty($analysisResult['compliance_flags'])) {
                $this->autoApprove($moderation);
            }
        } catch (\Exception $e) {
            Log::error('AI moderation analysis failed', [
                'moderation_id' => $moderation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Analyze content using AI (placeholder for actual AI service)
     */
    private function analyzeContentWithAI(ContentPost $post): array
    {
        // This would integrate with OpenAI, Google Cloud AI, or similar service
        // For now, we'll return mock analysis
        
        $content = $post->content;
        $title = $post->title;
        
        // Mock analysis based on content characteristics
        $complianceFlags = [];
        $suggestions = [];
        $confidenceScore = 0.8;
        
        // Check for potential issues
        if (str_contains(strtolower($content), 'guarantee') || str_contains(strtolower($title), 'guarantee')) {
            $complianceFlags[] = 'Contains guarantee claims that may need legal review';
            $confidenceScore -= 0.2;
        }
        
        if (preg_match('/\b(buy now|limited time|act fast)\b/i', $content)) {
            $complianceFlags[] = 'Contains high-pressure sales language';
            $suggestions[] = 'Consider softening the call-to-action language';
        }
        
        if (str_word_count($content) > 500) {
            $suggestions[] = 'Content is quite long - consider breaking into multiple posts';
        }
        
        if (empty($post->hashtags)) {
            $suggestions[] = 'Consider adding relevant hashtags to improve reach';
        }
        
        // Check for brand compliance
        $requiresBrandReview = str_contains(strtolower($content), 'partnership') || 
                              str_contains(strtolower($content), 'collaboration');
        
        // Check for legal review
        $requiresLegalReview = !empty(array_filter($complianceFlags, fn($flag) => 
            str_contains(strtolower($flag), 'legal') || 
            str_contains(strtolower($flag), 'guarantee') ||
            str_contains(strtolower($flag), 'claim')
        ));
        
        return [
            'confidence_score' => max(0.1, min(1.0, $confidenceScore)),
            'suggestions' => $suggestions,
            'compliance_flags' => $complianceFlags,
            'requires_legal_review' => $requiresLegalReview,
            'requires_brand_review' => $requiresBrandReview,
        ];
    }

    /**
     * Calculate priority based on post characteristics
     */
    private function calculatePriority(ContentPost $post): string
    {
        // High priority for scheduled posts
        if ($post->scheduled_at && $post->scheduled_at->diffInHours(now()) < 24) {
            return 'high';
        }
        
        // Medium priority for posts with media
        if (!empty($post->media_urls)) {
            return 'medium';
        }
        
        // High priority for multiple platforms
        if (count($post->platforms ?? []) > 2) {
            return 'high';
        }
        
        return 'medium';
    }

    /**
     * Auto-approve a post if it meets criteria
     */
    private function autoApprove(ContentModeration $moderation): void
    {
        $moderation->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'feedback' => 'Auto-approved by AI moderation system',
        ]);

        $moderation->contentPost->update(['status' => 'approved']);
    }

    /**
     * Get moderation queue for reviewers
     */
    public function getModerationQueue(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = ContentModeration::with(['contentPost', 'reviewer'])
            ->pending()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc');

        if (isset($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (isset($filters['requires_legal_review']) && $filters['requires_legal_review']) {
            $query->requiresLegalReview();
        }

        if (isset($filters['requires_brand_review']) && $filters['requires_brand_review']) {
            $query->requiresBrandReview();
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Assign reviewer to moderation
     */
    public function assignReviewer(ContentModeration $moderation, User $reviewer): void
    {
        $moderation->update(['reviewer_id' => $reviewer->id]);
    }

    /**
     * Get moderation statistics
     */
    public function getModerationStatistics(): array
    {
        return [
            'pending_reviews' => ContentModeration::pending()->count(),
            'high_priority' => ContentModeration::pending()->byPriority('high')->count(),
            'urgent_priority' => ContentModeration::pending()->byPriority('urgent')->count(),
            'requires_legal_review' => ContentModeration::pending()->requiresLegalReview()->count(),
            'requires_brand_review' => ContentModeration::pending()->requiresBrandReview()->count(),
            'avg_review_time' => ContentModeration::whereNotNull('reviewed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
                ->value('avg_hours') ?? 0,
            'approval_rate' => $this->calculateApprovalRate(),
        ];
    }

    /**
     * Calculate approval rate
     */
    private function calculateApprovalRate(): float
    {
        $total = ContentModeration::whereIn('status', ['approved', 'rejected'])->count();
        
        if ($total === 0) {
            return 0;
        }
        
        $approved = ContentModeration::byStatus('approved')->count();
        
        return round(($approved / $total) * 100, 2);
    }

    /**
     * Generate content suggestions based on performance data
     */
    public function generateContentSuggestions(ContentPost $post): array
    {
        $suggestions = [];
        
        // Analyze hashtag performance
        if (empty($post->hashtags)) {
            $suggestions[] = [
                'type' => 'hashtags',
                'message' => 'Add relevant hashtags to improve discoverability',
                'priority' => 'medium'
            ];
        }
        
        // Analyze content length
        $wordCount = str_word_count($post->content);
        if ($wordCount < 10) {
            $suggestions[] = [
                'type' => 'content_length',
                'message' => 'Consider adding more context to engage your audience',
                'priority' => 'low'
            ];
        } elseif ($wordCount > 300) {
            $suggestions[] = [
                'type' => 'content_length',
                'message' => 'Consider breaking this into multiple posts for better engagement',
                'priority' => 'medium'
            ];
        }
        
        // Platform-specific suggestions
        foreach ($post->platforms ?? [] as $platform) {
            $suggestions = array_merge($suggestions, $this->getPlatformSpecificSuggestions($platform, $post));
        }
        
        return $suggestions;
    }

    /**
     * Get platform-specific content suggestions
     */
    private function getPlatformSpecificSuggestions(string $platform, ContentPost $post): array
    {
        $suggestions = [];
        
        switch ($platform) {
            case 'twitter':
                if (strlen($post->content) > 280) {
                    $suggestions[] = [
                        'type' => 'platform_optimization',
                        'message' => 'Twitter content should be under 280 characters',
                        'priority' => 'high'
                    ];
                }
                break;
                
            case 'instagram':
                if (empty($post->media_urls)) {
                    $suggestions[] = [
                        'type' => 'platform_optimization',
                        'message' => 'Instagram posts perform better with visual content',
                        'priority' => 'high'
                    ];
                }
                break;
                
            case 'linkedin':
                if (str_word_count($post->content) < 50) {
                    $suggestions[] = [
                        'type' => 'platform_optimization',
                        'message' => 'LinkedIn posts benefit from more detailed, professional content',
                        'priority' => 'medium'
                    ];
                }
                break;
        }
        
        return $suggestions;
    }
}
