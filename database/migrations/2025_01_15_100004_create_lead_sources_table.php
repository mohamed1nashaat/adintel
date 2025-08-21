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
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['form', 'webhook', 'api', 'manual', 'import']);
            $table->string('webhook_url')->nullable(); // Generated webhook URL
            $table->string('webhook_secret')->nullable(); // Secret for webhook verification
            $table->json('form_fields')->nullable(); // Expected form fields configuration
            $table->json('mapping_config')->nullable(); // Field mapping configuration
            $table->string('google_sheet_id')->nullable();
            $table->string('google_sheet_name')->nullable();
            $table->boolean('auto_sync_sheets')->default(false);
            $table->json('notification_settings')->nullable(); // Email/Slack notifications
            $table->boolean('is_active')->default(true);
            $table->integer('leads_count')->default(0);
            $table->timestamp('last_lead_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_sources');
    }
};
