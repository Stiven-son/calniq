<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Google Ads click identifiers to bookings for conversion tracking.
     *
     * gclid  = Google Click ID (standard Google Ads)
     * gbraid = Google BRAID (iOS ATT, app-to-web)
     * wbraid = Google WBRAID (iOS ATT, web-to-web)
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'gclid')) {
                $table->string('gclid', 255)->nullable()->after('ga_client_id');
            }
            if (!Schema::hasColumn('bookings', 'gbraid')) {
                $table->string('gbraid', 255)->nullable()->after('gclid');
            }
            if (!Schema::hasColumn('bookings', 'wbraid')) {
                $table->string('wbraid', 255)->nullable()->after('gbraid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['gclid', 'gbraid', 'wbraid']);
        });
    }
};
