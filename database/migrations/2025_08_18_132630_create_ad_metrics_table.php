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
        Schema::create('ad_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->enum('platform', ['facebook', 'google', 'tiktok']);
            $table->foreignId('ad_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_campaign_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('objective', ['awareness', 'leads', 'sales', 'calls'])->nullable();
            
            // Core metrics
            $table->decimal('spend', 14, 2)->default(0);
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('reach')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->bigInteger('video_views')->default(0);
            $table->bigInteger('conversions')->default(0);
            $table->decimal('revenue', 14, 2)->default(0);
            $table->bigInteger('purchases')->default(0);
            $table->bigInteger('leads')->default(0);
            $table->bigInteger('calls')->default(0);
            $table->bigInteger('sessions')->default(0);
            $table->bigInteger('atc')->default(0); // add-to-cart
            
            // Additional metrics for future use
            $table->bigInteger('app_installs')->default(0);
            $table->bigInteger('page_views')->default(0);
            $table->decimal('cost_per_result', 10, 4)->default(0);
            
            $table->string('checksum', 64)->unique();
            $table->timestamps();
            
            // Composite indexes for performance
            $table->index(['tenant_id', 'date', 'platform', 'ad_account_id', 'ad_campaign_id'], 'metrics_composite_idx');
            $table->index(['tenant_id', 'objective', 'date'], 'metrics_objective_idx');
            $table->index(['tenant_id', 'platform', 'date'], 'metrics_platform_idx');
            $table->index(['date', 'platform'], 'metrics_date_platform_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_metrics');
    }
};
