<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledPost;
use App\Models\ContentPost;
use App\Models\User;
use App\Services\SocialMediaPublishingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function __construct(
        private SocialMediaPublishingService $publishingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = ScheduledPost::with(['contentPost', 'creator', 'approver']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('platform')) {
            $query->forPlatform($request->platform);
        }

        if ($request->filled('creator_id')) {
            $query->byCreator($request->creator_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        if ($request->filled('needs_approval')) {
            if ($request->boolean('needs_approval')) {
                $query->pendingApproval();
            } else {
                $query->approved();
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('contentPost', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'scheduled_at');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $posts = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function show(ScheduledPost $scheduledPost): JsonResponse
    {
        $scheduledPost->load(['contentPost', 'creator', 'approver']);
        
        return response()->json(['data' => $scheduledPost]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_post_id' => 'required|exists:content_posts,id',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'string|in:facebook,instagram,twitter,linkedin,tiktok,youtube',
            'platform_configs' => 'nullable|array',
            'scheduled_at' => 'required|date|after:now',
            'preview_approved' => 'boolean',
        ]);

        $validated['tenant_id'] = session('current_tenant_id');
        $validated['created_by'] = $request->user()->id;

        $scheduledPost = ScheduledPost::create($validated);

        // Generate preview data
        $scheduledPost->generatePreview();

        $scheduledPost->load(['contentPost', 'creator']);

        return response()->json(['data' => $scheduledPost], 201);
    }

    public function update(Request $request, ScheduledPost $scheduledPost): JsonResponse
    {
        $validated = $request->validate([
            'platforms' => 'sometimes|array|min:1',
            'platforms.*' => 'string|in:facebook,instagram,twitter,linkedin,tiktok,youtube',
            'platform_configs' => 'nullable|array',
            'scheduled_at' => 'sometimes|date|after:now',
        ]);

        // Only allow updates if not yet published
        if ($scheduledPost->isPublished()) {
            return response()->json(['error' => 'Cannot update published posts'], 400);
        }

        $scheduledPost->update($validated);

        // Regenerate preview if platforms or configs changed
        if (isset($validated['platforms']) || isset($validated['platform_configs'])) {
            $scheduledPost->generatePreview();
        }

        $scheduledPost->load(['contentPost', 'creator', 'approver']);

        return response()->json(['data' => $scheduledPost]);
    }

    public function destroy(ScheduledPost $scheduledPost): JsonResponse
    {
        // Only allow deletion if not published
        if ($scheduledPost->isPublished()) {
            return response()->json(['error' => 'Cannot delete published posts'], 400);
        }

        $scheduledPost->delete();

        return response()->json(['message' => 'Scheduled post deleted successfully']);
    }

    public function preview(Request $request, ScheduledPost $scheduledPost): JsonResponse
    {
        $preview = $scheduledPost->generatePreview();
        
        return response()->json(['data' => $preview]);
    }

    public function approve(Request $request, ScheduledPost $scheduledPost): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        if ($scheduledPost->isApproved()) {
            return response()->json(['error' => 'Post is already approved'], 400);
        }

        $scheduledPost->approve($request->user(), $validated['notes'] ?? null);

        return response()->json([
            'message' => 'Post approved successfully',
            'data' => $scheduledPost->fresh(['contentPost', 'creator', 'approver']),
        ]);
    }

    public function publish(Request $request, ScheduledPost $scheduledPost): JsonResponse
    {
        if (!$scheduledPost->isScheduled()) {
            return response()->json(['error' => 'Post is not in scheduled status'], 400);
        }

        if ($scheduledPost->needsApproval()) {
            return response()->json(['error' => 'Post needs approval before publishing'], 400);
        }

        try {
            $results = $this->publishingService->publishScheduledPost($scheduledPost);
            
            return response()->json([
                'message' => 'Post published successfully',
                'data' => $scheduledPost->fresh(['contentPost', 'creator', 'approver']),
                'publish_results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to publish post',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(ScheduledPost $scheduledPost): JsonResponse
    {
        if ($scheduledPost->isPublished()) {
            return response()->json(['error' => 'Cannot cancel published posts'], 400);
        }

        $scheduledPost->markAsCancelled();

        return response()->json([
            'message' => 'Post cancelled successfully',
            'data' => $scheduledPost->fresh(['contentPost', 'creator', 'approver']),
        ]);
    }

    public function reschedule(Request $request, ScheduledPost $scheduledPost): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        if ($scheduledPost->isPublished()) {
            return response()->json(['error' => 'Cannot reschedule published posts'], 400);
        }

        $scheduledPost->reschedule(new \DateTime($validated['scheduled_at']));

        return response()->json([
            'message' => 'Post rescheduled successfully',
            'data' => $scheduledPost->fresh(['contentPost', 'creator', 'approver']),
        ]);
    }

    public function duplicate(Request $request, ScheduledPost $scheduledPost): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'platforms' => 'nullable|array',
            'platforms.*' => 'string|in:facebook,instagram,twitter,linkedin,tiktok,youtube',
        ]);

        $duplicate = $scheduledPost->duplicate($validated);
        $duplicate->generatePreview();

        return response()->json([
            'message' => 'Post duplicated successfully',
            'data' => $duplicate->load(['contentPost', 'creator']),
        ], 201);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:scheduled_posts,id',
            'action' => 'required|in:publish,cancel,approve,delete',
            'scheduled_at' => 'nullable|date|after:now', // For reschedule action
            'notes' => 'nullable|string', // For approve action
        ]);

        $posts = ScheduledPost::whereIn('id', $validated['post_ids'])->get();
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($posts as $post) {
            try {
                switch ($validated['action']) {
                    case 'publish':
                        if ($post->isScheduled() && !$post->needsApproval()) {
                            $this->publishingService->publishScheduledPost($post);
                            $results['success']++;
                        } else {
                            $results['errors'][] = "Post {$post->id}: Cannot publish";
                            $results['failed']++;
                        }
                        break;

                    case 'cancel':
                        if (!$post->isPublished()) {
                            $post->markAsCancelled();
                            $results['success']++;
                        } else {
                            $results['errors'][] = "Post {$post->id}: Cannot cancel published post";
                            $results['failed']++;
                        }
                        break;

                    case 'approve':
                        if (!$post->isApproved()) {
                            $post->approve($request->user(), $validated['notes'] ?? null);
                            $results['success']++;
                        } else {
                            $results['errors'][] = "Post {$post->id}: Already approved";
                            $results['failed']++;
                        }
                        break;

                    case 'delete':
                        if (!$post->isPublished()) {
                            $post->delete();
                            $results['success']++;
                        } else {
                            $results['errors'][] = "Post {$post->id}: Cannot delete published post";
                            $results['failed']++;
                        }
                        break;
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Post {$post->id}: {$e->getMessage()}";
                $results['failed']++;
            }
        }

        return response()->json([
            'message' => "Bulk action completed: {$results['success']} successful, {$results['failed']} failed",
            'results' => $results,
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total' => ScheduledPost::count(),
            'scheduled' => ScheduledPost::scheduled()->count(),
            'publishing' => ScheduledPost::publishing()->count(),
            'published' => ScheduledPost::published()->count(),
            'failed' => ScheduledPost::failed()->count(),
            'cancelled' => ScheduledPost::cancelled()->count(),
            'pending_approval' => ScheduledPost::pendingApproval()->count(),
            'due_now' => ScheduledPost::due()->count(),
            'needs_retry' => ScheduledPost::needsRetry()->count(),
            'today' => ScheduledPost::whereDate('scheduled_at', now())->count(),
            'this_week' => ScheduledPost::whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => ScheduledPost::whereMonth('scheduled_at', now()->month)->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'platforms' => 'nullable|array',
            'platforms.*' => 'string|in:facebook,instagram,twitter,linkedin,tiktok,youtube',
        ]);

        $query = ScheduledPost::with(['contentPost'])
            ->byDateRange($validated['start_date'], $validated['end_date']);

        if (!empty($validated['platforms'])) {
            $query->where(function ($q) use ($validated) {
                foreach ($validated['platforms'] as $platform) {
                    $q->orWhereJsonContains('platforms', $platform);
                }
            });
        }

        $posts = $query->get()->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->contentPost->title,
                'scheduled_at' => $post->scheduled_at->toISOString(),
                'status' => $post->status,
                'platforms' => $post->platforms,
                'creator' => $post->creator->name ?? 'Unknown',
            ];
        });

        return response()->json(['data' => $posts]);
    }

    public function retryFailed(): JsonResponse
    {
        try {
            $results = $this->publishingService->retryFailedPosts();
            
            return response()->json([
                'message' => "Retry completed: {$results['processed']} processed, {$results['failed']} failed",
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retry posts',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function generatePreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_post_id' => 'required|exists:content_posts,id',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'string|in:facebook,instagram,twitter,linkedin,tiktok,youtube',
            'platform_configs' => 'nullable|array',
        ]);

        $contentPost = ContentPost::find($validated['content_post_id']);
        $preview = $this->publishingService->generatePreview(
            $contentPost,
            $validated['platforms'],
            $validated['platform_configs'] ?? []
        );

        return response()->json(['data' => $preview]);
    }
}
