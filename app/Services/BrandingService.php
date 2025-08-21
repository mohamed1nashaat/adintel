<?php

namespace App\Services;

use App\Models\TenantBranding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;

class BrandingService
{
    /**
     * Get tenant branding settings
     */
    public function getBranding(int $tenantId): array
    {
        try {
            $branding = TenantBranding::where('tenant_id', $tenantId)->first();
            
            if (!$branding) {
                return $this->getDefaultBranding();
            }
            
            return [
                'logo_url' => $branding->logo_path ? Storage::url($branding->logo_path) : null,
                'primary_color' => $branding->primary_color ?? '#3B82F6',
                'secondary_color' => $branding->secondary_color ?? '#6B7280',
                'accent_color' => $branding->accent_color ?? '#10B981',
                'font_family' => $branding->font_family ?? 'Inter',
                'company_name' => $branding->company_name ?? 'AdIntel',
                'tagline' => $branding->tagline ?? 'Advanced Advertising Intelligence',
                'favicon_url' => $branding->favicon_path ? Storage::url($branding->favicon_path) : null,
                'custom_css' => $branding->custom_css ?? '',
                'theme_mode' => $branding->theme_mode ?? 'light',
                'sidebar_color' => $branding->sidebar_color ?? '#1F2937',
                'header_color' => $branding->header_color ?? '#FFFFFF',
                'button_style' => $branding->button_style ?? 'rounded',
                'card_style' => $branding->card_style ?? 'shadow',
                'created_at' => $branding->created_at,
                'updated_at' => $branding->updated_at,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get branding settings', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            return $this->getDefaultBranding();
        }
    }

    /**
     * Update tenant branding settings
     */
    public function updateBranding(int $tenantId, array $data): array
    {
        try {
            $branding = TenantBranding::updateOrCreate(
                ['tenant_id' => $tenantId],
                [
                    'primary_color' => $data['primary_color'] ?? '#3B82F6',
                    'secondary_color' => $data['secondary_color'] ?? '#6B7280',
                    'accent_color' => $data['accent_color'] ?? '#10B981',
                    'font_family' => $data['font_family'] ?? 'Inter',
                    'company_name' => $data['company_name'] ?? 'AdIntel',
                    'tagline' => $data['tagline'] ?? 'Advanced Advertising Intelligence',
                    'custom_css' => $data['custom_css'] ?? '',
                    'theme_mode' => $data['theme_mode'] ?? 'light',
                    'sidebar_color' => $data['sidebar_color'] ?? '#1F2937',
                    'header_color' => $data['header_color'] ?? '#FFFFFF',
                    'button_style' => $data['button_style'] ?? 'rounded',
                    'card_style' => $data['card_style'] ?? 'shadow',
                ]
            );

            Log::info('Branding settings updated', [
                'tenant_id' => $tenantId,
                'branding_id' => $branding->id
            ]);

            return $this->getBranding($tenantId);
        } catch (\Exception $e) {
            Log::error('Failed to update branding settings', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            throw $e;
        }
    }

    /**
     * Upload logo
     */
    public function uploadLogo(int $tenantId, UploadedFile $file, string $type = 'logo'): array
    {
        try {
            $this->validateImageFile($file);
            
            $branding = TenantBranding::firstOrCreate(['tenant_id' => $tenantId]);
            
            // Delete old logo if exists
            if ($type === 'logo' && $branding->logo_path) {
                Storage::delete($branding->logo_path);
            } elseif ($type === 'favicon' && $branding->favicon_path) {
                Storage::delete($branding->favicon_path);
            }
            
            // Process and store new logo
            $path = $this->processAndStoreLogo($file, $tenantId, $type);
            
            // Update branding record
            if ($type === 'logo') {
                $branding->logo_path = $path;
            } else {
                $branding->favicon_path = $path;
            }
            $branding->save();

            Log::info('Logo uploaded successfully', [
                'tenant_id' => $tenantId,
                'type' => $type,
                'path' => $path
            ]);

            return [
                'success' => true,
                'url' => Storage::url($path),
                'path' => $path,
                'type' => $type,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to upload logo', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'type' => $type
            ]);
            throw $e;
        }
    }

    /**
     * Delete logo
     */
    public function deleteLogo(int $tenantId, string $type = 'logo'): bool
    {
        try {
            $branding = TenantBranding::where('tenant_id', $tenantId)->first();
            
            if (!$branding) {
                return false;
            }
            
            if ($type === 'logo' && $branding->logo_path) {
                Storage::delete($branding->logo_path);
                $branding->logo_path = null;
            } elseif ($type === 'favicon' && $branding->favicon_path) {
                Storage::delete($branding->favicon_path);
                $branding->favicon_path = null;
            }
            
            $branding->save();

            Log::info('Logo deleted successfully', [
                'tenant_id' => $tenantId,
                'type' => $type
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete logo', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'type' => $type
            ]);
            throw $e;
        }
    }

    /**
     * Generate CSS for tenant branding
     */
    public function generateBrandingCSS(int $tenantId): string
    {
        try {
            $branding = $this->getBranding($tenantId);
            
            $css = ":root {\n";
            $css .= "  --primary-color: {$branding['primary_color']};\n";
            $css .= "  --secondary-color: {$branding['secondary_color']};\n";
            $css .= "  --accent-color: {$branding['accent_color']};\n";
            $css .= "  --sidebar-color: {$branding['sidebar_color']};\n";
            $css .= "  --header-color: {$branding['header_color']};\n";
            $css .= "  --font-family: '{$branding['font_family']}', sans-serif;\n";
            $css .= "}\n\n";
            
            // Button styles
            if ($branding['button_style'] === 'rounded') {
                $css .= ".btn { border-radius: 0.5rem; }\n";
            } elseif ($branding['button_style'] === 'square') {
                $css .= ".btn { border-radius: 0; }\n";
            } else {
                $css .= ".btn { border-radius: 9999px; }\n";
            }
            
            // Card styles
            if ($branding['card_style'] === 'shadow') {
                $css .= ".card { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }\n";
            } elseif ($branding['card_style'] === 'border') {
                $css .= ".card { border: 1px solid #e5e7eb; box-shadow: none; }\n";
            } else {
                $css .= ".card { box-shadow: none; border: none; }\n";
            }
            
            // Theme mode
            if ($branding['theme_mode'] === 'dark') {
                $css .= "body { background-color: #111827; color: #f9fafb; }\n";
                $css .= ".card { background-color: #1f2937; }\n";
            }
            
            // Custom CSS
            if (!empty($branding['custom_css'])) {
                $css .= "\n/* Custom CSS */\n";
                $css .= $branding['custom_css'];
            }
            
            return $css;
        } catch (\Exception $e) {
            Log::error('Failed to generate branding CSS', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            return '';
        }
    }

    /**
     * Get branding preview
     */
    public function getBrandingPreview(int $tenantId, array $previewData = []): array
    {
        try {
            $currentBranding = $this->getBranding($tenantId);
            $previewBranding = array_merge($currentBranding, $previewData);
            
            return [
                'branding' => $previewBranding,
                'css' => $this->generatePreviewCSS($previewBranding),
                'components' => $this->generateComponentPreviews($previewBranding),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate branding preview', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            throw $e;
        }
    }

    /**
     * Reset branding to defaults
     */
    public function resetBranding(int $tenantId): array
    {
        try {
            $branding = TenantBranding::where('tenant_id', $tenantId)->first();
            
            if ($branding) {
                // Delete logo files
                if ($branding->logo_path) {
                    Storage::delete($branding->logo_path);
                }
                if ($branding->favicon_path) {
                    Storage::delete($branding->favicon_path);
                }
                
                // Delete branding record
                $branding->delete();
            }

            Log::info('Branding reset to defaults', [
                'tenant_id' => $tenantId
            ]);

            return $this->getDefaultBranding();
        } catch (\Exception $e) {
            Log::error('Failed to reset branding', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            throw $e;
        }
    }

    /**
     * Get available fonts
     */
    public function getAvailableFonts(): array
    {
        return [
            'Inter' => 'Inter',
            'Roboto' => 'Roboto',
            'Open Sans' => 'Open Sans',
            'Lato' => 'Lato',
            'Montserrat' => 'Montserrat',
            'Source Sans Pro' => 'Source Sans Pro',
            'Raleway' => 'Raleway',
            'Nunito' => 'Nunito',
            'Poppins' => 'Poppins',
            'Playfair Display' => 'Playfair Display',
        ];
    }

    /**
     * Get color palette suggestions
     */
    public function getColorPalettes(): array
    {
        return [
            'blue' => [
                'primary' => '#3B82F6',
                'secondary' => '#6B7280',
                'accent' => '#10B981',
                'name' => 'Professional Blue'
            ],
            'purple' => [
                'primary' => '#8B5CF6',
                'secondary' => '#6B7280',
                'accent' => '#F59E0B',
                'name' => 'Creative Purple'
            ],
            'green' => [
                'primary' => '#10B981',
                'secondary' => '#6B7280',
                'accent' => '#3B82F6',
                'name' => 'Growth Green'
            ],
            'red' => [
                'primary' => '#EF4444',
                'secondary' => '#6B7280',
                'accent' => '#F59E0B',
                'name' => 'Bold Red'
            ],
            'orange' => [
                'primary' => '#F97316',
                'secondary' => '#6B7280',
                'accent' => '#8B5CF6',
                'name' => 'Energetic Orange'
            ],
        ];
    }

    /**
     * Private helper methods
     */
    private function getDefaultBranding(): array
    {
        return [
            'logo_url' => null,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#6B7280',
            'accent_color' => '#10B981',
            'font_family' => 'Inter',
            'company_name' => 'AdIntel',
            'tagline' => 'Advanced Advertising Intelligence',
            'favicon_url' => null,
            'custom_css' => '',
            'theme_mode' => 'light',
            'sidebar_color' => '#1F2937',
            'header_color' => '#FFFFFF',
            'button_style' => 'rounded',
            'card_style' => 'shadow',
            'created_at' => null,
            'updated_at' => null,
        ];
    }

    private function validateImageFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }
        
        if ($file->getSize() > 5 * 1024 * 1024) { // 5MB limit
            throw new \InvalidArgumentException('File size too large (max 5MB)');
        }
        
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid file type. Only JPEG, PNG, GIF, and SVG files are allowed');
        }
    }

    private function processAndStoreLogo(UploadedFile $file, int $tenantId, string $type): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = $type . '_' . $tenantId . '_' . time() . '.' . $extension;
        $directory = 'branding/' . $tenantId;
        $path = $directory . '/' . $filename;
        
        // Create directory if it doesn't exist
        Storage::makeDirectory($directory);
        
        // For SVG files, store directly
        if ($file->getMimeType() === 'image/svg+xml') {
            Storage::put($path, file_get_contents($file->getRealPath()));
            return $path;
        }
        
        // Process other image types
        $image = Image::make($file->getRealPath());
        
        if ($type === 'logo') {
            // Resize logo to max 300x100 while maintaining aspect ratio
            $image->resize(300, 100, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        } else {
            // Resize favicon to 32x32
            $image->resize(32, 32);
        }
        
        // Save processed image
        Storage::put($path, $image->encode($extension, 90)->__toString());
        
        return $path;
    }

    private function generatePreviewCSS(array $branding): string
    {
        $css = ":root {\n";
        $css .= "  --primary-color: {$branding['primary_color']};\n";
        $css .= "  --secondary-color: {$branding['secondary_color']};\n";
        $css .= "  --accent-color: {$branding['accent_color']};\n";
        $css .= "}\n";
        
        return $css;
    }

    private function generateComponentPreviews(array $branding): array
    {
        return [
            'button_primary' => [
                'style' => "background-color: {$branding['primary_color']}; color: white; padding: 8px 16px; border-radius: " . ($branding['button_style'] === 'rounded' ? '0.5rem' : ($branding['button_style'] === 'square' ? '0' : '9999px')) . ";",
                'text' => 'Primary Button'
            ],
            'button_secondary' => [
                'style' => "background-color: {$branding['secondary_color']}; color: white; padding: 8px 16px; border-radius: " . ($branding['button_style'] === 'rounded' ? '0.5rem' : ($branding['button_style'] === 'square' ? '0' : '9999px')) . ";",
                'text' => 'Secondary Button'
            ],
            'card' => [
                'style' => "background-color: white; padding: 16px; " . ($branding['card_style'] === 'shadow' ? 'box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);' : ($branding['card_style'] === 'border' ? 'border: 1px solid #e5e7eb;' : '')),
                'content' => 'Sample card content with your branding'
            ],
        ];
    }
}
