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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('platform'); // 'facebook', 'instagram', 'twitter', 'whatsapp', 'telegram'
            $table->string('platform_conversation_id')->unique(); // External conversation ID
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable(); // Important for GCC/KSA market
            $table->string('customer_email')->nullable();
            $table->json('customer_metadata')->nullable(); // Platform-specific customer data
            $table->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users'); // Assigned agent
            $table->json('tags')->nullable(); // Conversation tags
            $table->string('language', 10)->default('ar'); // Default to Arabic for GCC
            $table->string('country_code', 3)->default('SA'); // Default to Saudi Arabia
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->boolean('is_read')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'platform']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'assigned_to']);
            $table->index(['platform', 'platform_conversation_id']);
            $table->index(['country_code', 'language']);
            $table->index(['last_message_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
