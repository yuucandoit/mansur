<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 200)->nullable();
            $table->text('description')->nullable();
            $table->integer('cover')->nullable();
            $table->unsignedBigInteger('list_id')->index();
            $table->unsignedBigInteger('project_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->integer('order')->default(0)->index();
            $table->tinyInteger('is_done')->default(0);
            $table->tinyInteger('is_archive')->default(0);
            $table->tinyInteger('is_critical')->default(0);
            $table->string('priority', 20)->nullable(); // low, medium, high, critical
            $table->timestamp('due_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
