<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentPost;
use App\Models\ContentTemplate;
use App\Models\ContentModeration;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    public function __construct(
        private ContentModerationService $moderationService
    ) {}

    /**
     * Get all content posts
     */
    public function index(Request $request): JsonResponse
    {
        $query = ContentPost::with(['creator', 'template', 'moderation']);

        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('post_type')) {
            $query->byPostType($request->post_type);
        }

        if ($request->has('platform')) {
            $query->byPlatform($request->platform);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('content', 'like', "%{$request->search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $posts = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ]
        ]);
    }

    /**
     * Create a new content post
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'media_urls' => 'nullable|array',
            'media_urls.*' => 'url',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'in:facebook,instagram,twitter,linkedin,tiktok,youtube',
            'post_type' => 'required|in:text,image,video,carousel,story',
            'hashtags' => 'nullable|array',
            'hashtags.*' => 'string|max:50',
            'mentions' => 'nullable|array',
            'mentions.*' => 'string|max:50',
            'scheduled_at' => 'nullable|date|after:now',
            'template_id' => 'nullable|exists:content_templates,id',
            'platform_specific_content' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $validated['tenant_id'] = session('current_tenant_id');
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'draft';

        $post = ContentPost::create($validated);

        // Create moderation record
        $this->moderationService->createModerationRecord($post);

        return response()->json($post->load(['creator', 'template', 'moderation']), 201);
    }

    /**
     * Get a specific content post
     */
    public function show(ContentPost $post): JsonResponse
    {
        return response()->json($post->load(['creator', 'template', 'moderation']));
    }

    /**
     * Update a content post
     */
    public function update(Request $request, ContentPost $post): JsonResponse
    {
        // Check if post can be edited
        if (!$post->canBeEdited()) {
            return response()->json([
                'message' => 'This post cannot be edited in its current status.'
            ], 422);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'media_urls' => 'nullable|array',
            'media_urls.*' => 'url',
            'platforms' => 'sometimes|array|min:1',
            'platforms.*' => 'in:facebook,instagram,twitter,linkedin,tiktok,youtube',
            'post_type' => 'sometimes|in:text,image,video,carousel,story',
            'hashtags' => 'nullable|array',
            'hashtags.*' => 'string|max:50',
            'mentions' => 'nullable|array',
            'mentions.*' => 'string|max:50',
            'scheduled_at' => 'nullable|date|after:now',
            'platform_specific_content' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $post->update($validated);

        // Re-evaluate moderation if content changed
        if (isset($validated['content']) || isset($validated['title'])) {
            $this->moderationService->reevaluateModeration($post);
        }

        return response()->json($post->load(['creator', 'template', 'moderation']));
    }

    /**
     * Delete a content post
     */
    public function destroy(ContentPost $post): JsonResponse
    {
        if ($post->isPublished()) {
            return response()->json([
                'message' => 'Published posts cannot be deleted.'
            ], 422);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }

    /**
     * Submit post for review
     */
    public function submitForReview(ContentPost $post): JsonResponse
    {
        if ($post->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft posts can be submitted for review.'
            ], 422);
        }

        $post->update(['status' => 'pending_review']);

        // Update moderation status
        $post->moderation?->update(['status' => 'pending']);

        return response()->json([
            'message' => 'Post submitted for review successfully',
            'post' => $post->load(['creator', 'template', 'moderation'])
        ]);
    }

    /**
     * Schedule a post
     */
    public function schedule(Request $request, ContentPost $post): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        if ($post->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved posts can be scheduled.'
            ], 422);
        }

        $post->update([
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'scheduled'
        ]);

        return response()->json([
            'message' => 'Post scheduled successfully',
            'post' => $post->load(['creator', 'template', 'moderation'])
        ]);
    }

    /**
     * Duplicate a post
     */
    public function duplicate(ContentPost $post): JsonResponse
    {
        $newPost = $post->replicate();
        $newPost->title = $post->title . ' (Copy)';
        $newPost->status = 'draft';
        $newPost->scheduled_at = null;
        $newPost->published_at = null;
        $newPost->save();

        // Create moderation record for the new post
        $this->moderationService->createModerationRecord($newPost);

        return response()->json($newPost->load(['creator', 'template', 'moderation']), 201);
    }

    /**
     * Get content statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_posts' => ContentPost::count(),
            'draft_posts' => ContentPost::byStatus('draft')->count(),
            'pending_review' => ContentPost::byStatus('pending_review')->count(),
            'approved_posts' => ContentPost::byStatus('approved')->count(),
            'scheduled_posts' => ContentPost::byStatus('scheduled')->count(),
            'published_posts' => ContentPost::byStatus('published')->count(),
            'rejected_posts' => ContentPost::byStatus('rejected')->count(),
            'posts_by_type' => ContentPost::selectRaw('post_type, COUNT(*) as count')
                ->groupBy('post_type')
                ->pluck('count', 'post_type'),
            'posts_by_platform' => ContentPost::selectRaw('JSON_UNQUOTE(JSON_EXTRACT(platforms, "$[0]")) as platform, COUNT(*) as count')
                ->groupBy('platform')
                ->pluck('count', 'platform'),
        ];

        return response()->json($stats);
    }
}
