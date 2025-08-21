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
        Schema::create('performance_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('industry'); // e.g., 'ecommerce', 'saas', 'healthcare'
            $table->string('platform'); // 'facebook', 'google', 'instagram', etc.
            $table->string('objective'); // 'awareness', 'leads', 'sales', 'calls'
            $table->string('region')->default('global'); // 'global', 'us', 'eu', 'mena', etc.
            $table->string('audience_size'); // 'small', 'medium', 'large'
            $table->decimal('cpm_benchmark', 10, 4)->nullable(); // Cost per 1000 impressions
            $table->decimal('cpc_benchmark', 10, 4)->nullable(); // Cost per click
            $table->decimal('cpl_benchmark', 10, 4)->nullable(); // Cost per lead
            $table->decimal('cpa_benchmark', 10, 4)->nullable(); // Cost per acquisition
            $table->decimal('ctr_benchmark', 8, 4)->nullable(); // Click-through rate %
            $table->decimal('cvr_benchmark', 8, 4)->nullable(); // Conversion rate %
            $table->decimal('roas_benchmark', 8, 4)->nullable(); // Return on ad spend
            $table->decimal('frequency_benchmark', 8, 4)->nullable(); // Average frequency
            $table->decimal('reach_benchmark', 12, 2)->nullable(); // Average reach
            $table->json('additional_metrics')->nullable(); // Platform-specific metrics
            $table->enum('data_source', ['internal', 'industry_report', 'platform_data', 'third_party']);
            $table->date('period_start');
            $table->date('period_end');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'industry', 'platform']);
            $table->index(['industry', 'platform', 'objective']);
            $table->index(['region', 'industry']);
            $table->index(['is_active', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_benchmarks');
    }
};
