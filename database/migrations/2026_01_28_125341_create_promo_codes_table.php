<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');

            $table->string('code', 50);
            $table->string('description', 255)->nullable();

            $table->string('discount_type', 20); // percent, fixed
            $table->decimal('discount_value', 10, 2);

            $table->integer('max_uses')->nullable();
            $table->integer('current_uses')->default(0);
            $table->decimal('min_order_amount', 10, 2)->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->jsonb('applicable_services')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
