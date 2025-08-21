<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBranding extends Model
{
    protected $table = 'tenant_branding';

    protected $fillable = [
        'tenant_id',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'font_family',
        'company_name',
        'tagline',
        'custom_css',
        'theme_mode',
        'sidebar_color',
        'header_color',
        'button_style',
        'card_style',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the branding
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to get branding for a specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Get the logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? \Storage::url($this->logo_path) : null;
    }

    /**
     * Get the favicon URL
     */
    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon_path ? \Storage::url($this->favicon_path) : null;
    }

    /**
     * Get color palette as array
     */
    public function getColorPaletteAttribute(): array
    {
        return [
            'primary' => $this->primary_color ?? '#3B82F6',
            'secondary' => $this->secondary_color ?? '#6B7280',
            'accent' => $this->accent_color ?? '#10B981',
            'sidebar' => $this->sidebar_color ?? '#1F2937',
            'header' => $this->header_color ?? '#FFFFFF',
        ];
    }

    /**
     * Check if branding has custom logo
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo_path);
    }

    /**
     * Check if branding has custom favicon
     */
    public function hasFavicon(): bool
    {
        return !empty($this->favicon_path);
    }

    /**
     * Check if branding uses dark theme
     */
    public function isDarkTheme(): bool
    {
        return $this->theme_mode === 'dark';
    }

    /**
     * Get CSS variables for the branding
     */
    public function getCssVariables(): array
    {
        return [
            '--primary-color' => $this->primary_color ?? '#3B82F6',
            '--secondary-color' => $this->secondary_color ?? '#6B7280',
            '--accent-color' => $this->accent_color ?? '#10B981',
            '--sidebar-color' => $this->sidebar_color ?? '#1F2937',
            '--header-color' => $this->header_color ?? '#FFFFFF',
            '--font-family' => "'{$this->font_family}', sans-serif" ?? "'Inter', sans-serif",
        ];
    }

    /**
     * Generate CSS string for the branding
     */
    public function generateCSS(): string
    {
        $variables = $this->getCssVariables();
        $css = ":root {\n";
        
        foreach ($variables as $property => $value) {
            $css .= "  {$property}: {$value};\n";
        }
        
        $css .= "}\n";
        
        // Add button styles
        if ($this->button_style === 'rounded') {
            $css .= ".btn { border-radius: 0.5rem; }\n";
        } elseif ($this->button_style === 'square') {
            $css .= ".btn { border-radius: 0; }\n";
        } else {
            $css .= ".btn { border-radius: 9999px; }\n";
        }
        
        // Add card styles
        if ($this->card_style === 'shadow') {
            $css .= ".card { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }\n";
        } elseif ($this->card_style === 'border') {
            $css .= ".card { border: 1px solid #e5e7eb; box-shadow: none; }\n";
        } else {
            $css .= ".card { box-shadow: none; border: none; }\n";
        }
        
        // Add theme styles
        if ($this->isDarkTheme()) {
            $css .= "body { background-color: #111827; color: #f9fafb; }\n";
            $css .= ".card { background-color: #1f2937; }\n";
        }
        
        // Add custom CSS
        if (!empty($this->custom_css)) {
            $css .= "\n/* Custom CSS */\n";
            $css .= $this->custom_css;
        }
        
        return $css;
    }
}
