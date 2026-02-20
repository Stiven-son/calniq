<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('notify_customer_new_booking')->default(true);
            $table->boolean('notify_customer_status_change')->default(true);
            $table->boolean('notify_business_new_booking')->default(true);
            $table->string('notification_email', 255)->nullable();
            $table->string('notification_phone', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'notify_customer_new_booking',
                'notify_customer_status_change',
                'notify_business_new_booking',
                'notification_email',
                'notification_phone',
            ]);
        });
    }
};