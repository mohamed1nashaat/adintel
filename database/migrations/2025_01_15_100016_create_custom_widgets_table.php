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
        Schema::create('custom_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['chart', 'kpi', 'table', 'text', 'image', 'iframe', 'custom']);
            $table->json('configuration'); // Widget-specific settings
            $table->json('data_source'); // Where to get data from
            $table->json('styling')->nullable(); // Custom CSS/styling
            $table->integer('width')->default(6); // Grid width (1-12)
            $table->integer('height')->default(4); // Grid height
            $table->boolean('is_public')->default(false); // Can other tenants use it
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable(); // User rating
            $table->json('permissions')->nullable(); // Who can view/edit
            $table->timestamps();
            
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['is_public', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_widgets');
    }
};
