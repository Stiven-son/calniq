<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // "Starter", "Pro", "Partner"
            $table->string('slug', 50)->unique();      // "starter-39", "pro-79", "partner-0"
            $table->decimal('price', 10, 2);           // 39.00, 79.00, 0.00
            $table->json('limits');                     // {"max_projects": 1, ...}
            $table->boolean('allows_addons')->default(false);
            $table->boolean('is_active')->default(true);   // can sit on this plan
            $table->boolean('is_public')->default(true);   // visible to new customers
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed initial plans
        DB::table('plans')->insert([
            [
                'name' => 'Starter',
                'slug' => 'starter-39',
                'price' => 39.00,
                'limits' => json_encode([
                    'max_projects' => 1,
                    'max_bookings_per_month' => 100,
                    'max_admins_per_project' => 1,
                    'max_managers_per_project' => 1,
                    'max_workers_per_project' => 1,
                ]),
                'allows_addons' => false,
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro-79',
                'price' => 79.00,
                'limits' => json_encode([
                    'max_projects' => 5,
                    'max_bookings_per_month' => null,
                    'max_admins_per_project' => 1,
                    'max_managers_per_project' => 2,
                    'max_workers_per_project' => 3,
                ]),
                'allows_addons' => true,
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Partner',
                'slug' => 'partner-0',
                'price' => 0.00,
                'limits' => json_encode([
                    'max_projects' => 0,
                    'max_bookings_per_month' => 0,
                    'max_admins_per_project' => 0,
                    'max_managers_per_project' => 0,
                    'max_workers_per_project' => 0,
                ]),
                'allows_addons' => false,
                'is_active' => true,
                'is_public' => false, // Partner registration is separate
                'sort_order' => 99,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
