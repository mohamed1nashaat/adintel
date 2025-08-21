<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_branding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('logo_url')->nullable();
            $table->string('logo_dark_url')->nullable(); // For dark mode
            $table->string('favicon_url')->nullable();
            $table->string('primary_color')->default('#3B82F6'); // Hex color
            $table->string('secondary_color')->default('#6B7280');
            $table->string('accent_color')->default('#10B981');
            $table->string('background_color')->default('#FFFFFF');
            $table->string('text_color')->default('#1F2937');
            $table->json('custom_css')->nullable(); // Custom CSS overrides
            $table->string('company_name')->nullable();
            $table->text('company_description')->nullable();
            $table->string('website_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->json('social_links')->nullable(); // Social media links
            $table->string('report_header_text')->nullable();
            $table->string('report_footer_text')->nullable();
            $table->boolean('show_powered_by')->default(true);
            $table->json('email_template_settings')->nullable();
            $table->timestamps();
            
            $table->unique('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_branding');
    }
};
