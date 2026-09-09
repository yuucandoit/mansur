<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update projects table with more columns
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'workspace_id')) {
                $table->unsignedBigInteger('workspace_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('projects', 'start_date')) {
                $table->date('start_date')->nullable()->after('description');
            }
            if (!Schema::hasColumn('projects', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('projects', 'is_starred')) {
                $table->tinyInteger('is_starred')->default(0)->after('end_date');
            }
            if (!Schema::hasColumn('projects', 'color')) {
                $table->string('color', 20)->nullable()->after('is_starred');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumnIfExists(['start_date', 'end_date', 'is_starred', 'color']);
        });
    }
};
