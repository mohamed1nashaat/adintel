<?php

namespace App\Services;

use App\Models\ScheduledPost;
use App\Models\ContentPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchedulingService
{
    /**
     * Schedule a single post
     */
    public function schedulePost(array $data, int $tenantId, int $userId): ScheduledPost
    {
        DB::beginTransaction();
        
        try {
            $scheduledPost = ScheduledPost::create([
                'tenant_id' => $tenantId,
                'content_post_id' => $data['content_post_id'],
                'platform' => $data['platform'],
                'scheduled_at' => Carbon::parse($data['scheduled_at'])->setTimezone($data['timezone']),
                'timezone' => $data['timezone'],
                'status' => 'scheduled',
                'recurring' => $data['recurring'] ?? false,
                'recurring_pattern' => $data['recurring_pattern'] ?? null,
                'recurring_end_date' => isset($data['recurring_end_date']) 
                    ? Carbon::parse($data['recurring_end_date']) 
                    : null,
                'created_by' => $userId,
            ]);

            // If recurring, create additional scheduled posts
            if ($scheduledPost->recurring && $scheduledPost->recurring_pattern) {
                $this->createRecurringPosts($scheduledPost);
            }

            DB::commit();
            
            Log::info('Post scheduled successfully', [
                'scheduled_post_id' => $scheduledPost->id,
                'platform' => $scheduledPost->platform,
                'scheduled_at' => $scheduledPost->scheduled_at,
            ]);

            return $scheduledPost;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to schedule post', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Update scheduled post
     */
    public function updateScheduledPost(ScheduledPost $scheduledPost, array $data): ScheduledPost
    {
        DB::beginTransaction();
        
        try {
            if (isset($data['scheduled_at']) && isset($data['timezone'])) {
                $scheduledPost->scheduled_at = Carbon::parse($data['scheduled_at'])
                    ->setTimezone($data['timezone']);
                $scheduledPost->timezone = $data['timezone'];
            }

            if (isset($data['status'])) {
                $scheduledPost->status = $data['status'];
            }

            $scheduledPost->save();

            DB::commit();
            
            Log::info('Scheduled post updated', [
                'scheduled_post_id' => $scheduledPost->id,
                'changes' => $data,
            ]);

            return $scheduledPost;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update scheduled post', [
                'error' => $e->getMessage(),
                'scheduled_post_id' => $scheduledPost->id,
            ]);
            throw $e;
        }
    }

    /**
     * Cancel scheduled post
     */
    public function cancelScheduledPost(ScheduledPost $scheduledPost): bool
    {
        try {
            $scheduledPost->update(['status' => 'cancelled']);
            
            Log::info('Scheduled post cancelled', [
                'scheduled_post_id' => $scheduledPost->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel scheduled post', [
                'error' => $e->getMessage(),
                'scheduled_post_id' => $scheduledPost->id,
            ]);
            throw $e;
        }
    }

    /**
     * Bulk schedule posts
     */
    public function bulkSchedulePosts(array $posts, string $timezone, int $tenantId, int $userId): array
    {
        DB::beginTransaction();
        
        try {
            $scheduledPosts = [];

            foreach ($posts as $postData) {
                $scheduledPost = ScheduledPost::create([
                    'tenant_id' => $tenantId,
                    'content_post_id' => $postData['content_post_id'],
                    'platform' => $postData['platform'],
                    'scheduled_at' => Carbon::parse($postData['scheduled_at'])->setTimezone($timezone),
                    'timezone' => $timezone,
                    'status' => 'scheduled',
                    'created_by' => $userId,
                ]);

                $scheduledPosts[] = $scheduledPost;
            }

            DB::commit();
            
            Log::info('Bulk posts scheduled', [
                'count' => count($scheduledPosts),
                'tenant_id' => $tenantId,
            ]);

            return $scheduledPosts;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk schedule posts', [
                'error' => $e->getMessage(),
                'posts_count' => count($posts),
            ]);
            throw $e;
        }
    }

    /**
     * Create recurring posts
     */
    protected function createRecurringPosts(ScheduledPost $originalPost): void
    {
        if (!$originalPost->recurring_end_date) {
            return;
        }

        $currentDate = Carbon::parse($originalPost->scheduled_at);
        $endDate = Carbon::parse($originalPost->recurring_end_date);
        $recurringPosts = [];

        while ($currentDate->lt($endDate)) {
            switch ($originalPost->recurring_pattern) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
                default:
                    return;
            }

            if ($currentDate->lte($endDate)) {
                $recurringPosts[] = [
                    'tenant_id' => $originalPost->tenant_id,
                    'content_post_id' => $originalPost->content_post_id,
                    'platform' => $originalPost->platform,
                    'scheduled_at' => $currentDate->copy(),
                    'timezone' => $originalPost->timezone,
                    'status' => 'scheduled',
                    'recurring' => false, // Recurring posts are not themselves recurring
                    'parent_scheduled_post_id' => $originalPost->id,
                    'created_by' => $originalPost->created_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($recurringPosts)) {
            ScheduledPost::insert($recurringPosts);
            
            Log::info('Recurring posts created', [
                'parent_post_id' => $originalPost->id,
                'recurring_posts_count' => count($recurringPosts),
            ]);
        }
    }

    /**
     * Get posts ready for publishing
     */
    public function getPostsReadyForPublishing(): \Illuminate\Database\Eloquent\Collection
    {
        return ScheduledPost::with(['contentPost'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();
    }

    /**
     * Mark post as published
     */
    public function markAsPublished(ScheduledPost $scheduledPost, array $publishResult = []): void
    {
        $scheduledPost->update([
            'status' => 'published',
            'published_at' => now(),
            'publish_result' => $publishResult,
        ]);

        Log::info('Post marked as published', [
            'scheduled_post_id' => $scheduledPost->id,
            'platform' => $scheduledPost->platform,
        ]);
    }

    /**
     * Mark post as failed
     */
    public function markAsFailed(ScheduledPost $scheduledPost, string $errorMessage): void
    {
        $scheduledPost->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);

        Log::error('Post publishing failed', [
            'scheduled_post_id' => $scheduledPost->id,
            'platform' => $scheduledPost->platform,
            'error' => $errorMessage,
        ]);
    }

    /**
     * Get scheduling statistics
     */
    public function getSchedulingStats(int $tenantId): array
    {
        return [
            'total_scheduled' => ScheduledPost::where('tenant_id', $tenantId)
                ->where('status', 'scheduled')
                ->count(),
            'published_today' => ScheduledPost::where('tenant_id', $tenantId)
                ->where('status', 'published')
                ->whereDate('published_at', today())
                ->count(),
            'failed_today' => ScheduledPost::where('tenant_id', $tenantId)
                ->where('status', 'failed')
                ->whereDate('failed_at', today())
                ->count(),
            'upcoming_week' => ScheduledPost::where('tenant_id', $tenantId)
                ->where('status', 'scheduled')
                ->whereBetween('scheduled_at', [now(), now()->addWeek()])
                ->count(),
        ];
    }
}
