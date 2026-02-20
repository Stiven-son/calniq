<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->integer('global_service_id')->nullable()->after('booking_id');

            $table->foreign('global_service_id')
                ->references('id')
                ->on('global_services')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->dropForeign(['global_service_id']);
            $table->dropColumn('global_service_id');
        });
    }
};
