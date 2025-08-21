<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = auth()->user();
        
        // Get tenant from header, query param, or user's default
        $tenantId = $request->header('X-Tenant-ID') 
                   ?? $request->query('tenant_id') 
                   ?? $user->default_tenant_id;

        if (!$tenantId) {
            return response()->json(['message' => 'No tenant specified'], 400);
        }

        // Verify user has access to this tenant
        $tenant = Tenant::find($tenantId);
        if (!$tenant || !$user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'Access denied to tenant'], 403);
        }

        // Set current tenant in session for global scopes
        session(['current_tenant_id' => $tenantId]);
        
        // Add tenant to request for easy access
        $request->merge(['current_tenant' => $tenant]);

        return $next($request);
    }
}
