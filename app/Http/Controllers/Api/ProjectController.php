<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects for the current tenant
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = session('current_tenant_id');
        $user = Auth::user();

        $query = Project::forTenant($tenantId)
            ->with(['creator', 'users'])
            ->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('region')) {
            $query->byRegion($request->region);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $projects = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Add computed fields
        $projects->getCollection()->transform(function ($project) use ($user) {
            $project->user_role = $project->getUserRole($user);
            $project->progress_percentage = $project->getProgressPercentage();
            $project->budget_spent = $project->getBudgetSpent();
            $project->budget_remaining = $project->getBudgetRemaining();
            return $project;
        });

        return response()->json($projects);
    }

    /**
     * Store a newly created project
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = session('current_tenant_id');
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:campaign,brand,product,event,ongoing',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'target_audience' => 'nullable|array',
            'kpis' => 'nullable|array',
            'platforms' => 'nullable|array',
            'industry' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:50',
            'settings' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $project = Project::create([
                ...$validated,
                'tenant_id' => $tenantId,
                'created_by' => $user->id,
                'status' => 'active',
            ]);

            // Add creator as owner
            $project->users()->attach($user->id, [
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            DB::commit();

            $project->load(['creator', 'users']);
            $project->user_role = 'owner';

            return response()->json($project, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create project'], 500);
        }
    }

    /**
     * Display the specified project
     */
    public function show(Project $project): JsonResponse
    {
        $user = Auth::user();

        // Check if user has access to this project
        if (!$project->canUserView($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $project->load(['creator', 'users', 'contentPosts', 'leads', 'scheduledPosts']);
        
        // Add computed fields
        $project->user_role = $project->getUserRole($user);
        $project->progress_percentage = $project->getProgressPercentage();
        $project->budget_spent = $project->getBudgetSpent();
        $project->budget_remaining = $project->getBudgetRemaining();
        
        // Add KPI values
        $project->kpi_values = [];
        if ($project->kpis) {
            foreach ($project->kpis as $kpi) {
                $project->kpi_values[$kpi] = $project->getKpiValue($kpi);
            }
        }

        return response()->json($project);
    }

    /**
     * Update the specified project
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $user = Auth::user();

        // Check if user can edit this project
        if (!$project->canUserEdit($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,archived',
            'type' => 'sometimes|in:campaign,brand,product,event,ongoing',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'target_audience' => 'nullable|array',
            'kpis' => 'nullable|array',
            'platforms' => 'nullable|array',
            'industry' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:50',
            'settings' => 'nullable|array',
        ]);

        $project->update([
            ...$validated,
            'updated_by' => $user->id,
        ]);

        $project->load(['creator', 'users']);
        $project->user_role = $project->getUserRole($user);

        return response()->json($project);
    }

    /**
     * Remove the specified project
     */
    public function destroy(Project $project): JsonResponse
    {
        $user = Auth::user();

        // Only owners can delete projects
        if (!$project->isOwner($user)) {
            return response()->json(['error' => 'Only project owners can delete projects'], 403);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }

    /**
     * Add a user to the project
     */
    public function addUser(Request $request, Project $project): JsonResponse
    {
        $user = Auth::user();

        // Check if user can manage this project
        if (!in_array($project->getUserRole($user), ['owner', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,manager,editor,viewer',
            'permissions' => 'nullable|array',
        ]);

        $targetUser = User::find($validated['user_id']);

        // Check if user is already in the project
        if ($project->hasUser($targetUser)) {
            return response()->json(['error' => 'User is already in this project'], 400);
        }

        $project->users()->attach($targetUser->id, [
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? null,
            'status' => 'active',
            'invited_by' => $user->id,
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        return response()->json(['message' => 'User added to project successfully']);
    }

    /**
     * Update user role in project
     */
    public function updateUserRole(Request $request, Project $project, User $targetUser): JsonResponse
    {
        $user = Auth::user();

        // Check if user can manage this project
        if (!in_array($project->getUserRole($user), ['owner', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,manager,editor,viewer',
            'permissions' => 'nullable|array',
        ]);

        if (!$project->hasUser($targetUser)) {
            return response()->json(['error' => 'User is not in this project'], 400);
        }

        $project->users()->updateExistingPivot($targetUser->id, [
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? null,
        ]);

        return response()->json(['message' => 'User role updated successfully']);
    }

    /**
     * Remove user from project
     */
    public function removeUser(Project $project, User $targetUser): JsonResponse
    {
        $user = Auth::user();

        // Check if user can manage this project or is removing themselves
        $canManage = in_array($project->getUserRole($user), ['owner', 'admin']);
        $isRemovingSelf = $user->id === $targetUser->id;

        if (!$canManage && !$isRemovingSelf) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Prevent removing the last owner
        if ($project->getUserRole($targetUser) === 'owner') {
            $ownerCount = $project->users()->wherePivot('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return response()->json(['error' => 'Cannot remove the last owner'], 400);
            }
        }

        $project->users()->detach($targetUser->id);

        return response()->json(['message' => 'User removed from project successfully']);
    }

    /**
     * Get project statistics
     */
    public function statistics(Project $project): JsonResponse
    {
        $user = Auth::user();

        if (!$project->canUserView($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = [
            'content_posts' => $project->contentPosts()->count(),
            'published_posts' => $project->contentPosts()->where('status', 'published')->count(),
            'leads' => $project->leads()->count(),
            'scheduled_posts' => $project->scheduledPosts()->where('status', 'scheduled')->count(),
            'team_members' => $project->users()->count(),
            'progress_percentage' => $project->getProgressPercentage(),
            'budget_spent' => $project->getBudgetSpent(),
            'budget_remaining' => $project->getBudgetRemaining(),
        ];

        // Add KPI values
        if ($project->kpis) {
            foreach ($project->kpis as $kpi) {
                $stats['kpi_' . $kpi] = $project->getKpiValue($kpi);
            }
        }

        return response()->json($stats);
    }
}
