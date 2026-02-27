<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop service_id FK from booking_items
        Schema::table('booking_items', function (Blueprint $table) {
            // Drop foreign key first, then column
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });

        // 2. Drop services table (has FK to service_categories)
        Schema::dropIfExists('services');

        // 3. Drop service_categories table
        Schema::dropIfExists('service_categories');
    }

    public function down(): void
    {
        // Recreate service_categories
        Schema::create('service_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('project_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Recreate services
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('project_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('price_type', 20)->default('fixed');
            $table->string('price_unit', 50)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->default(10);
            $table->integer('duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('service_categories')->onDelete('set null');
        });

        // Re-add service_id to booking_items
        Schema::table('booking_items', function (Blueprint $table) {
            $table->uuid('service_id')->nullable()->after('booking_id');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
        });
    }
};
