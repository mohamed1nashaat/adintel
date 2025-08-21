<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load(['defaultTenant', 'tenants']),
            'token' => $token,
            'tenants' => $user->tenants->map(function ($tenant) use ($user) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'role' => $user->getRoleForTenant($tenant),
                ];
            }),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $currentTenant = $request->current_tenant ?? $user->defaultTenant;

        return response()->json([
            'user' => $user,
            'current_tenant' => $currentTenant,
            'role' => $currentTenant ? $user->getRoleForTenant($currentTenant) : null,
            'tenants' => $user->tenants->map(function ($tenant) use ($user) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'role' => $user->getRoleForTenant($tenant),
                ];
            }),
        ]);
    }
}
