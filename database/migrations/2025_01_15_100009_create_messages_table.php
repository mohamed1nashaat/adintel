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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->string('platform_message_id')->unique(); // External message ID
            $table->enum('type', ['text', 'image', 'video', 'audio', 'document', 'location', 'contact', 'sticker', 'emoji']);
            $table->enum('direction', ['inbound', 'outbound']); // Customer to us or us to customer
            $table->text('content')->nullable(); // Message text content
            $table->json('media_urls')->nullable(); // Array of media file URLs
            $table->json('metadata')->nullable(); // Platform-specific message data
            $table->string('sender_name')->nullable();
            $table->string('sender_id')->nullable(); // Platform-specific sender ID
            $table->foreignId('sent_by')->nullable()->constrained('users'); // Internal user who sent (for outbound)
            $table->timestamp('sent_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_automated')->default(false); // Auto-reply or chatbot
            $table->string('language', 10)->nullable(); // Detected/set language
            $table->text('translated_content')->nullable(); // Arabic translation for non-Arabic messages
            $table->decimal('sentiment_score', 3, 2)->nullable(); // -1 to 1 sentiment analysis
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->nullable();
            $table->json('detected_intents')->nullable(); // AI-detected customer intents
            $table->boolean('requires_response')->default(false);
            $table->timestamp('response_due_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'conversation_id']);
            $table->index(['conversation_id', 'sent_at']);
            $table->index(['direction', 'requires_response']);
            $table->index(['sent_by', 'sent_at']);
            $table->index(['platform_message_id']);
            $table->index(['language', 'sentiment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
