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
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->json('platforms'); // ['facebook', 'instagram', 'twitter', 'linkedin', etc.]
            $table->json('platform_configs')->nullable(); // Platform-specific settings
            $table->timestamp('scheduled_at');
            $table->enum('status', ['scheduled', 'publishing', 'published', 'failed', 'cancelled'])->default('scheduled');
            $table->json('publish_results')->nullable(); // Results from each platform
            $table->text('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->json('preview_data')->nullable(); // Generated preview data
            $table->boolean('preview_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['created_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
    }
};
