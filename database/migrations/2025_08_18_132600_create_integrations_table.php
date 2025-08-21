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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('platform', ['facebook', 'google', 'tiktok']);
            $table->json('app_config');
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'platform']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
