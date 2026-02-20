<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->string('email', 255);
            $table->string('phone', 50)->nullable();
            $table->string('timezone', 50)->default('America/New_York');
            $table->string('currency', 3)->default('USD');

            $table->decimal('min_booking_amount', 10, 2)->default(0);
            $table->integer('booking_buffer_minutes')->default(30);
            $table->integer('advance_booking_days')->default(30);

            $table->string('logo_url', 500)->nullable();
            $table->string('primary_color', 7)->default('#10B981');
            $table->string('secondary_color', 7)->default('#064E3B');

            $table->string('plan', 20)->default('starter');
            $table->string('stripe_customer_id', 255)->nullable();
            $table->timestamp('subscription_ends_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
