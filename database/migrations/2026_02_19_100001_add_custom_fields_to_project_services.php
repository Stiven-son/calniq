<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_services', function (Blueprint $table) {
            $table->string('custom_name', 255)->nullable()->after('global_service_id');
            $table->text('custom_description')->nullable()->after('custom_name');
        });
    }

    public function down(): void
    {
        Schema::table('project_services', function (Blueprint $table) {
            $table->dropColumn(['custom_name', 'custom_description']);
        });
    }
};
