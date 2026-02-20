<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');

            $table->string('name', 255);
            $table->string('slug', 100)->unique();

            // Settings
            $table->string('timezone', 50)->default('America/New_York');
            $table->string('currency', 3)->default('USD');
            $table->decimal('min_booking_amount', 10, 2)->default(0);
            $table->integer('booking_buffer_minutes')->default(30);
            $table->integer('advance_booking_days')->default(30);

            // Branding
            $table->string('logo_url', 500)->nullable();
            $table->string('primary_color', 7)->default('#10B981');
            $table->string('secondary_color', 7)->default('#064E3B');

            // Notifications
            $table->boolean('notify_customer_new_booking')->default(true);
            $table->boolean('notify_customer_status_change')->default(true);
            $table->boolean('notify_business_new_booking')->default(true);
            $table->string('notification_email', 255)->nullable();
            $table->string('notification_phone', 50)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Миграция данных: создаём project для каждого tenant
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            DB::table('projects')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid7(),
                'tenant_id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'timezone' => $tenant->timezone,
                'currency' => $tenant->currency,
                'min_booking_amount' => $tenant->min_booking_amount,
                'booking_buffer_minutes' => $tenant->booking_buffer_minutes,
                'advance_booking_days' => $tenant->advance_booking_days,
                'logo_url' => $tenant->logo_url,
                'primary_color' => $tenant->primary_color,
                'secondary_color' => $tenant->secondary_color,
                'notify_customer_new_booking' => $tenant->notify_customer_new_booking,
                'notify_customer_status_change' => $tenant->notify_customer_status_change,
                'notify_business_new_booking' => $tenant->notify_business_new_booking,
                'notification_email' => $tenant->notification_email,
                'notification_phone' => $tenant->notification_phone,
                'is_active' => true,
                'created_at' => $tenant->created_at,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};