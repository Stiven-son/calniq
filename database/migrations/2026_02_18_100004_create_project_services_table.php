<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_services', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('global_service_id')->constrained('global_services')->cascadeOnDelete();
            $table->decimal('custom_price', 10, 2)->nullable();    // NULL = use default_price
            $table->string('custom_image', 500)->nullable();        // NULL = use global image
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'global_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_services');
    }
};
