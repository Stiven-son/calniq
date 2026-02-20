<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'locations',
            'service_categories',
            'services',
            'promo_codes',
            'bookings',
            'webhook_endpoints',
        ];

        // 1. Добавляем project_id (nullable) ко всем таблицам
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->uuid('project_id')->nullable()->after('tenant_id');
            });
        }

        // 2. Заполняем project_id на основе tenant_id → project
        $projects = DB::table('projects')->pluck('id', 'tenant_id');

        foreach ($tables as $table) {
            foreach ($projects as $tenantId => $projectId) {
                DB::table($table)
                    ->where('tenant_id', $tenantId)
                    ->update(['project_id' => $projectId]);
            }
        }

        // 3. Делаем NOT NULL + foreign key + index
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->uuid('project_id')->nullable(false)->change();
                $t->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
                $t->index('project_id');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'webhook_endpoints',
            'bookings',
            'promo_codes',
            'services',
            'service_categories',
            'locations',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['project_id']);
                $t->dropIndex(['project_id']);
                $t->dropColumn('project_id');
            });
        }
    }
};