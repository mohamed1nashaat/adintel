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
        Schema::create('offline_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); // Who entered the data
            $table->string('conversion_type'); // 'sale', 'lead', 'call', 'meeting', 'demo', etc.
            $table->string('source')->default('manual'); // 'manual', 'import', 'api', 'whatsapp', etc.
            $table->string('campaign_reference')->nullable(); // Link to online campaign
            $table->string('customer_identifier')->nullable(); // Phone, email, ID
            $table->decimal('conversion_value', 10, 2)->nullable();
            $table->string('currency', 3)->default('SAR');
            $table->date('conversion_date');
            $table->time('conversion_time')->nullable();
            $table->text('description')->nullable();
            $table->json('custom_fields')->nullable(); // Flexible additional data
            $table->string('sales_rep')->nullable();
            $table->string('lead_quality')->nullable(); // 'hot', 'warm', 'cold'
            $table->enum('status', ['pending', 'qualified', 'converted', 'lost', 'follow_up']);
            $table->text('notes')->nullable();
            $table->json('attribution_data')->nullable(); // Which ads/campaigns influenced
            $table->string('import_batch_id')->nullable(); // For bulk imports
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['tenant_id', 'conversion_type']);
            $table->index(['tenant_id', 'conversion_date']);
            $table->index(['tenant_id', 'status']);
            $table->index(['campaign_reference']);
            $table->index(['import_batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_conversions');
    }
};
