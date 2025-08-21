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
        Schema::create('pitch_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('industry')->nullable(); // null means universal template
            $table->string('target_region')->default('gcc');
            $table->enum('ad_type', ['awareness', 'leads', 'sales', 'calls']);
            $table->text('template_content'); // Template with placeholders
            $table->json('required_data_points'); // What data is needed to fill template
            $table->json('default_values')->nullable(); // Default values for placeholders
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('usage_count')->default(0);
            $table->decimal('success_rate', 5, 2)->nullable(); // % of pitches that got approved
            $table->timestamps();
            
            $table->index(['tenant_id', 'industry', 'ad_type']);
            $table->index(['is_active', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pitch_templates');
    }
};
