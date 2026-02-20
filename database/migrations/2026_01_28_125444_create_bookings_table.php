<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('location_id')->nullable();

            $table->string('reference_number', 20)->unique();

            $table->string('customer_name', 255);
            $table->string('customer_email', 255);
            $table->string('customer_phone', 50);
            $table->string('customer_type', 20)->default('residential');

            $table->text('address');
            $table->string('address_unit', 50)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('zip', 20)->nullable();

            $table->date('scheduled_date');
            $table->time('scheduled_time_start');
            $table->time('scheduled_time_end');

            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->uuid('promo_code_id')->nullable();
            $table->string('promo_code_used', 50)->nullable();

            $table->string('status', 20)->default('pending');

            $table->text('message')->nullable();
            $table->string('preferred_contact_time', 50)->nullable();

            $table->string('source', 50)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('ga_client_id', 100)->nullable();

            $table->string('google_event_id', 255)->nullable();

            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->onDelete('set null');

            $table->index(['tenant_id', 'scheduled_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
