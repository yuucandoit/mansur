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
        if (Schema::hasTable('email_templates')) { return; }
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('details', 200)->nullable()->default(null);
            $table->string('slug', 50)->nullable();
            $table->string('language', 10)->default('en')->nullable();
            $table->text('html')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email__templates');
    }
};
