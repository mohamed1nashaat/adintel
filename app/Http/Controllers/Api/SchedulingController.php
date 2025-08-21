<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledPost;
use App\Services\SchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SchedulingController extends Controller
{
    protected $schedulingService;

    public function __construct(SchedulingService $schedulingService)
    {
        $this->schedulingService = $schedulingService;
    }

    /**
     * Get all scheduled posts
     */
    public function index(Request $request): JsonResponse
    {
        $posts = ScheduledPost::with(['contentPost'])
            ->where('tenant_id', session('current_tenant_id'))
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->platform, function ($query, $platform) {
                return $query->where('platform', $platform);
            })
            ->orderBy('scheduled_at', 'asc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Schedule a new post
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content_post_id' => 'required|exists:content_posts,id',
            'platform' => 'required|in:facebook,instagram,twitter,linkedin,tiktok,snapchat,youtube,whatsapp',
            'scheduled_at' => 'required|date|after:now',
            'timezone' => 'required|string',
            'recurring' => 'boolean',
            'recurring_pattern' => 'nullable|in:daily,weekly,monthly',
            'recurring_end_date' => 'nullable|date|after:scheduled_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $scheduledPost = $this->schedulingService->schedulePost(
                $request->all(),
                session('current_tenant_id'),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Post scheduled successfully',
                'data' => $scheduledPost->load('contentPost'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule post: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update scheduled post
     */
    public function update(Request $request, ScheduledPost $scheduledPost): JsonResponse
    {
        if ($scheduledPost->tenant_id !== session('current_tenant_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'sometimes|date|after:now',
            'timezone' => 'sometimes|string',
            'status' => 'sometimes|in:scheduled,paused,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $scheduledPost = $this->schedulingService->updateScheduledPost(
                $scheduledPost,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Scheduled post updated successfully',
                'data' => $scheduledPost->load('contentPost'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update scheduled post: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel scheduled post
     */
    public function destroy(ScheduledPost $scheduledPost): JsonResponse
    {
        if ($scheduledPost->tenant_id !== session('current_tenant_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->schedulingService->cancelScheduledPost($scheduledPost);

            return response()->json([
                'success' => true,
                'message' => 'Scheduled post cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel scheduled post: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk schedule posts
     */
    public function bulkSchedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'posts' => 'required|array|min:1',
            'posts.*.content_post_id' => 'required|exists:content_posts,id',
            'posts.*.platform' => 'required|in:facebook,instagram,twitter,linkedin,tiktok,snapchat,youtube,whatsapp',
            'posts.*.scheduled_at' => 'required|date|after:now',
            'timezone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $scheduledPosts = $this->schedulingService->bulkSchedulePosts(
                $request->posts,
                $request->timezone,
                session('current_tenant_id'),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => count($scheduledPosts) . ' posts scheduled successfully',
                'data' => $scheduledPosts,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk schedule posts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get calendar view of scheduled posts
     */
    public function calendar(Request $request): JsonResponse
    {
        $startDate = $request->start_date ?? now()->startOfMonth();
        $endDate = $request->end_date ?? now()->endOfMonth();

        $posts = ScheduledPost::with(['contentPost'])
            ->where('tenant_id', session('current_tenant_id'))
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->orderBy('scheduled_at', 'asc')
            ->get()
            ->groupBy(function ($post) {
                return $post->scheduled_at->format('Y-m-d');
            });

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Get scheduling analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $tenantId = session('current_tenant_id');
        
        $stats = [
            'total_scheduled' => ScheduledPost::where('tenant_id', $tenantId)
                ->where('status', 'scheduled')
                ->count(),
            'published_today' => ScheduledPost::where('tenant_id', $tenantId)
                ->where('status', 'published')
                ->whereDate('published_at', today())
                ->count(),
            'upcoming_24h' => ScheduledPost::where('tenant_id', $tenantId)
                ->where('status', 'scheduled')
                ->whereBetween('scheduled_at', [now(), now()->addDay()])
                ->count(),
            'by_platform' => ScheduledPost::where('tenant_id', $tenantId)
                ->selectRaw('platform, count(*) as count')
                ->groupBy('platform')
                ->pluck('count', 'platform'),
            'by_status' => ScheduledPost::where('tenant_id', $tenantId)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
