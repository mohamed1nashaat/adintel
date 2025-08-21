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
        Schema::create('custom_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('criteria'); // Lead filtering criteria
            $table->json('date_range'); // Date range for lead selection
            $table->integer('lead_count')->default(0);
            $table->enum('status', ['building', 'ready', 'syncing', 'synced', 'error'])->default('building');
            $table->json('platform_sync_status')->nullable(); // Track sync status per platform
            $table->json('metadata')->nullable(); // Additional audience metadata
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_audiences');
    }
};
