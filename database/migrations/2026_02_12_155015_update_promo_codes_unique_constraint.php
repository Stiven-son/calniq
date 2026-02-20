<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'code']);
            $table->unique(['project_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'code']);
            $table->unique(['tenant_id', 'code']);
        });
    }
};