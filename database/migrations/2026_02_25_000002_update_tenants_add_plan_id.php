<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable()->after('plan');
            $table->uuid('referred_by')->nullable()->after('stripe_customer_id');
            $table->boolean('is_partner')->default(false)->after('referred_by');

            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
            $table->foreign('referred_by')->references('id')->on('tenants')->nullOnDelete();
        });

        // Migrate existing tenants: map varchar plan → plan_id
        $planMap = DB::table('plans')->pluck('id', 'slug');

        // Map old plan names to new slugs
        $mapping = [
            'starter' => 'starter-39',
            'pro'     => 'pro-79',
            'agency'  => 'pro-79', // agency → pro (grandfathered)
        ];

        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $tenant) {
            $newSlug = $mapping[$tenant->plan] ?? 'starter-39';
            $planId = $planMap[$newSlug] ?? $planMap['starter-39'];

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['plan_id' => $planId]);
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['plan_id', 'referred_by', 'is_partner']);
        });
    }
};
