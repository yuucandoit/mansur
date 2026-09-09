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
        if (Schema::hasTable('attachments')) { return; }
        Schema::create('attachments', function (Blueprint $table) {
            $table->id('id');
            $table->integer('task_id')->nullable()->index();
            $table->integer('comment_id')->nullable()->index();
            $table->string('name', 150)->nullable();
            $table->integer('user_id')->nullable()->index();
            $table->integer('size')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('path', 250);
            $table->timestamps();   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
