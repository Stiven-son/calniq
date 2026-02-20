<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove legacy settings/branding/notification columns from tenants.
     * These fields now live exclusively in the projects table.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                // Slug (now in projects)
                'slug',

                // Settings (now in projects)
                'timezone',
                'currency',
                'min_booking_amount',
                'booking_buffer_minutes',
                'advance_booking_days',

                // Branding (now in projects)
                'logo_url',
                'primary_color',
                'secondary_color',

                // Notifications (now in projects)
                'notify_customer_new_booking',
                'notify_customer_status_change',
                'notify_business_new_booking',
                'notification_email',
                'notification_phone',
            ]);
        });

        // Remove min_advance_hours if it exists
        if (Schema::hasColumn('tenants', 'min_advance_hours')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('min_advance_hours');
            });
        }
    }

    /**
     * Reverse: re-add all columns with defaults.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug', 100)->unique()->nullable()->after('name');
            $table->string('timezone', 50)->default('America/New_York')->after('phone');
            $table->string('currency', 3)->default('USD')->after('timezone');
            $table->decimal('min_booking_amount', 10, 2)->default(0)->after('currency');
            $table->integer('booking_buffer_minutes')->default(30)->after('min_booking_amount');
            $table->integer('advance_booking_days')->default(30)->after('booking_buffer_minutes');
            $table->integer('min_advance_hours')->default(24)->after('advance_booking_days');
            $table->string('logo_url', 500)->nullable()->after('min_advance_hours');
            $table->string('primary_color', 7)->default('#10B981')->after('logo_url');
            $table->string('secondary_color', 7)->default('#064E3B')->after('primary_color');
            $table->boolean('notify_customer_new_booking')->default(true)->after('secondary_color');
            $table->boolean('notify_customer_status_change')->default(true)->after('notify_customer_new_booking');
            $table->boolean('notify_business_new_booking')->default(true)->after('notify_customer_status_change');
            $table->string('notification_email', 255)->nullable()->after('notify_business_new_booking');
            $table->string('notification_phone', 50)->nullable()->after('notification_email');
        });
    }
};