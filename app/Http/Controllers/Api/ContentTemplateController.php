<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentTemplateController extends Controller
{
    /**
     * Get all content templates
     */
    public function index(Request $request): JsonResponse
    {
        $query = ContentTemplate::with('creator');

        // Apply filters
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('post_type')) {
            $query->byPostType($request->post_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhere('content_template', 'like', "%{$request->search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if ($sortBy === 'popular') {
            $query->popular();
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $templates = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $templates->items(),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ]
        ]);
    }

    /**
     * Create a new content template
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_template' => 'required|string',
            'placeholders' => 'nullable|array',
            'placeholders.*' => 'string|max:100',
            'default_hashtags' => 'nullable|array',
            'default_hashtags.*' => 'string|max:50',
            'suggested_platforms' => 'nullable|array',
            'suggested_platforms.*' => 'in:facebook,instagram,twitter,linkedin,tiktok,youtube',
            'category' => 'required|in:promotional,educational,engagement,announcement,seasonal',
            'post_type' => 'required|in:text,image,video,carousel,story',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = session('current_tenant_id');
        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $template = ContentTemplate::create($validated);

        return response()->json($template->load('creator'), 201);
    }

    /**
     * Get a specific content template
     */
    public function show(ContentTemplate $template): JsonResponse
    {
        return response()->json($template->load(['creator', 'contentPosts']));
    }

    /**
     * Update a content template
     */
    public function update(Request $request, ContentTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content_template' => 'sometimes|string',
            'placeholders' => 'nullable|array',
            'placeholders.*' => 'string|max:100',
            'default_hashtags' => 'nullable|array',
            'default_hashtags.*' => 'string|max:50',
            'suggested_platforms' => 'nullable|array',
            'suggested_platforms.*' => 'in:facebook,instagram,twitter,linkedin,tiktok,youtube',
            'category' => 'sometimes|in:promotional,educational,engagement,announcement,seasonal',
            'post_type' => 'sometimes|in:text,image,video,carousel,story',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return response()->json($template->load('creator'));
    }

    /**
     * Delete a content template
     */
    public function destroy(ContentTemplate $template): JsonResponse
    {
        // Check if template is being used
        if ($template->contentPosts()->exists()) {
            return response()->json([
                'message' => 'Cannot delete template that is being used by posts.'
            ], 422);
        }

        $template->delete();

        return response()->json(['message' => 'Template deleted successfully']);
    }

    /**
     * Duplicate a template
     */
    public function duplicate(ContentTemplate $template): JsonResponse
    {
        $newTemplate = $template->duplicate([
            'created_by' => request()->user()->id
        ]);

        return response()->json($newTemplate->load('creator'), 201);
    }

    /**
     * Generate content from template
     */
    public function generateContent(Request $request, ContentTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        $content = $template->generateContent($validated['data']);

        // Increment usage count
        $template->incrementUsage();

        return response()->json([
            'content' => $content,
            'hashtags' => $template->getDefaultHashtagsString(),
            'suggested_platforms' => $template->suggested_platforms,
        ]);
    }

    /**
     * Get template categories
     */
    public function categories(): JsonResponse
    {
        $categories = [
            'promotional' => 'Promotional',
            'educational' => 'Educational',
            'engagement' => 'Engagement',
            'announcement' => 'Announcement',
            'seasonal' => 'Seasonal',
        ];

        return response()->json($categories);
    }

    /**
     * Get template statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_templates' => ContentTemplate::count(),
            'active_templates' => ContentTemplate::active()->count(),
            'templates_by_category' => ContentTemplate::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category'),
            'templates_by_type' => ContentTemplate::selectRaw('post_type, COUNT(*) as count')
                ->groupBy('post_type')
                ->pluck('count', 'post_type'),
            'most_used' => ContentTemplate::popular()->limit(5)->get(['id', 'name', 'usage_count']),
        ];

        return response()->json($stats);
    }

    /**
     * Get available placeholders for a template
     */
    public function placeholders(ContentTemplate $template): JsonResponse
    {
        return response()->json([
            'placeholders' => $template->getAvailablePlaceholders(),
            'common_placeholders' => [
                'company_name' => 'Company Name',
                'product_name' => 'Product Name',
                'customer_name' => 'Customer Name',
                'date' => 'Date',
                'price' => 'Price',
                'discount' => 'Discount',
                'location' => 'Location',
                'event_name' => 'Event Name',
                'website_url' => 'Website URL',
                'phone_number' => 'Phone Number',
            ]
        ]);
    }
}
