<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('roles')) { return; }
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', '100');
            $table->string('slug', '100');
            $table->integer('create_workspace')->default(0);
            $table->integer('create_project')->default(0);
            $table->json('access')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
