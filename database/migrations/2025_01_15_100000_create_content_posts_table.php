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
        Schema::create('content_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->text('content');
            $table->json('media_urls')->nullable(); // Array of image/video URLs
            $table->json('platforms')->nullable(); // ['facebook', 'instagram', 'twitter', etc.]
            $table->enum('post_type', ['text', 'image', 'video', 'carousel', 'story']);
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'published', 'scheduled']);
            $table->json('hashtags')->nullable(); // Array of hashtags
            $table->json('mentions')->nullable(); // Array of mentions
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->json('platform_specific_content')->nullable(); // Different content per platform
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_by']);
            $table->index(['tenant_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_posts');
    }
};
