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
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_account_id')->constrained()->onDelete('cascade');
            $table->string('external_campaign_id');
            $table->string('name');
            $table->enum('objective', ['awareness', 'leads', 'sales', 'calls'])->nullable();
            $table->enum('status', ['active', 'paused', 'archived'])->default('active');
            $table->json('campaign_config')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'ad_account_id']);
            $table->index(['tenant_id', 'objective']);
            $table->index(['tenant_id', 'status']);
            $table->unique(['ad_account_id', 'external_campaign_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
