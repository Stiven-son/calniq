<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('category_id')->nullable();

            $table->string('name', 255);
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

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('service_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
