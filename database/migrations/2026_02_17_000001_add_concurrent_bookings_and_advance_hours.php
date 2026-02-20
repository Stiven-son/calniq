<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add max_concurrent_bookings to locations and min_advance_hours to projects.
     */
    public function up(): void
    {
        // max_concurrent_bookings = how many bookings can overlap on the same time slot
        // (e.g., 3 crews can work simultaneously)
        if (!Schema::hasColumn('locations', 'max_concurrent_bookings')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->integer('max_concurrent_bookings')
                    ->default(1)
                    ->after('google_refresh_token');
            });
        }

        // min_advance_hours = minimum hours before a booking can be made
        // (e.g., 24 = must book at least 24 hours in advance)
        if (!Schema::hasColumn('projects', 'min_advance_hours')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->integer('min_advance_hours')
                    ->default(24)
                    ->after('advance_booking_days');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('locations', 'max_concurrent_bookings')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('max_concurrent_bookings');
            });
        }

        if (Schema::hasColumn('projects', 'min_advance_hours')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('min_advance_hours');
            });
        }
    }
};
