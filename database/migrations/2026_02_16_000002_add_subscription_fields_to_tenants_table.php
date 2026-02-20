<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('subscription_status', 20)->default('trial')->after('plan');
            // possible values: trial, active, past_due, cancelled, expired
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_ends_at');
            $table->integer('notification_days_before')->default(3)->after('trial_ends_at');
            $table->timestamp('last_notified_at')->nullable()->after('notification_days_before');
        });

        // Set trial_ends_at for existing tenants (14 days from now)
        DB::table('tenants')->whereNull('trial_ends_at')->update([
            'trial_ends_at' => now()->addDays(14),
            'subscription_status' => 'trial',
        ]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_status',
                'trial_ends_at',
                'notification_days_before',
                'last_notified_at',
            ]);
        });
    }
};