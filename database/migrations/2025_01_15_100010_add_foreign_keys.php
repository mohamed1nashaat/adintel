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
        // Add foreign key constraints after all tables are created
        Schema::table('content_posts', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')->on('content_templates')->onDelete('set null');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('lead_source_id')->references('id')->on('lead_sources')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_posts', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['lead_source_id']);
        });
    }
};
