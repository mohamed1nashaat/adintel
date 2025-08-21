<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentModeration;
use App\Models\ContentPost;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentModerationController extends Controller
{
    public function __construct(
        private ContentModerationService $moderationService
    ) {}

    /**
     * Get moderation queue
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'priority',
            'requires_legal_review',
            'requires_brand_review',
            'per_page'
        ]);

        $moderations = $this->moderationService->getModerationQueue($filters);

        return response()->json([
            'data' => $moderations->items(),
            'meta' => [
                'current_page' => $moderations->currentPage(),
                'last_page' => $moderations->lastPage(),
                'per_page' => $moderations->perPage(),
                'total' => $moderations->total(),
            ]
        ]);
    }

    /**
     * Get a specific moderation record
     */
    public function show(ContentModeration $moderation): JsonResponse
    {
        return response()->json($moderation->load(['contentPost', 'reviewer']));
    }

    /**
     * Approve a content post
     */
    public function approve(Request $request, ContentModeration $moderation): JsonResponse
    {
        $validated = $request->validate([
            'feedback' => 'nullable|string',
        ]);

        if (!$moderation->isPending()) {
            return response()->json([
                'message' => 'This moderation has already been reviewed.'
            ], 422);
        }

        $moderation->approve($request->user(), $validated['feedback'] ?? null);

        return response()->json([
            'message' => 'Content approved successfully',
            'moderation' => $moderation->load(['contentPost', 'reviewer'])
        ]);
    }

    /**
     * Reject a content post
     */
    public function reject(Request $request, ContentModeration $moderation): JsonResponse
    {
        $validated = $request->validate([
            'feedback' => 'required|string',
            'suggested_changes' => 'nullable|array',
            'suggested_changes.*' => 'string',
        ]);

        if (!$moderation->isPending()) {
            return response()->json([
                'message' => 'This moderation has already been reviewed.'
            ], 422);
        }

        $moderation->reject(
            $request->user(),
            $validated['feedback'],
            $validated['suggested_changes'] ?? []
        );

        return response()->json([
            'message' => 'Content rejected successfully',
            'moderation' => $moderation->load(['contentPost', 'reviewer'])
        ]);
    }

    /**
     * Request revision for a content post
     */
    public function requestRevision(Request $request, ContentModeration $moderation): JsonResponse
    {
        $validated = $request->validate([
            'feedback' => 'required|string',
            'suggested_changes' => 'nullable|array',
            'suggested_changes.*' => 'string',
        ]);

        if (!$moderation->isPending()) {
            return response()->json([
                'message' => 'This moderation has already been reviewed.'
            ], 422);
        }

        $moderation->requestRevision(
            $request->user(),
            $validated['feedback'],
            $validated['suggested_changes'] ?? []
        );

        return response()->json([
            'message' => 'Revision requested successfully',
            'moderation' => $moderation->load(['contentPost', 'reviewer'])
        ]);
    }

    /**
     * Assign reviewer to moderation
     */
    public function assignReviewer(Request $request, ContentModeration $moderation): JsonResponse
    {
        $validated = $request->validate([
            'reviewer_id' => 'required|exists:users,id',
        ]);

        // Check if user has permission to assign reviewers (admin only)
        if (!$request->user()->isAdminForTenant($moderation->tenant)) {
            return response()->json([
                'message' => 'Insufficient permissions to assign reviewers.'
            ], 403);
        }

        $reviewer = \App\Models\User::find($validated['reviewer_id']);
        $this->moderationService->assignReviewer($moderation, $reviewer);

        return response()->json([
            'message' => 'Reviewer assigned successfully',
            'moderation' => $moderation->load(['contentPost', 'reviewer'])
        ]);
    }

    /**
     * Get moderation statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->moderationService->getModerationStatistics();
        return response()->json($stats);
    }

    /**
     * Get content suggestions for a post
     */
    public function suggestions(ContentPost $post): JsonResponse
    {
        $suggestions = $this->moderationService->generateContentSuggestions($post);
        
        return response()->json([
            'suggestions' => $suggestions,
            'post_id' => $post->id
        ]);
    }

    /**
     * Bulk approve multiple moderations
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'moderation_ids' => 'required|array|min:1',
            'moderation_ids.*' => 'exists:content_moderations,id',
            'feedback' => 'nullable|string',
        ]);

        $moderations = ContentModeration::whereIn('id', $validated['moderation_ids'])
            ->pending()
            ->get();

        $approved = 0;
        foreach ($moderations as $moderation) {
            $moderation->approve($request->user(), $validated['feedback'] ?? null);
            $approved++;
        }

        return response()->json([
            'message' => "Successfully approved {$approved} content posts",
            'approved_count' => $approved
        ]);
    }

    /**
     * Bulk reject multiple moderations
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'moderation_ids' => 'required|array|min:1',
            'moderation_ids.*' => 'exists:content_moderations,id',
            'feedback' => 'required|string',
            'suggested_changes' => 'nullable|array',
        ]);

        $moderations = ContentModeration::whereIn('id', $validated['moderation_ids'])
            ->pending()
            ->get();

        $rejected = 0;
        foreach ($moderations as $moderation) {
            $moderation->reject(
                $request->user(),
                $validated['feedback'],
                $validated['suggested_changes'] ?? []
            );
            $rejected++;
        }

        return response()->json([
            'message' => "Successfully rejected {$rejected} content posts",
            'rejected_count' => $rejected
        ]);
    }

    /**
     * Get moderation history for a post
     */
    public function history(ContentPost $post): JsonResponse
    {
        $history = ContentModeration::where('content_post_id', $post->id)
            ->with('reviewer')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'history' => $history,
            'post_id' => $post->id
        ]);
    }

    /**
     * Update moderation priority
     */
    public function updatePriority(Request $request, ContentModeration $moderation): JsonResponse
    {
        $validated = $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $moderation->update(['priority' => $validated['priority']]);

        return response()->json([
            'message' => 'Priority updated successfully',
            'moderation' => $moderation
        ]);
    }

    /**
     * Get my assigned moderations
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $query = ContentModeration::with(['contentPost'])
            ->byReviewer($request->user()->id)
            ->pending()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc');

        $moderations = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $moderations->items(),
            'meta' => [
                'current_page' => $moderations->currentPage(),
                'last_page' => $moderations->lastPage(),
                'per_page' => $moderations->perPage(),
                'total' => $moderations->total(),
            ]
        ]);
    }
}
