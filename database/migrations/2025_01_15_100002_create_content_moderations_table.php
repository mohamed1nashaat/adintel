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
        Schema::create('content_moderations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->nullable()->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected', 'needs_revision']);
            $table->text('feedback')->nullable();
            $table->json('suggested_changes')->nullable(); // Array of suggested modifications
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->timestamp('reviewed_at')->nullable();
            $table->json('compliance_flags')->nullable(); // Brand guidelines, legal compliance, etc.
            $table->decimal('ai_confidence_score', 5, 2)->nullable(); // AI moderation confidence
            $table->json('ai_suggestions')->nullable(); // AI-generated suggestions
            $table->boolean('requires_legal_review')->default(false);
            $table->boolean('requires_brand_review')->default(false);
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['content_post_id', 'status']);
            $table->index(['reviewer_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index(['tenant_id', 'reviewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_moderations');
    }
};
