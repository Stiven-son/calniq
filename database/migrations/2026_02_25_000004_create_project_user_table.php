<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('project_id');
            $table->string('role', 20)->default('worker'); // admin, manager, worker
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->unique(['user_id', 'project_id']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
