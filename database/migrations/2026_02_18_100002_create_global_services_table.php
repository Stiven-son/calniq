<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_category_id')->constrained('global_categories')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('default_price', 10, 2);
            $table->string('price_type', 20)->default('fixed');   // fixed, per_unit, per_sqft
            $table->string('price_unit', 50)->nullable();          // "seat", "sq ft", "room"
            $table->string('image_url', 500)->nullable();
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->default(10);
            $table->integer('duration_minutes')->default(60);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['global_category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_services');
    }
};
