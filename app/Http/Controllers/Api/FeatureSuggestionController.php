<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureSuggestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeatureSuggestionController extends Controller
{
    /**
     * Display a listing of feature suggestions
     */
    public function index(Request $request): JsonResponse
    {
        $query = FeatureSuggestion::with(['suggestedBy'])
            ->where('tenant_id', session('current_tenant_id'));

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $suggestions = $query->orderBy('priority', 'desc')
                           ->orderBy('votes', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 15));

        return response()->json($suggestions);
    }

    /**
     * Store a newly created feature suggestion
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,critical',
            'expected_impact' => 'nullable|string',
            'use_case' => 'nullable|string',
        ]);

        $suggestion = FeatureSuggestion::create([
            'tenant_id' => session('current_tenant_id'),
            'suggested_by' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'priority' => $request->priority,
            'expected_impact' => $request->expected_impact,
            'use_case' => $request->use_case,
            'status' => 'submitted',
            'votes' => 1, // Auto-vote by creator
        ]);

        return response()->json($suggestion->load('suggestedBy'), 201);
    }

    /**
     * Display the specified feature suggestion
     */
    public function show(FeatureSuggestion $featureSuggestion): JsonResponse
    {
        $this->authorize('view', $featureSuggestion);

        return response()->json($featureSuggestion->load(['suggestedBy']));
    }

    /**
     * Update the specified feature suggestion
     */
    public function update(Request $request, FeatureSuggestion $featureSuggestion): JsonResponse
    {
        $this->authorize('update', $featureSuggestion);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category' => 'sometimes|string|max:100',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'status' => 'sometimes|in:submitted,under_review,approved,rejected,in_development,completed',
            'expected_impact' => 'nullable|string',
            'use_case' => 'nullable|string',
            'admin_notes' => 'nullable|string',
        ]);

        $featureSuggestion->update($request->only([
            'title', 'description', 'category', 'priority', 'status',
            'expected_impact', 'use_case', 'admin_notes'
        ]));

        return response()->json($featureSuggestion->load('suggestedBy'));
    }

    /**
     * Remove the specified feature suggestion
     */
    public function destroy(FeatureSuggestion $featureSuggestion): JsonResponse
    {
        $this->authorize('delete', $featureSuggestion);

        $featureSuggestion->delete();

        return response()->json(['message' => 'Feature suggestion deleted successfully']);
    }

    /**
     * Vote for a feature suggestion
     */
    public function vote(Request $request, FeatureSuggestion $featureSuggestion): JsonResponse
    {
        $this->authorize('view', $featureSuggestion);

        $request->validate([
            'vote' => 'required|in:up,down',
        ]);

        // Simple voting system - in production, you'd track individual votes
        if ($request->vote === 'up') {
            $featureSuggestion->increment('votes');
        } else {
            $featureSuggestion->decrement('votes');
        }

        return response()->json([
            'message' => 'Vote recorded successfully',
            'votes' => $featureSuggestion->votes,
        ]);
    }

    /**
     * Get feature suggestion statistics
     */
    public function stats(): JsonResponse
    {
        $tenantId = session('current_tenant_id');

        $stats = [
            'total' => FeatureSuggestion::where('tenant_id', $tenantId)->count(),
            'by_status' => FeatureSuggestion::where('tenant_id', $tenantId)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_category' => FeatureSuggestion::where('tenant_id', $tenantId)
                ->selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category'),
            'by_priority' => FeatureSuggestion::where('tenant_id', $tenantId)
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'top_voted' => FeatureSuggestion::where('tenant_id', $tenantId)
                ->orderBy('votes', 'desc')
                ->limit(5)
                ->get(['id', 'title', 'votes']),
        ];

        return response()->json($stats);
    }

    /**
     * Get trending feature suggestions
     */
    public function trending(): JsonResponse
    {
        $suggestions = FeatureSuggestion::with(['suggestedBy'])
            ->where('tenant_id', session('current_tenant_id'))
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('votes', 'desc')
            ->limit(10)
            ->get();

        return response()->json(['data' => $suggestions]);
    }
}
