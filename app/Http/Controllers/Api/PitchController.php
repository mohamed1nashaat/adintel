<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pitch;
use App\Models\PitchTemplate;
use App\Services\SEMrushService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PitchController extends Controller
{
    protected $semrushService;

    public function __construct(SEMrushService $semrushService)
    {
        $this->semrushService = $semrushService;
    }

    /**
     * Display a listing of pitches
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pitch::with(['template', 'createdBy'])
            ->where('tenant_id', session('current_tenant_id'));

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by industry
        if ($request->has('industry')) {
            $query->where('industry', $request->industry);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('client_name', 'like', '%' . $request->search . '%');
            });
        }

        $pitches = $query->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 15));

        return response()->json($pitches);
    }

    /**
     * Store a newly created pitch
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'industry' => 'required|string|max:100',
            'ad_type' => 'required|string|max:100',
            'budget_range' => 'nullable|string|max:100',
            'target_audience' => 'nullable|array',
            'objectives' => 'nullable|array',
            'template_id' => 'nullable|exists:pitch_templates,id',
            'custom_requirements' => 'nullable|string',
        ]);

        $pitch = Pitch::create([
            'tenant_id' => session('current_tenant_id'),
            'created_by' => $request->user()->id,
            'title' => $request->title,
            'client_name' => $request->client_name,
            'industry' => $request->industry,
            'ad_type' => $request->ad_type,
            'budget_range' => $request->budget_range,
            'target_audience' => $request->target_audience,
            'objectives' => $request->objectives,
            'template_id' => $request->template_id,
            'custom_requirements' => $request->custom_requirements,
            'status' => 'draft',
        ]);

        // Generate pitch content using SEMrush data
        try {
            $pitchContent = $this->semrushService->generatePitch([
                'industry' => $request->industry,
                'ad_type' => $request->ad_type,
                'target_audience' => $request->target_audience,
                'objectives' => $request->objectives,
                'budget_range' => $request->budget_range,
                'custom_requirements' => $request->custom_requirements,
            ]);

            $pitch->update([
                'content' => $pitchContent,
                'status' => 'generated',
            ]);
        } catch (\Exception $e) {
            // If SEMrush fails, use template-based generation
            $pitch->update([
                'content' => $this->generateBasicPitch($request->all()),
                'status' => 'generated',
            ]);
        }

        return response()->json($pitch->load(['template', 'createdBy']), 201);
    }

    /**
     * Display the specified pitch
     */
    public function show(Pitch $pitch): JsonResponse
    {
        $this->authorize('view', $pitch);

        return response()->json($pitch->load(['template', 'createdBy']));
    }

    /**
     * Update the specified pitch
     */
    public function update(Request $request, Pitch $pitch): JsonResponse
    {
        $this->authorize('update', $pitch);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'client_name' => 'sometimes|string|max:255',
            'industry' => 'sometimes|string|max:100',
            'ad_type' => 'sometimes|string|max:100',
            'budget_range' => 'nullable|string|max:100',
            'target_audience' => 'nullable|array',
            'objectives' => 'nullable|array',
            'content' => 'nullable|string',
            'status' => 'sometimes|in:draft,generated,reviewed,approved,sent',
            'custom_requirements' => 'nullable|string',
        ]);

        $pitch->update($request->only([
            'title', 'client_name', 'industry', 'ad_type', 'budget_range',
            'target_audience', 'objectives', 'content', 'status', 'custom_requirements'
        ]));

        return response()->json($pitch->load(['template', 'createdBy']));
    }

    /**
     * Remove the specified pitch
     */
    public function destroy(Pitch $pitch): JsonResponse
    {
        $this->authorize('delete', $pitch);

        $pitch->delete();

        return response()->json(['message' => 'Pitch deleted successfully']);
    }

    /**
     * Get pitch templates
     */
    public function templates(Request $request): JsonResponse
    {
        $query = PitchTemplate::where('tenant_id', session('current_tenant_id'))
                             ->orWhere('is_global', true);

        if ($request->has('industry')) {
            $query->where('industry', $request->industry);
        }

        if ($request->has('ad_type')) {
            $query->where('ad_type', $request->ad_type);
        }

        $templates = $query->orderBy('name')->get();

        return response()->json(['data' => $templates]);
    }

    /**
     * Generate pitch using SEMrush data
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => 'required|string|max:100',
            'ad_type' => 'required|string|max:100',
            'target_audience' => 'nullable|array',
            'objectives' => 'nullable|array',
            'budget_range' => 'nullable|string|max:100',
            'custom_requirements' => 'nullable|string',
        ]);

        try {
            $pitchContent = $this->semrushService->generatePitch($request->all());

            return response()->json([
                'content' => $pitchContent,
                'generated_at' => now(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'content' => $this->generateBasicPitch($request->all()),
                'generated_at' => now(),
                'note' => 'Generated using basic template due to SEMrush API unavailability',
            ]);
        }
    }

    /**
     * Get industry insights for pitch generation
     */
    public function insights(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => 'required|string|max:100',
            'region' => 'nullable|string|max:50',
        ]);

        try {
            $insights = $this->semrushService->getIndustryInsights(
                $request->industry,
                $request->get('region', 'gcc')
            );

            return response()->json($insights);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch industry insights',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate basic pitch content when SEMrush is unavailable
     */
    private function generateBasicPitch(array $data): string
    {
        $industry = $data['industry'] ?? 'your industry';
        $adType = $data['ad_type'] ?? 'digital advertising';
        $budget = $data['budget_range'] ?? 'your budget';

        return "
# Digital Marketing Proposal for {$data['client_name']}

## Executive Summary
We propose a comprehensive {$adType} campaign tailored for the {$industry} sector in the GCC region.

## Campaign Objectives
- Increase brand awareness and market presence
- Drive qualified leads and conversions
- Optimize return on advertising spend (ROAS)

## Recommended Strategy
### Platform Mix
- Facebook & Instagram Ads (40%)
- Google Ads (35%)
- LinkedIn Ads (15%)
- TikTok Ads (10%)

### Budget Allocation
Total Budget: {$budget}
- Creative Development: 20%
- Media Spend: 70%
- Management & Optimization: 10%

## GCC Market Insights
- High mobile penetration (95%+)
- Strong social media engagement
- Growing e-commerce adoption
- Preference for Arabic content

## Expected Results
- 25-40% increase in brand awareness
- 15-25% improvement in lead generation
- 3-5x return on ad spend (ROAS)

## Next Steps
1. Campaign setup and creative development
2. Audience research and targeting
3. Launch and optimization
4. Performance monitoring and reporting

Let's discuss how we can customize this strategy for your specific needs.
        ";
    }
}
