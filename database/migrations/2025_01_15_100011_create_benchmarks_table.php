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
        Schema::create('benchmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('industry'); // e.g., 'retail', 'healthcare', 'finance'
            $table->string('region')->default('gcc'); // 'gcc', 'ksa', 'uae', 'global'
            $table->string('platform'); // 'facebook', 'google', 'snapchat', 'tiktok'
            $table->enum('objective', ['awareness', 'leads', 'sales', 'calls']);
            $table->string('metric_name'); // 'cpm', 'ctr', 'cpl', 'roas', etc.
            $table->decimal('benchmark_value', 10, 4);
            $table->decimal('percentile_25', 10, 4)->nullable();
            $table->decimal('percentile_50', 10, 4)->nullable(); // median
            $table->decimal('percentile_75', 10, 4)->nullable();
            $table->decimal('percentile_90', 10, 4)->nullable();
            $table->integer('sample_size')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->json('additional_filters')->nullable(); // age, gender, device, etc.
            $table->enum('data_source', ['internal', 'industry_report', 'competitor_analysis', 'gcc_market_data']);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['tenant_id', 'industry', 'region']);
            $table->index(['platform', 'objective', 'metric_name']);
            $table->index(['industry', 'region', 'platform']);
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benchmarks');
    }
};
