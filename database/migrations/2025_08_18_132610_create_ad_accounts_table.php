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
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('integration_id')->constrained()->onDelete('cascade');
            $table->string('external_account_id');
            $table->string('account_name');
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->json('account_config')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'integration_id']);
            $table->index(['tenant_id', 'status']);
            $table->unique(['integration_id', 'external_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
