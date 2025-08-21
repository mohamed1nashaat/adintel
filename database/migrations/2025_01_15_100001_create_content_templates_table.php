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
        Schema::create('content_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('content_template'); // Template with placeholders like {{company_name}}
            $table->json('placeholders')->nullable(); // Array of available placeholders
            $table->json('default_hashtags')->nullable();
            $table->json('suggested_platforms')->nullable();
            $table->enum('category', ['promotional', 'educational', 'engagement', 'announcement', 'seasonal']);
            $table->enum('post_type', ['text', 'image', 'video', 'carousel', 'story']);
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['category', 'post_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_templates');
    }
};
