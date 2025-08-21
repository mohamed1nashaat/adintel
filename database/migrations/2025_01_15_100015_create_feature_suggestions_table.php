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
        Schema::create('feature_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['analytics', 'automation', 'integration', 'ui_ux', 'reporting', 'optimization']);
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['submitted', 'under_review', 'approved', 'in_development', 'completed', 'rejected']);
            $table->json('user_behavior_data')->nullable(); // Data that triggered the suggestion
            $table->integer('votes')->default(0);
            $table->decimal('impact_score', 5, 2)->nullable(); // Calculated impact score
            $table->text('business_justification')->nullable();
            $table->json('implementation_estimate')->nullable(); // Time, resources needed
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('implemented_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'category']);
            $table->index(['priority', 'status']);
            $table->index(['impact_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_suggestions');
    }
};
