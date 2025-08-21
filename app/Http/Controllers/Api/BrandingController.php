<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantBranding;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    /**
     * Get tenant branding settings
     */
    public function index(): JsonResponse
    {
        $branding = TenantBranding::where('tenant_id', session('current_tenant_id'))->first();

        if (!$branding) {
            $branding = $this->getDefaultBranding();
        }

        return response()->json($branding);
    }

    /**
     * Update tenant branding settings
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'accent_color' => 'nullable|string|max:7',
            'font_family' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'website_url' => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'social_links' => 'nullable|array',
            'custom_css' => 'nullable|string',
        ]);

        $branding = TenantBranding::updateOrCreate(
            ['tenant_id' => session('current_tenant_id')],
            $request->only([
                'primary_color', 'secondary_color', 'accent_color', 'font_family',
                'company_name', 'tagline', 'website_url', 'contact_email',
                'contact_phone', 'address', 'social_links', 'custom_css'
            ])
        );

        return response()->json($branding);
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'type' => 'required|in:primary,secondary,favicon',
        ]);

        $file = $request->file('logo');
        $type = $request->type;
        
        // Generate unique filename
        $filename = $type . '_logo_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $path = $file->storeAs('branding/logos', $filename, 'public');
        
        // Update branding record
        $branding = TenantBranding::updateOrCreate(
            ['tenant_id' => session('current_tenant_id')],
            [$type . '_logo_url' => Storage::url($path)]
        );

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'logo_url' => Storage::url($path),
            'branding' => $branding,
        ]);
    }

    /**
     * Delete logo
     */
    public function deleteLogo(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:primary,secondary,favicon',
        ]);

        $branding = TenantBranding::where('tenant_id', session('current_tenant_id'))->first();
        
        if ($branding) {
            $logoField = $request->type . '_logo_url';
            $logoUrl = $branding->$logoField;
            
            if ($logoUrl) {
                // Delete file from storage
                $path = str_replace('/storage/', '', $logoUrl);
                Storage::disk('public')->delete($path);
                
                // Update database
                $branding->update([$logoField => null]);
            }
        }

        return response()->json(['message' => 'Logo deleted successfully']);
    }

    /**
     * Get branding templates
     */
    public function templates(): JsonResponse
    {
        $templates = [
            [
                'id' => 'modern',
                'name' => 'Modern',
                'description' => 'Clean and contemporary design',
                'colors' => [
                    'primary' => '#3B82F6',
                    'secondary' => '#64748B',
                    'accent' => '#10B981',
                ],
                'font_family' => 'Inter, sans-serif',
            ],
            [
                'id' => 'corporate',
                'name' => 'Corporate',
                'description' => 'Professional business look',
                'colors' => [
                    'primary' => '#1E40AF',
                    'secondary' => '#374151',
                    'accent' => '#059669',
                ],
                'font_family' => 'Roboto, sans-serif',
            ],
            [
                'id' => 'creative',
                'name' => 'Creative',
                'description' => 'Bold and vibrant design',
                'colors' => [
                    'primary' => '#7C3AED',
                    'secondary' => '#EC4899',
                    'accent' => '#F59E0B',
                ],
                'font_family' => 'Poppins, sans-serif',
            ],
            [
                'id' => 'minimal',
                'name' => 'Minimal',
                'description' => 'Simple and elegant',
                'colors' => [
                    'primary' => '#000000',
                    'secondary' => '#6B7280',
                    'accent' => '#EF4444',
                ],
                'font_family' => 'Helvetica, Arial, sans-serif',
            ],
        ];

        return response()->json(['data' => $templates]);
    }

    /**
     * Apply branding template
     */
    public function applyTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|string',
        ]);

        $templates = collect($this->templates()->getData()->data);
        $template = $templates->firstWhere('id', $request->template_id);

        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $branding = TenantBranding::updateOrCreate(
            ['tenant_id' => session('current_tenant_id')],
            [
                'primary_color' => $template->colors->primary,
                'secondary_color' => $template->colors->secondary,
                'accent_color' => $template->colors->accent,
                'font_family' => $template->font_family,
            ]
        );

        return response()->json([
            'message' => 'Template applied successfully',
            'branding' => $branding,
        ]);
    }

    /**
     * Generate CSS for current branding
     */
    public function generateCSS(): JsonResponse
    {
        $branding = TenantBranding::where('tenant_id', session('current_tenant_id'))->first();
        
        if (!$branding) {
            $branding = $this->getDefaultBranding();
        }

        $css = $this->generateBrandingCSS($branding);

        return response()->json([
            'css' => $css,
            'branding' => $branding,
        ]);
    }

    /**
     * Get default branding settings
     */
    private function getDefaultBranding(): array
    {
        return [
            'tenant_id' => session('current_tenant_id'),
            'primary_color' => '#3B82F6',
            'secondary_color' => '#64748B',
            'accent_color' => '#10B981',
            'font_family' => 'Inter, sans-serif',
            'company_name' => 'AdIntel',
            'tagline' => 'Advanced Ad Intelligence Platform',
            'primary_logo_url' => null,
            'secondary_logo_url' => null,
            'favicon_url' => null,
            'website_url' => null,
            'contact_email' => null,
            'contact_phone' => null,
            'address' => null,
            'social_links' => [],
            'custom_css' => null,
        ];
    }

    /**
     * Generate CSS from branding settings
     */
    private function generateBrandingCSS($branding): string
    {
        return "
:root {
    --primary-color: {$branding['primary_color']};
    --secondary-color: {$branding['secondary_color']};
    --accent-color: {$branding['accent_color']};
    --font-family: {$branding['font_family']};
}

.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background-color: color-mix(in srgb, var(--primary-color) 85%, black);
    border-color: color-mix(in srgb, var(--primary-color) 85%, black);
}

.text-primary {
    color: var(--primary-color) !important;
}

.bg-primary {
    background-color: var(--primary-color) !important;
}

.border-primary {
    border-color: var(--primary-color) !important;
}

.text-accent {
    color: var(--accent-color) !important;
}

.bg-accent {
    background-color: var(--accent-color) !important;
}

body {
    font-family: var(--font-family);
}

{$branding['custom_css']}
        ";
    }
}
