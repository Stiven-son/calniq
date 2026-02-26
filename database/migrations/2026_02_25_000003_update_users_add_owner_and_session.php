<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_owner')->default(false)->after('is_super_admin');
            $table->string('current_session_id', 255)->nullable()->after('remember_token');
        });

        // Set is_owner=true for the first user of each tenant (the one who registered)
        $tenantIds = DB::table('tenants')->pluck('id');
        foreach ($tenantIds as $tenantId) {
            $firstUser = DB::table('users')
                ->where('tenant_id', $tenantId)
                ->orderBy('id')
                ->first();

            if ($firstUser) {
                DB::table('users')
                    ->where('id', $firstUser->id)
                    ->update(['is_owner' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_owner', 'current_session_id']);
        });
    }
};
