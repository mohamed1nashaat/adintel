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
        Schema::create('pitches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('industry');
            $table->string('target_region')->default('gcc'); // 'gcc', 'ksa', 'uae', etc.
            $table->enum('ad_type', ['awareness', 'leads', 'sales', 'calls']);
            $table->json('target_audience'); // demographics, interests, behaviors
            $table->decimal('suggested_budget', 10, 2)->nullable();
            $table->json('keywords')->nullable(); // SEMrush keyword data
            $table->json('competitor_analysis')->nullable(); // SEMrush competitor data
            $table->json('market_insights')->nullable(); // SEMrush market data
            $table->json('content_suggestions')->nullable(); // Generated content ideas
            $table->json('platform_recommendations')->nullable(); // Which platforms to use
            $table->json('kpi_targets')->nullable(); // Expected performance metrics
            $table->text('pitch_content'); // Generated pitch text
            $table->enum('status', ['draft', 'ready', 'presented', 'approved', 'rejected']);
            $table->foreignId('template_id')->nullable()->constrained('pitch_templates');
            $table->json('semrush_data')->nullable(); // Raw SEMrush API response
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('presented_at')->nullable();
            $table->text('client_feedback')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'industry']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_by']);
            $table->index(['industry', 'target_region', 'ad_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pitches');
    }
};
